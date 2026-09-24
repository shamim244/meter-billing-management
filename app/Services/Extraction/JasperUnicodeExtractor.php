<?php

namespace App\Services\Extraction;

class JasperUnicodeExtractor implements BillExtractorInterface
{
    public function getEngineName(): string
    {
        return 'jasper_unicode';
    }

    /**
     * Check for JasperReports / FluentGrid Unicode bill markers.
     */
    public function supports(string $text): bool
    {
        return str_contains($text, 'विधुत') ||
               str_contains($text, 'बिल माह') ||
               str_contains($text, 'स्मार्ट प्रीपेड') ||
               str_contains($text, 'JasperReports') ||
               str_contains($text, 'KWH') ||
               str_contains($text, 'BARSOI_NEW') ||
               str_contains($text, 'बिल का आधार');
    }

    /**
     * Extract structured fields from new JasperReports Unicode bills.
     */
    public function extract(string $text, ?string $pdfPath = null): array
    {
        // 1. Sanitize to 100% valid UTF-8 by round-tripping through UTF-32 with 'none' substitute
        mb_substitute_character('none');
        $cleanText = mb_convert_encoding($text, 'UTF-32', 'UTF-8');
        $cleanText = mb_convert_encoding($cleanText, 'UTF-8', 'UTF-32');

        // 2. Normalize multi-byte non-breaking spaces and soft hyphens
        $cleanText = str_replace(["\xc2\xa0", "\xc2\xad"], [' ', '-'], $cleanText);

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

        // 1. Consumer Name
        if (preg_match('/नाम[\t\s]*:[\t\s]*([^:\r\n]+)/u', $cleanText, $m) && strlen(trim($m[1])) >= 3 && ! preg_match('/^\d/', trim($m[1]))) {
            $data['consumer_name'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        } elseif (preg_match('/:\s*([A-Z][A-Z\s]{2,}[A-Z])\s*\r?\n\s*\d{2}\*{5}/', $cleanText, $m)) {
            $data['consumer_name'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        } elseif (preg_match('/:\s*NA\s*\r?\n\s*:\s*([A-Z\s]{3,})/u', $cleanText, $m)) {
            $data['consumer_name'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        }

        // 2. Father / Relative Name
        if (preg_match('/पिता का नाम[\t\s]*:[\t\s]*([^:\r\n]+)/u', $cleanText, $m)) {
            $f = trim(preg_replace('/\s+/', ' ', $m[1]));
            if (! empty($f) && $f !== '-') {
                $data['father_name'] = $f;
            }
        }

        // 3. Bill Month
        if (preg_match('/:\s*([A-Z]{3}\s*,\s*\d{4})/i', $cleanText, $m)) {
            $data['bill_month'] = trim($m[1]);
        }

        // 4. Total Amount
        if (preg_match('/(\d+\.\d{2})\s*\r?\n\s*(\d+\.\d{2})\s*\r?\n\s*(\d+\.\d{2})\s*\r?\n\s*:\s*\d{11}/', $cleanText, $m)) {
            $data['total_amount'] = (float) $m[1];
        } elseif (preg_match('/वर्तमान विपत्र राशि[^\d\r\n]*(-?[\d,]+\.?\d*)/u', $cleanText, $m)) {
            $data['total_amount'] = (float) str_replace([' ', ','], '', $m[1]);
        } elseif (preg_match('/कुल राशि\s*[\r\n]+[^\d\r\n]*(\d+\.\d{2})/u', $cleanText, $m)) {
            $data['total_amount'] = (float) $m[1];
        }

        // 5. Due Date
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*\r?\n\s*(\d{2}\/\d{2}\/\d{4})\s*\r?\n\s*(\d{2}\/\d{2}\/\d{4})/', $cleanText, $m)) {
            $data['due_date'] = date('Y-m-d', strtotime(str_replace('/', '-', $m[2])));
        }

        // 6. Meter Reading, Bill Date & Readings
        if (preg_match('/(\d+)\s+KWH\s+(\d{2}-\d{2}-\d{4})\s+(\d+)\s+(\d{2}-\d{2}-\d{4})\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/i', $cleanText, $m)) {
            $data['meter_no'] = $m[1];
            $data['bill_date'] = date('Y-m-d', strtotime($m[2]));
            $data['current_reading'] = (int) $m[3];
            $data['previous_reading'] = (int) $m[5];
            $data['units_consumed'] = (int) $m[8];
        } elseif (preg_match('/KT\d+/i', $cleanText, $m)) {
            $data['meter_no'] = $m[0];
            if (preg_match('/1\s+(\d{2}-\d{2}-\d{4})\s+(\d+)/', $cleanText, $mRead)) {
                $data['current_reading'] = (int) $mRead[2];
                $data['bill_date'] = date('Y-m-d', strtotime($mRead[1]));
            }
        }

        // 7. Tariff Category & Billing Basis
        if (preg_match('/:\s*(Kutir\s*Jyoti[^\r\n\t:]*|DS-[^\r\n\t:]+|NDS-[^\r\n\t:]+|LTIS-[^\r\n\t:]+)/iu', $cleanText, $m)) {
            $data['tariff_category'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        }
        if (preg_match('/:\s*(?:ACTUAL|NORMAL)\s*\(([A-Za-z0-9]+)\)/iu', $cleanText, $m)) {
            $data['billing_basis'] = strtoupper($m[1]);
        } elseif (preg_match('/बिल का आधार[\t\s]*:[\t\s]*([^\r\n\t]+)/u', $cleanText, $m)) {
            $basisRaw = trim($m[1]);
            $data['billing_basis'] = (stripos($basisRaw, 'OK') !== false) ? 'OK' : ((stripos($basisRaw, 'LK') !== false) ? 'LK' : $basisRaw);
        }

        // 8. MRU Identifier
        if (preg_match('/:\s*(\d{4}\s*\/\s*[A-Z_]+)/', $cleanText, $m)) {
            $data['mru'] = trim(str_replace([' ', "\t"], '', $m[1]));
        }

        // 9. Historical Monthly Consumption (12-Month Ledger)
        if (preg_match_all('/([A-Z][a-z]{2})-(\d{4})\s+(\d+)\s*\(([A-Za-z0-9]+)\)/', $cleanText, $mHist, PREG_SET_ORDER)) {
            $monthMap = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6, 'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];
            foreach ($mHist as $h) {
                $mNum = $monthMap[$h[1]] ?? null;
                if ($mNum) {
                    $data['consumption_history'][] = [
                        'month' => $mNum,
                        'year' => (int) $h[2],
                        'month_label' => "{$h[1]}, {$h[2]}",
                        'units' => (int) $h[3],
                        'basis' => $h[4],
                    ];
                }
            }
        }

        return $data;
    }
}
