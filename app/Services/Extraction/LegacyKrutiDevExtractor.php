<?php

namespace App\Services\Extraction;

use DateTime;

class LegacyKrutiDevExtractor implements BillExtractorInterface
{
    public function getEngineName(): string
    {
        return 'legacy_krutidev';
    }

    /**
     * Check for legacy Kruti Dev / iText bill markers.
     */
    public function supports(string $text): bool
    {
        return str_contains($text, 'miHkks') ||
               str_contains($text, 'fcy ekg') ||
               str_contains($text, 'rd ns; jkf') ||
               str_contains($text, ',e vkj ;q') ||
               str_contains($text, '[kir fooj');
    }

    /**
     * Extract structured fields from legacy Kruti Dev bills.
     */
    public function extract(string $text, ?string $pdfPath = null): array
    {
        $data = [
            'engine' => $this->getEngineName(),
            'consumer_name' => null,
            'father_name' => null,
            'bill_month' => null,
            'bill_date' => null,
            'due_date' => null,
            'current_reading' => null,
            'previous_reading' => null,
            'units_consumed' => 0,
            'total_amount' => 0.0,
            'meter_no' => null,
            'tariff_category' => null,
            'billing_basis' => 'OK',
            'mru' => null,
            'consumption_history' => [],
        ];

        // 1. Consumer Name: (matches uppercase English name line before miHkks)
        if (preg_match('/\n([^\n\r]+?)\s*[\t\s]+miHkks/u', $text, $m)) {
            $raw = trim(preg_replace('/[^A-Za-z0-9\s\.\,\/\-\&\(\)]/u', '', $m[1]));
            $data['consumer_name'] = preg_replace('/\s+/', ' ', $raw);
        } elseif (preg_match('/miHkksDrk dk uke[^\n\r]*\n\s*([^\n\r]+)/u', $text, $m)) {
            $data['consumer_name'] = trim($m[1]);
        }

        // 2. Father / Relative Name:
        if (preg_match('/\n([A-Z0-9\s\.\,\/\-]+?)\s*[\t\s]+,e vkj ;q/u', $text, $mFather)) {
            $rawFather = trim(preg_replace('/[^A-Za-z0-9\s\.\,\/\-\&\(\)]/u', '', $mFather[1]));
            if (! empty($rawFather) && ! str_contains($rawFather, 'VILL') && strlen($rawFather) >= 3) {
                $data['father_name'] = preg_replace('/\s+/', ' ', $rawFather);
            }
        }

        // 3. Bill Month:
        if (preg_match('/fcy ekg\s*\n?\s*([A-Z]{3},\s*\d{4})/i', $text, $m)) {
            $data['bill_month'] = trim($m[1]);
        }

        // 4. Total Amount:
        if (preg_match('/\d{2}-\d{2}-\d{4}\s+rd ns; jkf\'k\s*\n\s*(-?\s*[\d,]+\.?\d*)/u', $text, $m)) {
            $data['total_amount'] = (float) str_replace([' ', ','], '', $m[1]);
        } elseif (preg_match_all('/dqy jkf\'k\s+(-?\s*[\d.]+)/u', $text, $amounts)) {
            $data['total_amount'] = (float) str_replace(' ', '', end($amounts[1]));
        }

        // 5. Due Date:
        if (preg_match('/(\d{2}-\d{2}-\d{4})\s+rd ns; jkf\'k/u', $text, $m)) {
            $d = DateTime::createFromFormat('d-m-Y', $m[1]);
            $data['due_date'] = $d ? $d->format('Y-m-d') : null;
        }

        // 6. Bill Date:
        if (preg_match('/fcy frfFk\s*\n\s*(\d{2}-\d{2}-\d{4})/u', $text, $m)) {
            $d = DateTime::createFromFormat('d-m-Y', $m[1]);
            $data['bill_date'] = $d ? $d->format('Y-m-d') : null;
        }

        // 7. Meter Readings & Units:
        $readingPattern = '/(\d+)\s+(\d{2}-\d{2}-\d{4})\s*(\d+)\s+(\d{2}-[A-Z]{3}-\d{2})\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/i';
        if (preg_match($readingPattern, $text, $m)) {
            $data['meter_no'] = $m[1];
            $data['current_reading'] = (int) $m[3];
            $data['previous_reading'] = (int) $m[5];
            $data['units_consumed'] = (int) $m[6];
        } elseif (preg_match('/dqy \[kir\s*\n\s*(\d+)/u', $text, $m)) {
            $data['units_consumed'] = (int) $m[1];
        }

        // 8. Tariff Category (under Js.kh)
        if (preg_match('/Js\.kh\s*\n\s*([A-Za-z0-9\(\)\/\-\_]+)/u', $text, $m)) {
            $data['tariff_category'] = trim($m[1]);
        }

        // 9. Billing Basis (under fcy dk vkèkkj)
        if (preg_match('/fcy dk vkèkkj\s*\n\s*([A-Za-z0-9\(\)\/\-\_]+)/u', $text, $m)) {
            $rawBasis = trim($m[1]);
            if (stripos($rawBasis, 'MD') !== false) {
                $data['billing_basis'] = 'MD';
            } elseif (stripos($rawBasis, 'LK') !== false) {
                $data['billing_basis'] = 'LK';
            } elseif (stripos($rawBasis, 'PL') !== false) {
                $data['billing_basis'] = 'PL';
            } elseif (stripos($rawBasis, 'RN') !== false) {
                $data['billing_basis'] = 'RN';
            } elseif (stripos($rawBasis, 'Normal') !== false || stripos($rawBasis, 'OK') !== false) {
                $data['billing_basis'] = 'OK';
            } else {
                $data['billing_basis'] = strtoupper(substr($rawBasis, 0, 4));
            }
        }

        // 10. MRU:
        if (preg_match('/,e vkj ;q\s*\n\s*([A-Za-z0-9_\-\s]+?)(?=\n\d|\n[A-Z]|\nrd)/u', $text, $m)) {
            $data['mru'] = trim(str_replace(["\r", "\n", ' '], '', $m[1]));
        } elseif (preg_match('/,e vkj ;q\s+([A-Za-z0-9_\-]+)/u', $text, $m)) {
            $data['mru'] = trim($m[1]);
        }

        // 11. Historical Monthly Consumption Table ([kir fooj.kh):
        if (preg_match('/\[kir fooj\.kh(.*?)(?:lHkh|\z)/us', $text, $sec)) {
            if (preg_match_all('/([A-Z]{3})\/(\d{2})\s+(\d+)(?:\(([A-Za-z0-9]+)(?:,\s*[A-Za-z0-9]+)?\))?/i', $sec[1], $histMatches, PREG_SET_ORDER)) {
                $monthMap = [
                    'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6,
                    'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
                ];
                foreach ($histMatches as $hm) {
                    $mShort = strtoupper($hm[1]);
                    $mNum = $monthMap[$mShort] ?? null;
                    $yNum = 2000 + (int) $hm[2];
                    $units = (int) $hm[3];
                    $basis = ! empty($hm[4]) ? strtoupper($hm[4]) : 'OK';

                    if ($mNum && $units >= 0) {
                        $data['consumption_history'][] = [
                            'month' => $mNum,
                            'year' => $yNum,
                            'month_label' => "{$mShort}, {$yNum}",
                            'units' => $units,
                            'basis' => $basis,
                        ];
                    }
                }
            }
        }

        return $data;
    }
}
