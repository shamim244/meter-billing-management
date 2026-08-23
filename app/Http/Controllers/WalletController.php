<?php

namespace App\Http\Controllers;

use App\Services\Wallet\WalletService;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Display the authenticated agent's wallet dashboard and transaction history.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $balance = $this->walletService->getBalance($user);

        $filters = [
            'type' => $request->query('type'),
            'source' => $request->query('source'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'search' => $request->query('search'),
        ];

        $transactions = $this->walletService->getTransactionHistory($user, $filters, 15);

        $stats = [
            'balance' => $balance,
            'total_credited' => (float) ($user->transactions()->where('type', 'deposit')->sum('amount') / 100),
            'total_debited' => (float) (abs($user->transactions()->where('type', 'withdraw')->sum('amount')) / 100),
            'transaction_count' => $user->transactions()->count(),
        ];

        return view('wallet.index', compact(
            'user',
            'balance',
            'transactions',
            'filters',
            'stats'
        ));
    }

    /**
     * Export the authenticated agent's transaction history to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $transactions = $user->transactions()->latest('id')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="wallet_ledger_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tx ID', 'Date & Time', 'Type', 'Amount (INR)', 'Source', 'Reference ID', 'Description']);

            foreach ($transactions as $tx) {
                $meta = (array) ($tx->meta ?? []);
                fputcsv($handle, [
                    $tx->id,
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->type === 'deposit' ? 'CREDIT' : 'DEBIT',
                    number_format((float) $tx->amountFloat, 2, '.', ''),
                    $meta['source'] ?? $tx->type,
                    $meta['reference_id'] ?? '-',
                    $meta['description'] ?? ($meta['reason'] ?? '-'),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
