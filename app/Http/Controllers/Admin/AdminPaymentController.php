<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentAuditAction;
use App\Enums\PaymentMode;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailedEvent;
use App\Events\PaymentSuccessEvent;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentAuditLog;
use App\Models\User;
use App\Services\Payment\OnlinePaymentGatewayService;
use App\Services\Payment\PaymentSettingsService;
use App\Services\Payment\PaymentVerificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    public function __construct(
        protected PaymentSettingsService $settingsService,
        protected PaymentVerificationService $verificationService,
        protected OnlinePaymentGatewayService $onlinePgService
    ) {}

    /**
     * Display a listing of all payments with search & status filters (Master Ledger).
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status', 'all');
        $modeFilter = $request->query('mode');
        $purposeFilter = $request->query('purpose');
        $search = $request->query('search');

        $query = Payment::withoutUserScope()
            ->with(['user', 'verifiedBy', 'auditLogs.admin'])
            ->latest('id');

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($modeFilter) {
            $query->where('mode', $modeFilter);
        }

        if ($purposeFilter) {
            $query->where('purpose', $purposeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('utr_number', 'like', "%{$search}%")
                  ->orWhere('bank_reference', 'like', "%{$search}%")
                  ->orWhere('gateway_order_id', 'like', "%{$search}%")
                  ->orWhere('gateway_payment_id', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->paginate(20)->withQueryString();

        // Calculate counts for quick queue tabs
        $pendingCount = Payment::withoutUserScope()->where('status', PaymentStatus::PENDING_VERIFICATION->value)->count();
        $successCount = Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->count();
        $rejectedCount = Payment::withoutUserScope()->where('status', PaymentStatus::REJECTED->value)->count();
        $totalCollected = Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->sum('amount');

        return view('admin.payments.index', compact(
            'payments',
            'statusFilter',
            'modeFilter',
            'purposeFilter',
            'search',
            'pendingCount',
            'successCount',
            'rejectedCount',
            'totalCollected'
        ));
    }

    /**
     * Dedicated Manual Payment Verification Queue (Pending UPI & Bank Transfers).
     */
    public function manual(Request $request): View
    {
        $modeFilter = $request->query('mode'); // 'manual_upi' or 'bank_transfer'
        $search = $request->query('search');

        $query = Payment::withoutUserScope()
            ->with(['user', 'auditLogs.admin'])
            ->where('status', PaymentStatus::PENDING_VERIFICATION->value)
            ->whereIn('mode', [PaymentMode::MANUAL_UPI->value, PaymentMode::BANK_TRANSFER->value])
            ->latest('id');

        if ($modeFilter) {
            $query->where('mode', $modeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('utr_number', 'like', "%{$search}%")
                  ->orWhere('bank_reference', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($t) use ($search) {
                      $t->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $pendingPayments = $query->paginate(15)->withQueryString();

        $upiPendingCount = Payment::withoutUserScope()
            ->where('status', PaymentStatus::PENDING_VERIFICATION->value)
            ->where('mode', PaymentMode::MANUAL_UPI->value)
            ->count();

        $bankPendingCount = Payment::withoutUserScope()
            ->where('status', PaymentStatus::PENDING_VERIFICATION->value)
            ->where('mode', PaymentMode::BANK_TRANSFER->value)
            ->count();

        $totalPendingAmount = Payment::withoutUserScope()
            ->where('status', PaymentStatus::PENDING_VERIFICATION->value)
            ->sum('amount');

        return view('admin.payments.manual', compact(
            'pendingPayments',
            'modeFilter',
            'search',
            'upiPendingCount',
            'bankPendingCount',
            'totalPendingAmount'
        ));
    }

    /**
     * Financial Analytics & Revenue Performance Dashboard.
     */
    public function analytics(Request $request): View
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfToday = $now->copy()->startOfDay();

        // 1. Core KPIs
        $totalCollected = (float) Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->sum('amount');
        $monthCollected = (float) Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->where('created_at', '>=', $startOfMonth)->sum('amount');
        $todayCollected = (float) Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->where('created_at', '>=', $startOfToday)->sum('amount');

        $totalTransactions = Payment::withoutUserScope()->count();
        $successCount = Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->count();
        $rejectedCount = Payment::withoutUserScope()->where('status', PaymentStatus::REJECTED->value)->count();
        $pendingCount = Payment::withoutUserScope()->where('status', PaymentStatus::PENDING_VERIFICATION->value)->count();
        $failedCount = Payment::withoutUserScope()->where('status', PaymentStatus::FAILED->value)->count();

        $successRate = $totalTransactions > 0 ? round(($successCount / $totalTransactions) * 100, 1) : 100;
        $avgTicketSize = $successCount > 0 ? round($totalCollected / $successCount, 2) : 0.0;

        // 2. Mode Distribution
        $modeBreakdown = [
            'pg' => [
                'count' => Payment::withoutUserScope()->where('mode', PaymentMode::PG->value)->where('status', PaymentStatus::SUCCESS->value)->count(),
                'amount' => (float) Payment::withoutUserScope()->where('mode', PaymentMode::PG->value)->where('status', PaymentStatus::SUCCESS->value)->sum('amount'),
            ],
            'manual_upi' => [
                'count' => Payment::withoutUserScope()->where('mode', PaymentMode::MANUAL_UPI->value)->where('status', PaymentStatus::SUCCESS->value)->count(),
                'amount' => (float) Payment::withoutUserScope()->where('mode', PaymentMode::MANUAL_UPI->value)->where('status', PaymentStatus::SUCCESS->value)->sum('amount'),
            ],
            'bank_transfer' => [
                'count' => Payment::withoutUserScope()->where('mode', PaymentMode::BANK_TRANSFER->value)->where('status', PaymentStatus::SUCCESS->value)->count(),
                'amount' => (float) Payment::withoutUserScope()->where('mode', PaymentMode::BANK_TRANSFER->value)->where('status', PaymentStatus::SUCCESS->value)->sum('amount'),
            ],
        ];

        // 3. Purpose Distribution
        $purposeBreakdown = [
            'wallet_topup' => [
                'count' => Payment::withoutUserScope()->where('purpose', PaymentPurpose::WALLET_TOPUP->value)->where('status', PaymentStatus::SUCCESS->value)->count(),
                'amount' => (float) Payment::withoutUserScope()->where('purpose', PaymentPurpose::WALLET_TOPUP->value)->where('status', PaymentStatus::SUCCESS->value)->sum('amount'),
            ],
            'direct_subscription' => [
                'count' => Payment::withoutUserScope()->where('purpose', PaymentPurpose::DIRECT_SUBSCRIPTION->value)->where('status', PaymentStatus::SUCCESS->value)->count(),
                'amount' => (float) Payment::withoutUserScope()->where('purpose', PaymentPurpose::DIRECT_SUBSCRIPTION->value)->where('status', PaymentStatus::SUCCESS->value)->sum('amount'),
            ],
        ];

        // 4. Top Billing Agents by Payments
        $topAgents = Payment::withoutUserScope()
            ->select('user_id', DB::raw('SUM(amount) as total_spent'), DB::raw('COUNT(id) as transaction_count'))
            ->where('status', PaymentStatus::SUCCESS->value)
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->with('user')
            ->get();

        // 5. Monthly Trend (Last 6 Months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth();
            $mEnd = $monthDate->copy()->endOfMonth();

            $monthlyTrend[] = [
                'month' => $monthDate->format('M Y'),
                'amount' => (float) Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->whereBetween('created_at', [$mStart, $mEnd])->sum('amount'),
                'count' => Payment::withoutUserScope()->where('status', PaymentStatus::SUCCESS->value)->whereBetween('created_at', [$mStart, $mEnd])->count(),
            ];
        }

        return view('admin.payments.analytics', compact(
            'totalCollected',
            'monthCollected',
            'todayCollected',
            'totalTransactions',
            'successCount',
            'rejectedCount',
            'pendingCount',
            'failedCount',
            'successRate',
            'avgTicketSize',
            'modeBreakdown',
            'purposeBreakdown',
            'topAgents',
            'monthlyTrend'
        ));
    }

    /**
     * Audit Log & Verification Trail.
     */
    public function audit(Request $request): View
    {
        $actionFilter = $request->query('action');
        $search = $request->query('search');

        $query = PaymentAuditLog::with(['admin', 'payment.user'])
            ->latest('id');

        if ($actionFilter) {
            $query->where('action', $actionFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('payment_id', 'like', "%{$search}%")
                  ->orWhereHas('admin', function ($a) use ($search) {
                      $a->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('payment.user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $auditLogs = $query->paginate(25)->withQueryString();

        return view('admin.payments.audit', compact('auditLogs', 'actionFilter', 'search'));
    }

    /**
     * Payment Sandbox & Testing Simulator Console.
     */
    public function simulator(Request $request): View
    {
        $users = User::where('status', 'active')->orderBy('name')->get();
        $settings = $this->settingsService->getSettings();
        
        $recentPayments = Payment::withoutUserScope()
            ->with(['user', 'verifiedBy'])
            ->latest('id')
            ->limit(10)
            ->get();

        $diagnostics = [
            'php_version' => PHP_VERSION,
            'curl_installed' => function_exists('curl_version'),
            'openssl_installed' => extension_loaded('openssl'),
            'json_installed' => extension_loaded('json'),
            'cashfree_sdk_available' => class_exists(\Cashfree\Cashfree::class),
            'razorpay_sdk_available' => class_exists(\Razorpay\Api\Api::class),
            'database_connection' => DB::connection()->getPdo() ? 'Connected (MySQL)' : 'Error',
        ];

        return view('admin.payments.simulator', compact('users', 'settings', 'recentPayments', 'diagnostics'));
    }

    /**
     * Simulate a completed PG Transaction (Success or Failure) for testing.
     */
    public function simulateCheckout(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'purpose' => 'required|in:wallet_topup,direct_subscription',
            'gateway' => 'required|in:razorpay,cashfree',
            'outcome' => 'required|in:success,failed',
        ]);

        $user = User::findOrFail($request->input('user_id'));
        $amount = (float) $request->input('amount');
        $purpose = PaymentPurpose::from($request->input('purpose'));
        $gateway = $request->input('gateway');
        $outcome = $request->input('outcome');

        $orderId = ($gateway === 'razorpay' ? 'order_sim_' : 'cf_ord_sim_') . Str::random(12);
        $paymentId = ($gateway === 'razorpay' ? 'pay_sim_' : 'cf_pay_sim_') . Str::random(12);

        $meta = [];
        if ($purpose === PaymentPurpose::DIRECT_SUBSCRIPTION) {
            $plan = \App\Models\Plan::where('is_active', true)->with('durations')->first();
            $duration = $plan?->durations->first();
            if ($plan && $duration) {
                $meta = ['plan_id' => $plan->id, 'duration_id' => $duration->id, 'action_type' => 'new'];
            }
        }

        if ($outcome === 'success') {
            $payment = Payment::create([
                'user_id' => $user->id,
                'mode' => PaymentMode::PG,
                'purpose' => $purpose,
                'amount' => $amount,
                'currency' => 'INR',
                'status' => PaymentStatus::SUCCESS,
                'gateway_order_id' => $orderId,
                'gateway_payment_id' => $paymentId,
                'verified_at' => now(),
                'meta' => $meta,
            ]);

            PaymentAuditLog::create([
                'payment_id' => $payment->id,
                'admin_id' => $request->user()->id,
                'action' => PaymentAuditAction::APPROVED,
                'notes' => "[SIMULATOR] Mock {$gateway} checkout completed successfully (Ref: {$paymentId}).",
            ]);

            event(new PaymentSuccessEvent($payment));

            return redirect()->back()->with('success', "Simulated successful {$gateway} payment of ₹" . number_format($amount, 2) . " for {$user->name} (Payment #{$payment->id})!");
        } else {
            $payment = Payment::create([
                'user_id' => $user->id,
                'mode' => PaymentMode::PG,
                'purpose' => $purpose,
                'amount' => $amount,
                'currency' => 'INR',
                'status' => PaymentStatus::FAILED,
                'gateway_order_id' => $orderId,
                'gateway_payment_id' => $paymentId,
                'rejection_reason' => 'Simulated card declined / insufficient funds by test sandbox',
            ]);

            PaymentAuditLog::create([
                'payment_id' => $payment->id,
                'admin_id' => $request->user()->id,
                'action' => PaymentAuditAction::REJECTED,
                'notes' => "[SIMULATOR] Mock {$gateway} payment failure generated for test purposes.",
            ]);

            event(new PaymentFailedEvent($payment, 'Simulated payment failure'));

            return redirect()->back()->with('error', "Simulated failed {$gateway} payment generated (Payment #{$payment->id}).");
        }
    }

    /**
     * Dispatch mock signed webhook for test purposes.
     */
    public function simulateWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'event_type' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'gateway' => 'required|in:razorpay,cashfree',
        ]);

        $gateway = $request->input('gateway');
        $eventType = $request->input('event_type');
        $amount = (float) $request->input('amount');
        $user = User::first() ?? $request->user();

        // Create initial pending payment if testing webhook matching
        $orderId = ($gateway === 'razorpay' ? 'order_wh_' : 'cf_ord_wh_') . Str::random(10);
        $payment = Payment::create([
            'user_id' => $user->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING,
            'gateway_order_id' => $orderId,
        ]);

        if ($gateway === 'razorpay') {
            $webhookSecret = $this->settingsService->getRazorpayWebhookSecret();
            $payload = [
                'entity' => 'event',
                'event' => $eventType,
                'payload' => [
                    'payment' => [
                        'entity' => [
                            'id' => 'pay_rzp_wh_' . Str::random(8),
                            'order_id' => $orderId,
                            'amount' => (int) round($amount * 100),
                            'currency' => 'INR',
                            'status' => ($eventType === 'payment.captured') ? 'captured' : 'failed',
                        ]
                    ]
                ]
            ];
            $rawJson = json_encode($payload);
            $signature = hash_hmac('sha256', $rawJson, $webhookSecret);

            // Explicitly verify signature via OnlinePaymentGatewayService to test the verification path
            $isSigValid = $this->onlinePgService->verifyWebhookSignature($rawJson, $signature);
            if (!$isSigValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Signature verification failed during simulation.',
                ], 400);
            }

            $result = $this->onlinePgService->processWebhook($payload);

            return response()->json([
                'status' => 'ok',
                'gateway' => 'razorpay',
                'event' => $eventType,
                'signature_tested' => $signature,
                'signature_verified' => true,
                'result' => $result,
                'payment_id' => $payment->id,
                'final_payment_status' => $payment->fresh()->status->value,
            ]);
        } else {
            $webhookSecret = $this->settingsService->getCashfreeSecretKey();
            $timestamp = (string) round(microtime(true) * 1000);
            $payload = [
                'type' => $eventType,
                'event_time' => now()->toIso8601String(),
                'data' => [
                    'order' => [
                        'order_id' => $orderId,
                        'order_amount' => $amount,
                        'order_currency' => 'INR',
                    ],
                    'payment' => [
                        'cf_payment_id' => rand(10000000, 99999999),
                        'payment_status' => ($eventType === 'PAYMENT_SUCCESS_WEBHOOK') ? 'SUCCESS' : 'FAILED',
                        'payment_amount' => $amount,
                        'payment_currency' => 'INR',
                        'payment_message' => 'Simulated Cashfree test event',
                    ]
                ]
            ];
            $rawJson = json_encode($payload);
            $signature = base64_encode(hash_hmac('sha256', $timestamp . $rawJson, $webhookSecret, true));

            // Explicitly verify signature via OnlinePaymentGatewayService to test the verification path
            $isSigValid = $this->onlinePgService->verifyWebhookSignature($rawJson, $signature, $timestamp);
            if (!$isSigValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Signature verification failed during simulation.',
                ], 400);
            }

            $result = $this->onlinePgService->processWebhook($payload);

            return response()->json([
                'status' => 'ok',
                'gateway' => 'cashfree',
                'event' => $eventType,
                'signature_tested' => $signature,
                'signature_verified' => true,
                'result' => $result,
                'payment_id' => $payment->id,
                'final_payment_status' => $payment->fresh()->status->value,
            ]);
        }
    }

    /**
     * Seed sample test payments (Pending UPI, Pending Bank, Success, Rejected).
     */
    public function seedDemoPayments(Request $request): RedirectResponse
    {
        $users = User::where('status', 'active')->limit(3)->get();
        if ($users->isEmpty()) {
            return redirect()->back()->with('error', 'No active users found to seed test payments.');
        }

        $admin = $request->user();

        // 1. Pending Manual UPI
        Payment::create([
            'user_id' => $users->first()->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 1500.0,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'utr_number' => '423' . rand(100000000, 999999999),
        ]);

        // 2. Pending Bank Transfer
        Payment::create([
            'user_id' => $users->random()->id,
            'mode' => PaymentMode::BANK_TRANSFER,
            'purpose' => PaymentPurpose::DIRECT_SUBSCRIPTION,
            'amount' => 5000.0,
            'currency' => 'INR',
            'status' => PaymentStatus::PENDING_VERIFICATION,
            'bank_reference' => 'NEFT-SBIN-' . rand(100000, 999999),
        ]);

        // 3. Successful Razorpay PG
        $successPg = Payment::create([
            'user_id' => $users->random()->id,
            'mode' => PaymentMode::PG,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 2500.0,
            'currency' => 'INR',
            'status' => PaymentStatus::SUCCESS,
            'gateway_order_id' => 'order_' . Str::random(10),
            'gateway_payment_id' => 'pay_' . Str::random(10),
            'verified_at' => now(),
        ]);
        PaymentAuditLog::create([
            'payment_id' => $successPg->id,
            'admin_id' => null,
            'action' => PaymentAuditAction::APPROVED,
            'notes' => 'Mock payment auto-verified via Razorpay webhook signature.',
        ]);

        // 4. Rejected Manual UPI
        $rejectedPayment = Payment::create([
            'user_id' => $users->last()->id,
            'mode' => PaymentMode::MANUAL_UPI,
            'purpose' => PaymentPurpose::WALLET_TOPUP,
            'amount' => 300.0,
            'currency' => 'INR',
            'status' => PaymentStatus::REJECTED,
            'utr_number' => 'INVALID_' . rand(1000, 9999),
            'rejection_reason' => 'UTR not found in bank statement records during manual audit.',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);
        PaymentAuditLog::create([
            'payment_id' => $rejectedPayment->id,
            'admin_id' => $admin->id,
            'action' => PaymentAuditAction::REJECTED,
            'notes' => 'UTR not found in bank statement records during manual audit.',
        ]);

        return redirect()->back()->with('success', 'Generated 4 demo payments across pending, success, and rejected queues!');
    }

    /**
     * Display specific payment detail with full audit trail.
     */
    public function show(Payment $payment): View
    {
        $payment->load(['user', 'verifiedBy', 'auditLogs.admin']);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Approve manual payment (UPI or Bank Transfer).
     */
    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->verificationService->approve(
                $payment,
                $request->user(),
                $request->input('notes')
            );

            return redirect()->back()->with('success', "Payment #{$payment->id} for ₹" . number_format((float)$payment->amount, 2) . " approved successfully.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject manual payment with mandatory rejection reason.
     */
    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:3|max:500',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->verificationService->reject(
                $payment,
                $request->user(),
                $request->input('rejection_reason'),
                $request->input('notes')
            );

            return redirect()->back()->with('success', "Payment #{$payment->id} has been rejected with reason logged.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Process manual refund for a successful payment.
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'refund_reason' => 'required|string|min:3|max:500',
        ]);

        try {
            $this->verificationService->refund(
                $payment,
                $request->user(),
                $request->input('refund_reason')
            );

            return redirect()->back()->with('success', "Refund logged for Payment #{$payment->id}.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display payment settings and gateway controls.
     */
    public function settings(): View
    {
        $settings = $this->settingsService->getSettings();
        return view('admin.payments.settings', compact('settings'));
    }

    /**
     * Update payment gateway & manual transfer settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'min_amount' => 'required|numeric|min:1',
            'active_pg_driver' => 'nullable|string|in:cashfree,razorpay',
            'cashfree_app_id' => 'nullable|string|max:255',
            'cashfree_secret_key' => 'nullable|string|max:255',
            'cashfree_environment' => 'nullable|string|in:sandbox,production',
            'cashfree_webhook_secret' => 'nullable|string|max:255',
            'razorpay_key_id' => 'nullable|string|max:255',
            'razorpay_key_secret' => 'nullable|string|max:255',
            'razorpay_webhook_secret' => 'nullable|string|max:255',
            'business_upi_id' => 'required|string|max:100',
            'business_upi_name' => 'required|string|max:100',
            'bank_account_name' => 'required|string|max:100',
            'bank_account_number' => 'required|string|max:50',
            'bank_ifsc' => 'required|string|max:20',
            'bank_name' => 'required|string|max:100',
        ]);

        $this->settingsService->updateSettings([
            'pg_enabled' => $request->boolean('pg_enabled'),
            'cashfree_enabled' => $request->boolean('cashfree_enabled'),
            'razorpay_enabled' => $request->boolean('razorpay_enabled'),
            'manual_upi_enabled' => $request->boolean('manual_upi_enabled'),
            'bank_transfer_enabled' => $request->boolean('bank_transfer_enabled'),
            'min_amount' => (float) $request->input('min_amount'),
            'active_pg_driver' => (string) $request->input('active_pg_driver', 'cashfree'),
            'cashfree_app_id' => (string) $request->input('cashfree_app_id'),
            'cashfree_secret_key' => (string) $request->input('cashfree_secret_key'),
            'cashfree_environment' => (string) $request->input('cashfree_environment', 'sandbox'),
            'cashfree_webhook_secret' => (string) $request->input('cashfree_webhook_secret'),
            'razorpay_key_id' => (string) $request->input('razorpay_key_id'),
            'razorpay_key_secret' => (string) $request->input('razorpay_key_secret'),
            'razorpay_webhook_secret' => (string) $request->input('razorpay_webhook_secret'),
            'business_upi_id' => (string) $request->input('business_upi_id'),
            'business_upi_name' => (string) $request->input('business_upi_name'),
            'bank_account_name' => (string) $request->input('bank_account_name'),
            'bank_account_number' => (string) $request->input('bank_account_number'),
            'bank_ifsc' => (string) $request->input('bank_ifsc'),
            'bank_name' => (string) $request->input('bank_name'),
        ]);

        return redirect()->route('admin.payments.settings')->with('success', 'Payment gateway & channel settings updated successfully.');
    }
}
