<?php

namespace App\Services\Backup;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class AgentWorkspaceExportService
{
    /**
     * Create a complete, tenant-isolated workspace export ZIP archive for a specific agent.
     *
     * @param User $agent The billing agent user
     * @param string $outputPath Path to save the final ZIP
     * @return array Summary of exported records and files
     */
    public function export(User $agent, string $outputPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create agent export zip archive at: {$outputPath}");
        }

        $userId = $agent->id;

        // 1. MRUs CSV
        $mrus = Mru::where('user_id', $userId)->get();
        $mruCsv = fopen('php://temp', 'r+');
        fputcsv($mruCsv, ['ID', 'MRU Code', 'Description', 'Current Cycle', 'Total Consumers', 'Lock Status', 'Created At']);
        foreach ($mrus as $mru) {
            fputcsv($mruCsv, [
                $mru->id,
                $mru->code,
                $mru->name ?? $mru->description,
                $mru->current_cycle ?? '',
                $mru->total_consumers ?? 0,
                $mru->is_locked ? 'Locked' : 'Unlocked',
                $mru->created_at?->toDateTimeString(),
            ]);
        }
        rewind($mruCsv);
        $zip->addFromString('ledger/01_mrus_master.csv', stream_get_contents($mruCsv));
        fclose($mruCsv);

        // 2. Consumers CSV
        $consumers = ConsumerAccount::where('user_id', $userId)->orderBy('mru_code')->orderBy('sequence_no')->get();
        $conCsv = fopen('php://temp', 'r+');
        fputcsv($conCsv, ['ID', 'MRU Code', 'CA Number', 'Consumer Name', 'Father Name', 'Address', 'Tariff Category', 'Meter Number', 'Phone', 'Sequence', 'Created At']);
        foreach ($consumers as $c) {
            fputcsv($conCsv, [
                $c->id,
                $c->mru_code,
                $c->ca_number,
                $c->consumer_name ?? $c->name,
                $c->father_name ?? '',
                $c->address ?? '',
                $c->tariff_category ?? $c->tariff,
                $c->meter_number ?? $c->meter_no,
                $c->mobile_number ?? $c->phone,
                $c->sequence_no ?? 0,
                $c->created_at?->toDateTimeString(),
            ]);
        }
        rewind($conCsv);
        $zip->addFromString('ledger/02_consumers_registry.csv', stream_get_contents($conCsv));
        fclose($conCsv);

        // 3. Bill Records & 4-Box Reading Ledger CSV
        $bills = BillRecord::with('mru')->where('user_id', $userId)->orderBy('billing_year', 'desc')->orderBy('billing_month', 'desc')->get();
        $billCsv = fopen('php://temp', 'r+');
        fputcsv($billCsv, [
            'ID', 'MRU Code', 'Billing Month', 'Billing Year', 'Label', 'CA Number', 'Consumer Name',
            'Box 1 Working Reading', 'Box 2 DB Previous', 'Box 3 Smart Avg Units', 'Box 4 Official PDF Reading',
            'Billing Basis', 'Tariff', 'Due Amount', 'Review Status', 'Review Tag', 'Remark', 'Has PDF File', 'Created At'
        ]);

        $pdfCount = 0;
        foreach ($bills as $b) {
            $hasPdf = ! empty($b->pdf_path) && file_exists(storage_path('app/' . $b->pdf_path));
            $mruCode = $b->mru?->code ?? $b->mru_code ?? 'MRU_DEFAULT';

            fputcsv($billCsv, [
                $b->id,
                $mruCode,
                $b->billing_month,
                $b->billing_year,
                $b->bill_month_label ?? '',
                $b->ca_number,
                $b->consumer_name,
                $b->working_reading,
                $b->previous_reading,
                $b->units_consumed ?? $b->units,
                $b->current_reading,
                $b->billing_basis,
                $b->tariff_category ?? $b->tariff,
                $b->total_amount ?? $b->net_amount ?? $b->amount,
                $b->review_status,
                $b->review_tag,
                $b->remark,
                $hasPdf ? 'YES' : 'NO',
                $b->created_at?->toDateTimeString(),
            ]);

            // Add PDF file into ZIP if present
            if ($hasPdf) {
                $absolutePdf = storage_path('app/' . $b->pdf_path);
                $zipRelativePath = "bills/{$mruCode}/{$b->billing_year}-{$b->billing_month}/{$b->ca_number}.pdf";
                $zip->addFile($absolutePdf, $zipRelativePath);
                $pdfCount++;
            }
        }
        rewind($billCsv);
        $zip->addFromString('ledger/03_monthly_reading_ledger.csv', stream_get_contents($billCsv));
        fclose($billCsv);

        // 4. Wallet Statement CSV
        $walletCsv = fopen('php://temp', 'r+');
        fputcsv($walletCsv, ['ID', 'Type', 'Amount (₹)', 'Confirmed', 'Description', 'Timestamp']);
        try {
            if ($agent->wallet) {
                foreach ($agent->wallet->transactions()->latest()->get() as $tx) {
                    fputcsv($walletCsv, [
                        $tx->id,
                        $tx->type,
                        $tx->amount / 100, // standard decimal conversion
                        $tx->confirmed ? 'YES' : 'NO',
                        $tx->meta['description'] ?? $tx->meta['note'] ?? 'Wallet Transaction',
                        $tx->created_at?->toDateTimeString(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback if wallet package not attached
        }
        rewind($walletCsv);
        $zip->addFromString('ledger/04_wallet_statement.csv', stream_get_contents($walletCsv));
        fclose($walletCsv);

        // 5. Workspace Export Manifest
        $manifest = [
            'platform' => 'NBPDCL Electricity Billing SaaS Pro',
            'export_type' => 'AGENT_WORKSPACE_DATA_PORTABILITY',
            'exported_at' => now()->toIso8601String(),
            'agent' => [
                'id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'phone' => $agent->phone,
            ],
            'statistics' => [
                'total_mrus' => $mrus->count(),
                'total_consumers' => $consumers->count(),
                'total_bill_records' => $bills->count(),
                'total_pdfs_bundled' => $pdfCount,
            ],
        ];

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
        $zip->close();

        return $manifest;
    }
}
