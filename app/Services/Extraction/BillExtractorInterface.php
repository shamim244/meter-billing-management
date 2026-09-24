<?php

namespace App\Services\Extraction;

interface BillExtractorInterface
{
    /**
     * Extract structured bill fields from PDF text.
     *
     * @return array<string, mixed>
     */
    public function extract(string $text, ?string $pdfPath = null): array;

    /**
     * Determine if this extractor supports the given PDF text based on layout signatures.
     */
    public function supports(string $text): bool;

    /**
     * Unique identifier for this extraction engine.
     */
    public function getEngineName(): string;
}
