<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WalletAdminAdjustmentType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Wallet\WalletService;
use Bavix\Wallet\Models\Transaction;
use Bavix\Wallet\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminWalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Display all agent wallets with balances and status overview.
     */
    public function index(Request $request): View
    {
        $query = User::role('user')->latest('id');

        if ($request->filled('search')) {
            $search = '%' . $request->query('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('phone', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            if ($request->query('status') === 'frozen') {
                $query->where('is_wallet_frozen', true);
            } elseif ($request->query('status') === 'active') {
                $query->where('is_wallet_frozen', false);
            }
        }

        $users = $query->paginate(20)->withQueryString();

        $stats = [
            'total_wallets' => User::role('user')->count(),
            'total_balance' => (float) (Wallet::sum('balance') / 100),
            'frozen_wallets' => User::role('user')->where('is_wallet_frozen', true)->count(),
            'total_adjustments' => Transaction::where('meta->source', 'admin_adjustment')->count(),
        ];

        return view('admin.wallets.index', compact('users', 'stats'));
    }

    /**
     * Show single Agent's Wallet Detail & Adjustment Console.
     * (Reachable in <= 2 clicks from user view).
     */
    public function show(User $user, Request $request): View
    {
        $balance = $this->walletService->getBalance($user);

        $filters = [
            'type' => $request->query('type'),
            'source' => $request->query('source'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'search' => $request->query('search'),
        ];

        $transactions = $this->walletService->getTransactionHistory($user, $filters, 20);

        // Fetch adjustments from bavix transactions where source = 'admin_adjustment'
        $adjustments = $user->transactions()
            ->where('meta->source', 'admin_adjustment')
            ->latest('id')
            ->limit(10)
            ->get();

        $stats = [
            'balance' => $balance,
            'total_credited' => (float) ($user->transactions()->where('type', 'deposit')->sum('amount') / 100),
            'total_debited' => (float) (abs($user->transactions()->where('type', 'withdraw')->sum('amount')) / 100),
            'transaction_count' => $user->transactions()->count(),
            'adjustment_count' => $user->transactions()->where('meta->source', 'admin_adjustment')->count(),
        ];

        return view('admin.wallets.show', compact(
            'user',
            'balance',
            'transactions',
            'adjustments',
            'filters',
            'stats'
        ));
    }

    /**
     * Execute Administrative Wallet Balance Adjustment (+ Add Balance or - Deduct Balance).
     * The ONLY method permitted to push wallet balance negative when deducting.
     */
    public function adjust(User $user, Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|string|in:add,deduct',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|min:3|max:1000',
        ]);

        $admin = $request->user();
        $type = WalletAdminAdjustmentType::from($request->input('type'));
        $amount = (float) $request->input('amount');
        $reason = trim($request->input('reason'));

        try {
            $this->walletService->adminAdjust(
                user: $user,
                admin: $admin,
                type: $type,
                amount: $amount,
                reason: $reason
            );

            $newBalance = $this->walletService->getBalance($user);
            $actionWord = $type === WalletAdminAdjustmentType::ADD ? 'credited to' : 'deducted from';
            return redirect()->route('admin.wallets.show', $user->id)->with(
                'success',
                "Successfully {$actionWord} {$user->name}'s wallet by ₹" . number_format($amount, 2) . ". New balance: ₹" . number_format($newBalance, 2)
            );
        } catch (\Throwable $e) {
            return redirect()->route('admin.wallets.show', $user->id)->withInput()->with('error', "Adjustment failed: " . $e->getMessage());
        }
    }

    /**
     * Toggle Freeze / Unfreeze status for an agent's wallet.
     */
    public function toggleFreeze(User $user, Request $request): RedirectResponse
    {
        $admin = $request->user();

        if ($user->isWalletFrozen()) {
            $this->walletService->unfreeze($user, $admin, $request->input('reason', 'Unfrozen by Admin.'));
            return redirect()->route('admin.wallets.show', $user->id)->with('success', "Wallet for {$user->name} has been unfrozen successfully.");
        } else {
            $request->validate([
                'reason' => 'required|string|min:3|max:1000',
            ]);
            $this->walletService->freeze($user, $admin, trim($request->input('reason')));
            return redirect()->route('admin.wallets.show', $user->id)->with('success', "Wallet for {$user->name} has been frozen. Debits are now blocked.");
        }
    }

    /**
     * Export single agent's transaction history to CSV.
     */
    public function export(User $user): StreamedResponse
    {
        $transactions = $user->transactions()->latest('id')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="agent_' . $user->id . '_wallet_ledger_' . date('Y-m-d_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($user, $transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tx ID', 'Agent Name', 'Agent Email', 'Date & Time', 'Type', 'Amount (INR)', 'Source', 'Reference ID', 'Description']);

            foreach ($transactions as $tx) {
                $meta = (array) ($tx->meta ?? []);
                fputcsv($handle, [
                    $tx->id,
                    $user->name,
                    $user->email,
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
