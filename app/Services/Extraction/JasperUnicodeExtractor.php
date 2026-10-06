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
               str_contains($text, 'विद्युत') ||
               str_contains($text, 'बिल माह') ||
               str_contains($text, 'स्मार्ट प्रीपेड') ||
               str_contains($text, 'JasperReports') ||
               str_contains($text, 'बिल का आधार') ||
               str_contains($text, 'विपत्र') ||
               (preg_match_all('/[\x{0900}-\x{097F}]/u', $text) > 10);
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

        // 2. Normalize multi-byte non-breaking spaces, soft hyphens, and unicode dashes/minuses
        $cleanText = str_replace(
            ["\xc2\xa0", "\xc2\xad", "\xe2\x88\x92", "\xe2\x80\x93", "\xe2\x80\x94"],
            [' ', '-', '-', '-', '-'],
            $cleanText
        );

        $data = [
            'engine' => $this->getEngineName(),
            'consumer_name' => null,
            'father_name' => null,
            'bill_number' => null,
            'bill_month' => null,
            'bill_date' => null,
            'due_date' => null,
            'sanctioned_load' => null,
            'phase' => null,
            'current_reading' => null,
            'previous_reading' => null,
            'units_consumed' => 0,
            'total_amount' => 0.0,
            'energy_charges' => 0.0,
            'fixed_charges' => 0.0,
            'government_subsidy' => 0.0,
            'electricity_duty' => 0.0,
            'arrears' => 0.0,
            'meter_no' => null,
            'tariff_category' => null,
            'billing_basis' => 'OK',
            'mru' => null,
            'consumption_history' => [],
        ];

        // 1. Consumer Name (supports dots, slashes, hyphens, ampersands, apostrophes, parentheses, and Devanagari)
        // 1a. Postpaid Jasper layout: Consumer name preceded by : NA and colon
        if ($data['consumer_name'] === null && preg_match('/:\s*NA\s*\r?\n\s*:\s*([^\r\n:]+)/u', $cleanText, $m)) {
            $candidate = $this->sanitizeConsumerName($m[1]);
            if ($candidate !== null) {
                $data['consumer_name'] = $candidate;
            }
        }

        // 1b. Postpaid Jasper layout where consumer name line is followed by masked mobile number (e.g. 89*****724 or 10 digits)
        if ($data['consumer_name'] === null && preg_match('/:\s*([^\r\n:]+)\s*\r?\n\s*(?:\d{2}\*{4,}|\d{10})/u', $cleanText, $m)) {
            $candidate = $this->sanitizeConsumerName($m[1]);
            if ($candidate !== null) {
                $data['consumer_name'] = $candidate;
            }
        }

        // 1c. नाम or उपभोक्ता का नाम label with colon
        if ($data['consumer_name'] === null && preg_match('/(?:नाम|उपभोक्ता\s*का\s*नाम)[\t\s]*:[\t\s]*([^\r\n:]+)/u', $cleanText, $m)) {
            $candidate = $this->sanitizeConsumerName($m[1]);
            if ($candidate !== null) {
                $data['consumer_name'] = $candidate;
            }
        }

        // 1d. Postpaid Jasper layout followed by connection date line
        if ($data['consumer_name'] === null && preg_match('/:\s*([^\r\n:]+)\s*\r?\n\s*:\s*\d{2}\/\d{2}\/\d{4}/u', $cleanText, $m)) {
            $candidate = $this->sanitizeConsumerName($m[1]);
            if ($candidate !== null) {
                $data['consumer_name'] = $candidate;
            }
        }

        // 2. Father / Relative Name
        if (preg_match('/पिता का नाम[\t\s]*:[\t\s]*([^:\r\n]+)/u', $cleanText, $m)) {
            $f = trim(preg_replace('/\s+/', ' ', $m[1]));
            if (! empty($f) && $f !== '-' && ! str_contains($f, 'कनेक्शन') && ! str_contains($f, 'विवरणी')) {
                $data['father_name'] = $f;
            }
        }

        // 3. Bill Number (17-18 digit unique invoice identifier)
        if (preg_match('/:\s*(20\d{14,18})/u', $cleanText, $m)) {
            $data['bill_number'] = $m[1];
        } elseif (preg_match('/(20\d{14,18})/u', $cleanText, $m)) {
            $data['bill_number'] = $m[1];
        }

        // 4. Bill Month
        if (preg_match('/:\s*([A-Z]{3}\s*,\s*\d{4})/i', $cleanText, $m)) {
            $data['bill_month'] = trim($m[1]);
        }

        // 5. Total Amount (supports negative amounts, advance/credit balances, CR suffix, and parentheses)
        $amountFound = null;

        // 5a. Primary 3-line amount block in Jasper postpaid bills before CA number
        if (preg_match('/([^\r\n:]+?)\s*\r?\n\s*([^\r\n:]+?)\s*\r?\n\s*([^\r\n:]+?)\s*\r?\n\s*:\s*\d{11}\b/u', $cleanText, $m)) {
            $candidate = $this->parseAmount($m[1]);
            if ($candidate !== null) {
                $amountFound = $candidate;
            }
        }

        // 5b. वर्तमान विपत्र राशि (Current Bill Amount - supports positive and negative amounts)
        if ($amountFound === null && preg_match('/वर्तमान\s*विपत्र\s*राशि[^\r\n\d\-−–—\(]*([^\r\n]+)/u', $cleanText, $m)) {
            $candidate = $this->parseAmount($m[1]);
            if ($candidate !== null) {
                $amountFound = $candidate;
            }
        }

        // 5c. कुल राशि / कुल देय राशि (Total Payable Amount)
        if ($amountFound === null && preg_match('/(?:कुल\s*देय\s*राशि|नियत\s*तिथि\s*तक\s*देय\s*राशि|कुल\s*राशि)\s*[\r\n:]+[^\r\n\d\-−–—\(]*([^\r\n]+)/u', $cleanText, $m)) {
            $candidate = $this->parseAmount($m[1]);
            if ($candidate !== null) {
                $amountFound = $candidate;
            }
        }

        // 5d. Prepaid Balance (जमा शेष) when credit balance exists
        if ($amountFound === null && preg_match('/जमा\s*शेष[^\r\n\d\-−–—\(]*([^\r\n]+)/u', $cleanText, $m)) {
            $candidate = $this->parseAmount($m[1]);
            if ($candidate !== null) {
                $amountFound = $candidate;
            }
        }

        // 5e. Smart Prepaid net balance fallback (under मीटर पठन विवरणी before कुल खपत)
        if ($amountFound === null && preg_match('/मीटर\s*पठन\s*विवरणी\s*\r?\n\s*([^\r\n]+)\s*\r?\n\s*कुल\s*खपत/u', $cleanText, $m)) {
            $candidate = $this->parseAmount($m[1]);
            if ($candidate !== null) {
                $amountFound = $candidate;
            }
        }

        if ($amountFound !== null) {
            $data['total_amount'] = $amountFound;
        }

        // 6. Due Date
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*\r?\n\s*(\d{2}\/\d{2}\/\d{4})\s*\r?\n\s*(\d{2}\/\d{2}\/\d{4})/', $cleanText, $m)) {
            $data['due_date'] = date('Y-m-d', strtotime(str_replace('/', '-', $m[2])));
        }

        // 7. Sanctioned Load & Phase
        if (preg_match('/:\s*(\d+(?:\.\d+)?)\s*(KW|HP|KVA)/iu', $cleanText, $m)) {
            $data['sanctioned_load'] = $m[1].' '.strtoupper($m[2]);
        }
        if (preg_match('/फेज\s*[\r\n\s:]*(\d+)/u', $cleanText, $m)) {
            $data['phase'] = (int) $m[1];
        } elseif (preg_match('/:\s*([13])\s*\r?\n\s*:\s*(?:Kutir|DS|NDS)/u', $cleanText, $m)) {
            $data['phase'] = (int) $m[1];
        }

        // 8. Meter Serial, Reading Dates & Values
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

        // 9. Tariff Category & Billing Basis (supports OK, LK, MD, PL, RN, EST, MB)
        if (preg_match('/:\s*(Kutir\s*Jyoti[^\r\n\t:]*|DS-[^\r\n\t:]+|NDS-[^\r\n\t:]+|LTIS-[^\r\n\t:]+|IAS-[^\r\n\t:]+|SS-[^\r\n\t:]+)/iu', $cleanText, $m)) {
            $data['tariff_category'] = trim(preg_replace('/\s+/', ' ', $m[1]));
        }

        $basisResolved = null;

        // 9a. Descriptor followed by code in parentheses with leading colon: : ACTUAL (OK), : LOCKED (LK), : DEFECTIVE (MD), etc.
        if (preg_match('/:\s*(?:ACTUAL|NORMAL|LOCKED|DOOR\s*LOCKED|DEFECTIVE|METER\s*DEFECTIVE|AVERAGE|ESTIMATE|POWER\s*LINE|PL|RN|MB)?\s*\(([A-Za-z0-9]{2,4})\)/iu', $cleanText, $m)) {
            $basisResolved = $this->normalizeBillingBasis($m[1]);
        }

        // 9b. Multiline lookahead after 'बिल का आधार'
        if ($basisResolved === null && preg_match('/बिल\s*का\s*आधार[\s\S]{1,600}?:\s*(?:ACTUAL|NORMAL|LOCKED|DOOR\s*LOCKED|DEFECTIVE|METER\s*DEFECTIVE)?\s*\(([A-Za-z0-9]{2,4})\)/u', $cleanText, $m)) {
            $basisResolved = $this->normalizeBillingBasis($m[1]);
        }

        // 9c. Standalone basis code with colon: : LK, : MD, : OK, : PL, : RN
        if ($basisResolved === null && preg_match('/:\s*(OK|LK|MD|PL|RN|EST|MB|AVG)\b/i', $cleanText, $m)) {
            $basisResolved = $this->normalizeBillingBasis($m[1]);
        }

        // 9d. Look for code in parentheses without colon
        if ($basisResolved === null && preg_match('/\b(?:ACTUAL|NORMAL|LOCKED|DOOR\s*LOCKED|DEFECTIVE|METER\s*DEFECTIVE|AVERAGE|ESTIMATE|POWER\s*LINE)\s*\(([A-Za-z0-9]{2,4})\)/iu', $cleanText, $m)) {
            $basisResolved = $this->normalizeBillingBasis($m[1]);
        }

        $data['billing_basis'] = $basisResolved ?: 'OK';

        // 10. MRU Identifier
        if (preg_match('/:\s*(\d{4}\s*\/\s*[A-Z_]+)/', $cleanText, $m)) {
            $data['mru'] = trim(str_replace([' ', "\t"], '', $m[1]));
        }

        // 11. Financial Breakdown
        if (preg_match('/(\d+\.\d{2})\s*:\s*(\d+\.\d{2})\s*:\s*(\d+\.\d{2})\s*0\.00\s*किश्त राशि/u', $cleanText, $m)) {
            $data['energy_charges'] = (float) $m[2];
            $data['fixed_charges'] = (float) $m[3];
        }
        if (preg_match('/(\d+\.\d{2})\s*0\.00\s*:\s*(-?\d+\.\d{2})/u', $cleanText, $m)) {
            $data['electricity_duty'] = (float) $m[1];
            $data['government_subsidy'] = (float) $m[2];
        }
        if (preg_match('/(\d+\.\d{2})\s*0\.00(?:\s*:\s*)+मीटर पठन विवरणी/u', $cleanText, $m)) {
            $data['arrears'] = (float) $m[1];
        }

        // 12. Historical Monthly Consumption (12-Month Ledger)
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

            // Fallback to latest month in historical ledger if main header basis was not found
            if ($basisResolved === null && ! empty($data['consumption_history'])) {
                $data['billing_basis'] = $this->normalizeBillingBasis($data['consumption_history'][0]['basis'] ?? null);
            }
        }

        // 13. Sanity Reconciliation: If units_consumed is 0, infer from readings or recent ledger
        if ($data['units_consumed'] <= 0) {
            if ($data['current_reading'] !== null && $data['previous_reading'] !== null && $data['billing_basis'] === 'OK') {
                $diff = $data['current_reading'] - $data['previous_reading'];
                if ($diff >= 0) {
                    $data['units_consumed'] = $diff;
                }
            } elseif (! empty($data['consumption_history'])) {
                $data['units_consumed'] = $data['consumption_history'][0]['units'];
            }
        }

        return $data;
    }

    /**
     * Sanitize and validate consumer name from PDF text.
     * Supports special characters: '.', '/', '-', '&', ''', '()', and Devanagari Unicode.
     */
    protected function sanitizeConsumerName(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // 1. Strip colons, hyphens, or leading/trailing whitespace and punctuation
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^[\s\:\-\.\,\/]+/u', '', $cleaned);
        $cleaned = preg_replace('/[\s\:\-\.\,\/]+$/u', '', $cleaned);
        $cleaned = trim(preg_replace('/\s+/u', ' ', $cleaned));

        if (mb_strlen($cleaned) < 2) {
            return null;
        }

        // 2. Reject obvious metadata noise or placeholders
        $upper = strtoupper($cleaned);
        if (in_array($upper, ['NA', 'N/A', 'NULL', 'NONE', 'NIL', '-', '--', 'NOT AVAILABLE', 'UNKNOWN', 'NO NAME'], true)) {
            return null;
        }

        // 3. Reject strings that start with MRU format (e.g. 0122 / CHETANA)
        if (preg_match('/^\d{3,4}\s*[\/\-]/', $cleaned)) {
            return null;
        }

        // 4. Reject phone/mobile/mask numbers, dates, or pure numeric strings
        if (preg_match('/^\d{2}\*{3,}|\d{10,}|\d{2}[\/\-]\d{2}[\/\-]\d{2,4}$/', $cleaned)) {
            return null;
        }

        // 5. Reject address headers or system labels
        $blacklist = [
            'VILL', 'VILLAGE', 'TOLA', 'PANCH', 'BLOCK', 'POST', 'DIST', 'DISTRICT', 'PIN',
            'CONSUMER', 'ELECTRICITY', 'NBPDCL', 'SBPDCL', 'METER', 'READING', 'JASPER',
            'KUTIR JYOTI', 'DS-I', 'DS-II', 'NDS-I', 'NDS-II', 'RURAL', 'URBAN',
            'उपभोक्ता', 'विवरणी', 'कनेक्शन', 'विधुत', 'विपत्र', 'श्रेणी', 'पिता का नाम',
        ];
        foreach ($blacklist as $token) {
            if (str_starts_with($upper, $token.' ') || str_starts_with($upper, $token.'-') || str_starts_with($upper, $token.':') || $upper === $token) {
                return null;
            }
        }

        // 6. Must contain at least one letter or Devanagari character
        if (! preg_match('/[A-Za-z\x{0900}-\x{097F}]/u', $cleaned)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Parse and normalize financial amounts, supporting negative signs,
     * parentheses (accounting format), CR (credit) suffixes, and advance balances.
     */
    protected function parseAmount(?string $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '' || $trimmed === '-' || $trimmed === '--') {
            return null;
        }

        // Check for negative indicators
        $isNegative = false;
        if (str_contains($trimmed, '-') || str_contains($trimmed, '−') || str_contains($trimmed, '–') || str_contains($trimmed, '—')) {
            $isNegative = true;
        }
        if (preg_match('/CR\b/i', $trimmed) || stripos($trimmed, 'Credit') !== false || stripos($trimmed, 'Advance') !== false) {
            $isNegative = true;
        }
        if (preg_match('/^\s*\(\s*-?[\d,\.]+\s*\)\s*$/', $trimmed)) {
            $isNegative = true;
        }

        // Extract numeric digits and optional decimal point
        if (preg_match('/([\d,]+\.?\d*)/', $trimmed, $m)) {
            $cleanedNum = str_replace(',', '', $m[1]);
            if ($cleanedNum === '' || $cleanedNum === '.') {
                return null;
            }

            $val = (float) $cleanedNum;
            $final = $isNegative ? (-1 * abs($val)) : $val;

            return $final == 0.0 ? 0.0 : $final;
        }

        return null;
    }

    /**
     * Normalize billing basis to canonical standard code (OK, LK, MD, PL, RN, EST, MB).
     */
    protected function normalizeBillingBasis(?string $raw): string
    {
        if ($raw === null) {
            return 'OK';
        }

        $upper = strtoupper(trim($raw));
        $clean = preg_replace('/[^A-Z]/', '', $upper);

        if (str_contains($clean, 'MD') || str_contains($clean, 'DEFECTIVE') || str_contains($clean, 'DEF')) {
            return 'MD';
        }
        if (str_contains($clean, 'LK') || str_contains($clean, 'LOCKED') || str_contains($clean, 'LOCK')) {
            return 'LK';
        }
        if (str_contains($clean, 'PL') || str_contains($clean, 'POWERLINE')) {
            return 'PL';
        }
        if (str_contains($clean, 'RN')) {
            return 'RN';
        }
        if (str_contains($clean, 'EST') || str_contains($clean, 'ESTIMATE')) {
            return 'EST';
        }
        if (str_contains($clean, 'MB') || str_contains($clean, 'BURNT')) {
            return 'MB';
        }
        if (str_contains($clean, 'AVG') || str_contains($clean, 'AVERAGE')) {
            return 'AVG';
        }
        if (str_contains($clean, 'OK') || str_contains($clean, 'NORMAL') || str_contains($clean, 'ACTUAL')) {
            return 'OK';
        }

        return ! empty($clean) ? substr($clean, 0, 4) : 'OK';
    }
}
