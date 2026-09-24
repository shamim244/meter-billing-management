<?php

namespace App\Services\Extraction;

class BillExtractionManager
{
    protected array $extractors;

    public function __construct(
        protected JasperUnicodeExtractor $jasperExtractor,
        protected LegacyKrutiDevExtractor $legacyExtractor
    ) {
        $this->extractors = [
            'jasper_unicode' => $this->jasperExtractor,
            'legacy_krutidev' => $this->legacyExtractor,
        ];
    }

    /**
     * Algorithmically detect the PDF layout format.
     */
    public function detectFormat(string $text): string
    {
        // 1. Check for modern JasperReports / Unicode Devanagari markers
        if ($this->jasperExtractor->supports($text)) {
            return 'jasper_unicode';
        }

        // 2. Check for legacy Kruti Dev / iText markers
        if ($this->legacyExtractor->supports($text)) {
            return 'legacy_krutidev';
        }

        // 3. Heuristic analysis if ambiguous
        $devanagariCount = preg_match_all('/[\x{0900}-\x{097F}]/u', $text);
        if ($devanagariCount > 20) {
            return 'jasper_unicode';
        }

        return 'legacy_krutidev';
    }

    /**
     * Get extractor by engine name.
     */
    public function getExtractor(string $format): BillExtractorInterface
    {
        return $this->extractors[$format] ?? $this->jasperExtractor;
    }

    /**
     * Extract data using the algorithmically detected engine with automatic fallback.
     */
    public function extract(string $text, ?string $pdfPath = null): array
    {
        $settingMode = \App\Models\SystemSetting::get('nbpdcl_extraction_engine', config('nbpdcl.extraction_engine', 'auto'));

        if (in_array($settingMode, ['jasper_unicode', 'legacy_krutidev'], true)) {
            $detectedFormat = $settingMode;
        } else {
            $detectedFormat = $this->detectFormat($text);
        }

        $primaryExtractor = $this->getExtractor($detectedFormat);

        $data = $primaryExtractor->extract($text, $pdfPath);
        $data['detected_format'] = $detectedFormat;
        $data['extractor_used'] = $primaryExtractor->getEngineName();

        // Graceful Fallback: If primary engine failed to extract any identifying data, try alternative engine
        $hasKeyFields = ! empty($data['consumer_name']) || ! empty($data['meter_no']) || ! empty($data['bill_month']);
        if (! $hasKeyFields) {
            $fallbackFormat = ($detectedFormat === 'jasper_unicode') ? 'legacy_krutidev' : 'jasper_unicode';
            $fallbackExtractor = $this->getExtractor($fallbackFormat);
            $fallbackData = $fallbackExtractor->extract($text, $pdfPath);

            if (! empty($fallbackData['consumer_name']) || ! empty($fallbackData['meter_no'])) {
                $fallbackData['detected_format'] = $detectedFormat;
                $fallbackData['extractor_used'] = $fallbackExtractor->getEngineName().' (fallback)';

                return $fallbackData;
            }
        }

        return $data;
    }
}
