<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Extraction\BillExtractionManager;
use App\Services\Extraction\JasperUnicodeExtractor;
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
        $this->manager = app(BillExtractionManager::class);
        $this->parser = new Parser;
        SystemSetting::clearRuntimeCache();
    }

    public function test_jasper_unicode_extraction_on_live_2026_postpaid_pdf(): void
    {
        $pdfPath = base_path('apk/bill-pdf/LIVE_DOWNLOAD_SUCCESS.pdf');
        $this->assertFileExists($pdfPath);

        $pdf = @$this->parser->parseFile($pdfPath);
        $text = @$pdf->getText();

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

        $pdf = @$this->parser->parseFile($pdfPath);
        $text = @$pdf->getText();

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

        $pdf = @$this->parser->parseFile($pdfPath);
        $text = @$pdf->getText();

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
        $pdf = @$this->parser->parseFile($pdfPath);
        $text = @$pdf->getText();

        // Even though legacy was forced, primary legacy extractor fails to find key fields on modern PDF,
        // and manager should gracefully fall back to JasperUnicodeExtractor
        $data = $this->manager->extract($text, $pdfPath);

        $this->assertEquals('BOBI SAFIKUL', $data['consumer_name']);
        $this->assertStringContainsString('fallback', $data['extractor_used']);

        SystemSetting::set('nbpdcl_extraction_engine', 'auto');
    }

    public function test_jasper_extractor_supports_negative_amounts_and_credit_balances(): void
    {
        $extractor = app(JasperUnicodeExtractor::class);

        // Simulated postpaid text with negative credit balance before CA number
        $sampleNegative1 = "विधुत बिल\n-150.00\n-150.00\n-150.00\n: 10230041576\n";
        $data1 = $extractor->extract($sampleNegative1);
        $this->assertEquals(-150.00, $data1['total_amount']);

        // Simulated credit with CR suffix
        $sampleNegativeCR = "विधुत बिल\n250.50 CR\n250.50 CR\n250.50 CR\n: 10230041576\n";
        $dataCR = $extractor->extract($sampleNegativeCR);
        $this->assertEquals(-250.50, $dataCR['total_amount']);

        // Simulated credit with accounting parentheses
        $sampleNegativeParen = "विधुत बिल\n(500.00)\n(500.00)\n(500.00)\n: 10230041576\n";
        $dataParen = $extractor->extract($sampleNegativeParen);
        $this->assertEquals(-500.00, $dataParen['total_amount']);

        // Simulated credit with (-) format
        $sampleNegativeDashParen = "विधुत बिल\n(-) 320.00\n(-) 320.00\n(-) 320.00\n: 10230041576\n";
        $dataDashParen = $extractor->extract($sampleNegativeDashParen);
        $this->assertEquals(-320.00, $dataDashParen['total_amount']);

        // Prepaid bill net balance under meter reading section
        $samplePrepaidNegative = "स्मार्ट प्रीपेड विधुत बिल\nमीटर पठन विवरणी\n-283.29\nकुल खपत\n";
        $dataPrepaid = $extractor->extract($samplePrepaidNegative);
        $this->assertEquals(-283.29, $dataPrepaid['total_amount']);
    }

    public function test_jasper_extractor_supports_names_with_special_characters(): void
    {
        $extractor = app(JasperUnicodeExtractor::class);

        // 1. Initials / Dots
        $textDot = ": NA\n: MD. SAFIKUL\n89*****724\n";
        $dataDot = $extractor->extract($textDot);
        $this->assertEquals('MD. SAFIKUL', $dataDot['consumer_name']);

        // 2. Relation / Slashes
        $textSlash = ": NA\n: SITA DEVI W/O RAMESH\n89*****724\n";
        $dataSlash = $extractor->extract($textSlash);
        $this->assertEquals('SITA DEVI W/O RAMESH', $dataSlash['consumer_name']);

        // 3. Commercial / Ampersand
        $textAmp = ": NA\n: M/S RAM & SONS\n89*****724\n";
        $dataAmp = $extractor->extract($textAmp);
        $this->assertEquals('M/S RAM & SONS', $dataAmp['consumer_name']);

        // 4. Hyphenated Name
        $textHyphen = ": NA\n: AL-AMIN\n89*****724\n";
        $dataHyphen = $extractor->extract($textHyphen);
        $this->assertEquals('AL-AMIN', $dataHyphen['consumer_name']);

        // 5. Apostrophe Name
        $textApos = ": NA\n: D'SOUZA\n89*****724\n";
        $dataApos = $extractor->extract($textApos);
        $this->assertEquals("D'SOUZA", $dataApos['consumer_name']);

        // 6. Parentheses / Description
        $textParen = ": NA\n: RAMESH PRASAD (SHOP)\n89*****724\n";
        $dataParen = $extractor->extract($textParen);
        $this->assertEquals('RAMESH PRASAD (SHOP)', $dataParen['consumer_name']);

        // 7. Unicode Devanagari Name
        $textHindi = "नाम : मुजीबुर रहमान\n";
        $dataHindi = $extractor->extract($textHindi);
        $this->assertEquals('मुजीबुर रहमान', $dataHindi['consumer_name']);
    }

    public function test_jasper_extractor_detects_lk_md_ok_billing_basis_correctly(): void
    {
        $extractor = app(JasperUnicodeExtractor::class);

        // 1. Locked (LK)
        $textLK1 = ": Kutir Jyoti Rural\n: LOCKED (LK)\n";
        $this->assertEquals('LK', $extractor->extract($textLK1)['billing_basis']);

        $textLK2 = ": Kutir Jyoti Rural\n: DOOR LOCKED (LK)\n";
        $this->assertEquals('LK', $extractor->extract($textLK2)['billing_basis']);

        $textLK3 = "बिल का आधार\n: LK\n";
        $this->assertEquals('LK', $extractor->extract($textLK3)['billing_basis']);

        // 2. Defective (MD)
        $textMD1 = ": Kutir Jyoti Rural\n: DEFECTIVE (MD)\n";
        $this->assertEquals('MD', $extractor->extract($textMD1)['billing_basis']);

        $textMD2 = ": Kutir Jyoti Rural\n: METER DEFECTIVE (MD)\n";
        $this->assertEquals('MD', $extractor->extract($textMD2)['billing_basis']);

        $textMD3 = "बिल का आधार\n: MD\n";
        $this->assertEquals('MD', $extractor->extract($textMD3)['billing_basis']);

        // 3. Power Line (PL)
        $textPL = ": Kutir Jyoti Rural\n: POWER LINE (PL)\n";
        $this->assertEquals('PL', $extractor->extract($textPL)['billing_basis']);

        // 4. Normal / Actual (OK)
        $textOK1 = ": Kutir Jyoti Rural\n: ACTUAL (OK)\n";
        $this->assertEquals('OK', $extractor->extract($textOK1)['billing_basis']);

        $textOK2 = ": Kutir Jyoti Rural\n: NORMAL (OK)\n";
        $this->assertEquals('OK', $extractor->extract($textOK2)['billing_basis']);

        // 5. Fallback from consumption history
        $textHistory = "Nov-2025 57 (LK)\n";
        $dataHist = $extractor->extract($textHistory);
        $this->assertEquals('LK', $dataHist['billing_basis']);
    }

    public function test_jasper_extractor_extracts_negative_amount_from_live_prepaid_pdf(): void
    {
        $pdfPath = base_path('apk/bill-pdf/bill_10230014993_09_2026.pdf');
        $this->assertFileExists($pdfPath);

        $pdf = @$this->parser->parseFile($pdfPath);
        $text = @$pdf->getText();

        $data = $this->manager->extract($text, $pdfPath);

        $this->assertEquals('jasper_unicode', $data['detected_format']);
        $this->assertEquals('MUJIBUR RAHMAN', $data['consumer_name']);
        // Verify current monthly bill amount was correctly captured
        $this->assertEquals(159.49, $data['total_amount']);
    }
}
