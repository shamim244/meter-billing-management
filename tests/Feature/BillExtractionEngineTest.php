<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Extraction\BillExtractionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class BillExtractionEngineTest extends TestCase
{
    use RefreshDatabase;

    protected BillExtractionManager $manager;

    protected Parser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        if (! class_exists(Parser::class) && file_exists(base_path('../vendor/autoload.php'))) {
            require_once base_path('../vendor/autoload.php');
        }
        $this->manager = app(BillExtractionManager::class);
        $this->parser = new Parser;
        SystemSetting::clearRuntimeCache();
    }

    public function test_jasper_unicode_extraction_on_live_2026_postpaid_pdf(): void
    {
        $pdfPath = base_path('apk/bill-pdf/LIVE_DOWNLOAD_SUCCESS.pdf');
        $this->assertFileExists($pdfPath);

        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $format = $this->manager->detectFormat($text);
        $this->assertEquals('jasper_unicode', $format);

        $data = $this->manager->extract($text, $pdfPath);

        $this->assertEquals('jasper_unicode', $data['detected_format']);
        $this->assertStringContainsString('jasper_unicode', $data['extractor_used']);
        $this->assertEquals('BOBI SAFIKUL', $data['consumer_name']);
        $this->assertNotEmpty($data['bill_month']);
        $this->assertEquals('OK', $data['billing_basis']);
        $this->assertNotEmpty($data['meter_no']);
    }

    public function test_jasper_unicode_extraction_on_live_2026_smart_prepaid_pdf(): void
    {
        $pdfPath = base_path('apk/bill-pdf/bill_10230014993_09_2026.pdf');
        $this->assertFileExists($pdfPath);

        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $format = $this->manager->detectFormat($text);
        $this->assertEquals('jasper_unicode', $format);

        $data = $this->manager->extract($text, $pdfPath);

        $this->assertEquals('jasper_unicode', $data['detected_format']);
        $this->assertEquals('MUJIBUR RAHMAN', $data['consumer_name']);
        $this->assertStringStartsWith('KT', $data['meter_no']);
        $this->assertEquals('OK', $data['billing_basis']);
    }

    public function test_legacy_krutidev_extraction_on_older_direct_url_pdf(): void
    {
        $pdfPath = base_path('apk/bill-pdf/from-direct-url.pdf');
        $this->assertFileExists($pdfPath);

        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();

        $format = $this->manager->detectFormat($text);
        $this->assertEquals('legacy_krutidev', $format);

        $data = $this->manager->extract($text, $pdfPath);

        $this->assertEquals('legacy_krutidev', $data['detected_format']);
        $this->assertStringContainsString('legacy_krutidev', $data['extractor_used']);
        $this->assertEquals('MUJIBUR RAHMAN', $data['consumer_name']);
        $this->assertEquals('OK', $data['billing_basis']);
    }

    public function test_fallback_between_engines_when_mismatched(): void
    {
        // Force legacy engine via system setting for a modern Unicode PDF
        SystemSetting::set('nbpdcl_extraction_engine', 'legacy_krutidev');

        $pdfPath = base_path('apk/bill-pdf/LIVE_DOWNLOAD_SUCCESS.pdf');
        $pdf = $this->parser->parseFile($pdfPath);
        $text = $pdf->getText();

        // Even though legacy was forced, primary legacy extractor fails to find key fields on modern PDF,
        // and manager should gracefully fall back to JasperUnicodeExtractor
        $data = $this->manager->extract($text, $pdfPath);

        $this->assertEquals('BOBI SAFIKUL', $data['consumer_name']);
        $this->assertStringContainsString('fallback', $data['extractor_used']);

        SystemSetting::set('nbpdcl_extraction_engine', 'auto');
    }
}
