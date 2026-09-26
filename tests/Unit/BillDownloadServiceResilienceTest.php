<?php

namespace Tests\Unit;

use App\Services\BillDownloadService;
use App\Services\Extraction\JasperUnicodeExtractor;
use Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class BillDownloadServiceResilienceTest extends TestCase
{
    protected BillDownloadService $downloadService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->downloadService = new BillDownloadService;
    }

    public function test_validate_and_commit_pdf_accepts_valid_complete_pdf(): void
    {
        $tmpDir = storage_path('app/test_downloads');
        File::ensureDirectoryExists($tmpDir);
        $tmpFile = "{$tmpDir}/test_valid.tmp";
        $finalFile = "{$tmpDir}/test_valid.pdf";

        // Create a synthetic valid PDF with %PDF- header and %%EOF trailer >= 1024 bytes
        $dummyContent = "%PDF-1.4\n".str_repeat("0123456789ABCDEF\n", 70).'%%EOF';
        File::put($tmpFile, $dummyContent);

        $result = $this->downloadService->validateAndCommitPdf($tmpFile, $finalFile);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['error']);
        $this->assertFileExists($finalFile);
        $this->assertFileDoesNotExist($tmpFile);

        // Cleanup
        File::deleteDirectory($tmpDir);
    }

    public function test_validate_and_commit_pdf_rejects_truncated_pdf_missing_eof(): void
    {
        $tmpDir = storage_path('app/test_downloads');
        File::ensureDirectoryExists($tmpDir);
        $tmpFile = "{$tmpDir}/test_truncated.tmp";
        $finalFile = "{$tmpDir}/test_truncated.pdf";

        // Missing %%EOF at the end
        $truncatedContent = "%PDF-1.4\n".str_repeat("DUMMY DATA STREAM CONTENT\n", 50);
        File::put($tmpFile, $truncatedContent);

        $result = $this->downloadService->validateAndCommitPdf($tmpFile, $finalFile);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('stream truncated or incomplete', $result['error']);
        $this->assertFileDoesNotExist($finalFile);
        $this->assertFileDoesNotExist($tmpFile);

        // Cleanup
        File::deleteDirectory($tmpDir);
    }

    public function test_validate_and_commit_pdf_strips_chunked_proxy_prefixes(): void
    {
        $tmpDir = storage_path('app/test_downloads');
        File::ensureDirectoryExists($tmpDir);
        $tmpFile = "{$tmpDir}/test_chunked.tmp";
        $finalFile = "{$tmpDir}/test_chunked.pdf";

        // Prepend chunked transfer header junk before %PDF-
        $prefixedContent = "1f4\r\n"."%PDF-1.5\n".str_repeat("VALID_PDF_STREAM_BYTES\n", 60).'%%EOF';
        File::put($tmpFile, $prefixedContent);

        $result = $this->downloadService->validateAndCommitPdf($tmpFile, $finalFile);

        $this->assertTrue($result['valid']);
        $this->assertFileExists($finalFile);
        $cleanData = File::get($finalFile);
        $this->assertStringStartsWith('%PDF-', $cleanData);

        // Cleanup
        File::deleteDirectory($tmpDir);
    }

    public function test_classify_non_pdf_response_identifies_upstream_json_business_errors(): void
    {
        $jsonPayload = json_encode([
            'status' => 'FAILURE',
            'message' => 'Bill not generated for consumer 10230041576 in period 09/2026',
        ]);

        $classified = $this->downloadService->classifyNonPdfResponse($jsonPayload, strlen($jsonPayload), 200);

        $this->assertEquals('Upstream Message: Bill not generated for consumer 10230041576 in period 09/2026', $classified);
    }

    public function test_classify_non_pdf_response_identifies_html_gateway_errors(): void
    {
        $htmlPayload = '<!DOCTYPE html><html><head><title>502 Bad Gateway</title></head><body><h1>Bad Gateway</h1></body></html>';

        $classified = $this->downloadService->classifyNonPdfResponse($htmlPayload, strlen($htmlPayload), 502);

        $this->assertEquals('Upstream HTML Error: 502 Bad Gateway', $classified);
    }

    public function test_cryptojs_aes_encryption_produces_salted_envelope(): void
    {
        $encrypted = $this->downloadService->encryptCryptoJS('{"test":"payload"}', 'fgwebcp@2020');

        $raw = base64_decode($encrypted);
        $this->assertStringStartsWith('Salted__', $raw);
        $this->assertGreaterThan(16, strlen($raw));
    }

    public function test_download_single_rejects_empty_or_invalid_ca(): void
    {
        $result = $this->downloadService->downloadSingle('!@#$%', 9, 2026);

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid or empty CA Number provided.', $result['error']);
    }

    public function test_jasper_unicode_extractor_parses_extended_attributes_from_postpaid_fixture(): void
    {
        $fixturePath = base_path('apk/bill-pdf/LIVE_DOWNLOAD_SUCCESS.pdf');
        if (! File::exists($fixturePath)) {
            $this->markTestSkipped('LIVE_DOWNLOAD_SUCCESS.pdf fixture not available.');
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($fixturePath);
        $text = $pdf->getText();

        $extractor = new JasperUnicodeExtractor;
        $data = $extractor->extract($text);

        $this->assertEquals('jasper_unicode', $data['engine']);
        $this->assertEquals('BOBI SAFIKUL', $data['consumer_name']);
        $this->assertEquals('20260910230041576', $data['bill_number']);
        $this->assertEquals('0.25 KW', $data['sanctioned_load']);
        $this->assertEquals(1, $data['phase']);
        $this->assertEquals('3805315', $data['meter_no']);
        $this->assertEquals(1129, $data['current_reading']);
        $this->assertEquals(1080, $data['previous_reading']);
        $this->assertEquals(49, $data['units_consumed']);
        $this->assertEquals(871.00, $data['total_amount']);
        $this->assertEquals(363.58, $data['energy_charges']);
        $this->assertEquals(21.70, $data['fixed_charges']);
        $this->assertEquals(21.81, $data['electricity_duty']);
        $this->assertEquals(-407.09, $data['government_subsidy']);
        $this->assertEquals(859.75, $data['arrears']);
        $this->assertEquals('OK', $data['billing_basis']);
        $this->assertEquals('0122/CHETANA', $data['mru']);
        $this->assertCount(12, $data['consumption_history']);
    }

    public function test_jasper_unicode_extractor_parses_smart_prepaid_fixture(): void
    {
        $fixturePath = base_path('apk/bill-pdf/bill_10230014993_09_2026.pdf');
        if (! File::exists($fixturePath)) {
            $this->markTestSkipped('bill_10230014993_09_2026.pdf fixture not available.');
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($fixturePath);
        $text = $pdf->getText();

        $extractor = new JasperUnicodeExtractor;
        $data = $extractor->extract($text);

        $this->assertEquals('jasper_unicode', $data['engine']);
        $this->assertEquals('MUJIBUR RAHMAN', $data['consumer_name']);
        $this->assertEquals('S/O RYAZUDDIN', $data['father_name']);
        $this->assertEquals('20260910230014993', $data['bill_number']);
        $this->assertEquals('1 KW', $data['sanctioned_load']);
        $this->assertEquals(1, $data['phase']);
        $this->assertEquals('KT098729', $data['meter_no']);
        $this->assertEquals(350, $data['current_reading']);
        $this->assertEquals(184, $data['units_consumed']);
        $this->assertEquals(159.49, $data['total_amount']);
        $this->assertEquals('DS-I (D)', $data['tariff_category']);
        $this->assertEquals('OK', $data['billing_basis']);
        $this->assertEquals('0142/SAMAIKOL', $data['mru']);
        $this->assertCount(12, $data['consumption_history']);
    }

    public function test_download_single_resolves_previous_month_baseline_when_target_cycle_unbilled(): void
    {
        // Target month 10/2026 has no bill, but 09/2026 fixture exists in apk/bill-pdf
        $result = $this->downloadService->downloadSingle('10230041576', 10, 2026, 'auto', 6);

        $this->assertTrue($result['success']);
        $this->assertEquals('09/2026', $result['resolved_cycle']);
        $this->assertEquals(9, $result['resolved_month']);
        $this->assertEquals(2026, $result['resolved_year']);
        $this->assertEquals(1, $result['lookback_steps']);
        $this->assertGreaterThan(1000, $result['pdf_bytes']);
    }

    public function test_download_single_reports_lookback_exhaustion_when_unbilled(): void
    {
        $result = $this->downloadService->downloadSingle('99999999999', 10, 2026, 'wss', 2);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('preceding 2 months', $result['error']);
    }
}
