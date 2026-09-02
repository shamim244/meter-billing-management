<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\BillStatus;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Services\EngineService;
use App\Services\Plan\ConsumerQuotaService;
use App\Services\Plan\MruQuotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MruController extends Controller
{
    protected EngineService $engineService;
    protected MruQuotaService $mruQuotaService;
    protected ConsumerQuotaService $consumerQuotaService;

    public function __construct(
        EngineService $engineService,
        MruQuotaService $mruQuotaService,
        ConsumerQuotaService $consumerQuotaService
    ) {
        $this->engineService = $engineService;
        $this->mruQuotaService = $mruQuotaService;
        $this->consumerQuotaService = $consumerQuotaService;
    }

    /**
     * Display all MRU Workspaces for the current billing agent / user.
     */
    public function index(): View
    {
        $userId = Auth::id();
        $mrus = Mru::withCount(['consumerAccounts', 'billRecords'])
            ->orderBy('code')
            ->get();

        $activeSubscription = $this->mruQuotaService->getActiveSubscription($userId);
        $availableQuota = $this->mruQuotaService->checkMruQuotaAvailable($userId);

        return view('mrus.index', compact('mrus', 'activeSubscription', 'availableQuota'));
    }

    /**
     * Store a new MRU workspace.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'full_identifier' => 'nullable|string|max:255',
            'pay_overage' => 'nullable|boolean',
        ]);

        $userId = Auth::id();
        $code = strtoupper(trim($request->input('code')));

        // Check if an MRU with this code already exists for the current user
        $existing = Mru::where('user_id', $userId)->where('code', $code)->first();
        if ($existing) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'already_exists' => true,
                    'message' => "MRU '{$existing->name} ({$existing->code})' already exists in your workspace.",
                    'mru' => [
                        'id' => $existing->id,
                        'code' => $existing->code,
                        'name' => $existing->name,
                        'full_identifier' => $existing->full_identifier,
                        'consumers_count' => $existing->consumerAccounts()->count(),
                        'show_url' => route('mrus.show', $existing),
                        'dashboard_url' => route('dashboard', ['mru_id' => $existing->id]),
                    ]
                ], 200);
            }

            return redirect()->route('mrus.show', $existing)
                ->with('info', "MRU '{$existing->name} ({$existing->code})' already exists in your workspace. Opened existing workspace.");
        }

        // Quota check & Pay-gate flow
        $activeSubscription = $this->mruQuotaService->getActiveSubscription($userId);
        $payOverage = $request->boolean('pay_overage', false);

        if ($activeSubscription && $this->mruQuotaService->checkMruQuotaAvailable($userId) <= 0 && !$payOverage) {
            $extraRate = (float) $activeSubscription->extra_mru_rate_locked;
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'requires_overage' => true,
                    'overage_type' => 'mru_creation',
                    'amount_due' => $extraRate,
                    'message' => "This exceeds your plan's included MRU limit ({$activeSubscription->included_mrus_locked}). Pay ₹" . number_format($extraRate, 2) . " to create this MRU.",
                ], 402);
            }

            return redirect()->route('mrus.index')->with('error', "This exceeds your plan's included MRU limit ({$activeSubscription->included_mrus_locked}). Additional MRU creation requires overage confirmation (₹" . number_format($extraRate, 2) . ").");
        }

        $mru = Mru::create([
            'user_id' => $userId,
            'code' => $code,
            'name' => trim($request->input('name')),
            'full_identifier' => trim($request->input('full_identifier', $code)),
            'status' => 'active',
        ]);

        if ($activeSubscription) {
            $quotaResult = $this->mruQuotaService->consumeMruSlot($userId, $mru, $payOverage);
            if (!$quotaResult['allowed']) {
                $mru->delete();
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json([
                        'requires_overage' => true,
                        'amount_due' => $quotaResult['amount_due'] ?? 0,
                        'message' => $quotaResult['message'] ?? $quotaResult['reason'] ?? 'MRU quota exceeded.',
                    ], 402);
                }
                return redirect()->route('mrus.index')->with('error', $quotaResult['message'] ?? 'MRU quota exceeded.');
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'already_exists' => false,
                'message' => "MRU '{$mru->name} ({$mru->code})' created successfully.",
                'redirect_url' => route('mrus.show', $mru),
                'mru' => [
                    'id' => $mru->id,
                    'code' => $mru->code,
                    'name' => $mru->name,
                ]
            ], 201);
        }

        return redirect()->route('mrus.show', $mru)->with('success', "MRU '{$mru->name} ({$mru->code})' created successfully.");
    }

    /**
     * Self-lock an active MRU.
     */
    public function lock(Mru $mru, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeMru($mru);

        $reason = $request->input('reason', 'user_manual_lock');
        $this->mruQuotaService->lockMru($mru, $reason);

        $msg = "MRU '{$mru->name} ({$mru->code})' has been locked.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'mru' => $mru->fresh(),
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Unlock an MRU via wallet deduction.
     */
    public function unlock(Mru $mru, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeMru($mru);

        $payOverage = $request->boolean('pay_overage', true);
        $result = $this->mruQuotaService->unlockMru($mru, $payOverage);

        if (!$result['success']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'amount_due' => $result['amount_due'] ?? 0,
                ], 402);
            }

            return redirect()->route('mrus.show', $mru)->with('error', $result['message']);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "MRU '{$mru->name}' unlocked successfully.",
                'mru' => $result['mru'],
            ]);
        }

        return redirect()->route('mrus.show', $mru)->with('success', "MRU '{$mru->name}' unlocked successfully.");
    }

    /**
     * Show single MRU Workspace Hub (Billing Sessions & Master Consumer List).
     */
    public function show(Mru $mru, Request $request): View
    {
        $this->authorizeMru($mru);

        $search = trim($request->get('search', ''));

        // Permanent Consumer Master List for this MRU
        $consumersQuery = $mru->consumerAccounts()->orderBy('ca_number');
        if (!empty($search)) {
            $escaped = addcslashes($search, '%_\\');
            $consumersQuery->where(function ($q) use ($escaped) {
                $q->where('ca_number', 'like', "%{$escaped}%")
                  ->orWhere('consumer_name', 'like', "%{$escaped}%")
                  ->orWhere('meter_no', 'like', "%{$escaped}%");
            });
        }
        $consumers = $consumersQuery->paginate(50);

        // Monthly Billing Sessions inside this MRU
        $sessions = BillRecord::where('mru_id', $mru->id)
            ->selectRaw('billing_month, billing_year, COUNT(*) as total_bills, SUM(total_amount) as total_amount, SUM(units_consumed) as total_units, MAX(updated_at) as last_updated')
            ->groupBy('billing_month', 'billing_year')
            ->orderBy('billing_year', 'desc')
            ->orderBy('billing_month', 'desc')
            ->get();

        return view('mrus.show', compact('mru', 'consumers', 'sessions', 'search'));
    }

    /**
     * Update MRU details (handles renaming and physical folder migration).
     */
    public function update(Request $request, Mru $mru): RedirectResponse
    {
        $this->authorizeMru($mru);

        $userId = $mru->user_id ?? Auth::id();

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                \Illuminate\Validation\Rule::unique('mrus', 'code')
                    ->where(fn ($q) => $q->where('user_id', $userId))
                    ->ignore($mru->id),
            ],
            'status' => 'required|string|in:active,inactive',
        ]);

        $oldCode = $mru->code;
        $newCode = strtoupper(trim($request->code));

        \Illuminate\Support\Facades\DB::transaction(function () use ($mru, $oldCode, $newCode, $userId, $request) {
            // If MRU Code changed, migrate physical PDF folders and database paths
            if ($oldCode !== $newCode) {
                $bills = BillRecord::where('mru_id', $mru->id)->get();
                $cycles = $bills->groupBy(fn($b) => "{$b->billing_year}_{$b->billing_month}");

                foreach ($cycles as $cycleBills) {
                    $first = $cycleBills->first();
                    $year = $first->billing_year;
                    $month = $first->billing_month;

                    $oldDir = "users/{$userId}/pdfs/{$year}/{$month}/{$oldCode}";
                    $newDir = "users/{$userId}/pdfs/{$year}/{$month}/{$newCode}";

                    if (Storage::disk('local')->exists($oldDir)) {
                        Storage::disk('local')->move($oldDir, $newDir);
                    }

                    // Update bill_records pdf_path in database
                    foreach ($cycleBills as $b) {
                        if (!empty($b->pdf_path) && str_contains($b->pdf_path, "/{$oldCode}/")) {
                            $b->pdf_path = str_replace("/{$oldCode}/", "/{$newCode}/", $b->pdf_path);
                            $b->save();
                        }
                    }
                }
            }

            $mru->update([
                'name' => trim($request->name),
                'code' => $newCode,
                'status' => $request->status,
            ]);
        });

        return back()->with('success', "MRU '{$mru->name}' updated successfully (PDF storage paths synced).");
    }

    /**
     * Authorize MRU ownership.
     */
    protected function authorizeMru(Mru $mru): void
    {
        $currentUser = Auth::user();
        if (!$currentUser || ($mru->user_id !== $currentUser->id && !$currentUser->hasRole('admin'))) {
            abort(403, 'Unauthorized access to this MRU.');
        }
    }

    /**
     * Delete MRU and purge all its physical PDF storage folders.
     */
    public function destroy(Request $request, Mru $mru): RedirectResponse|JsonResponse
    {
        $this->authorizeMru($mru);

        $name = $mru->name;
        $userId = $mru->user_id;
        $mruCode = $mru->code;

        \Illuminate\Support\Facades\DB::transaction(function () use ($mru, $userId, $mruCode) {
            // 1. Purge all physical PDF directories for this MRU across all years and months
            $bills = BillRecord::where('mru_id', $mru->id)->get();
            $cycles = $bills->groupBy(fn($b) => "{$b->billing_year}_{$b->billing_month}");

            foreach ($cycles as $cycleBills) {
                $first = $cycleBills->first();
                $dir = "users/{$userId}/pdfs/{$first->billing_year}/{$first->billing_month}/{$mruCode}";
                if (Storage::disk('local')->exists($dir)) {
                    Storage::disk('local')->deleteDirectory($dir);
                }
            }

            // 2. Cleanly delete database records
            $caNumbers = $mru->consumerAccounts()->pluck('ca_number')->toArray();
            if (!empty($caNumbers)) {
                BillStatus::where('user_id', $userId)->whereIn('ca_number', $caNumbers)->delete();
            }

            $mru->consumerAccounts()->delete();
            $mru->billRecords()->delete();
            $mru->delete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "MRU '{$name}' and all associated physical PDF files deleted successfully.",
                'redirect_url' => route('mrus.index'),
            ]);
        }

        return redirect()->route('mrus.index')->with('success', "MRU '{$name}' and all associated physical PDF files deleted successfully.");
    }

    /**
     * Delete a single monthly billing session & purge its physical PDFs.
     */
    public function deleteSession(Mru $mru, int $month, int $year): RedirectResponse
    {
        $this->authorizeMru($mru);

        $userId = $mru->user_id;
        $mruCode = $mru->code;
        $monthLabel = date('M, Y', mktime(0, 0, 0, $month, 1, $year));
        $count = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($mru, $userId, $mruCode, $month, $year, &$count) {
            // 1. Purge physical storage folder for this specific session
            $dir = "users/{$userId}/pdfs/{$year}/{$month}/{$mruCode}";
            if (Storage::disk('local')->exists($dir)) {
                Storage::disk('local')->deleteDirectory($dir);
            }

            // 2. Clean database records
            $bills = BillRecord::where('mru_id', $mru->id)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->get();

            $caNumbers = $bills->pluck('ca_number')->toArray();
            if (!empty($caNumbers)) {
                BillStatus::where('user_id', $userId)
                    ->where('billing_month', $month)
                    ->where('billing_year', $year)
                    ->whereIn('ca_number', $caNumbers)
                    ->delete();
            }

            $count = $bills->count();
            BillRecord::where('mru_id', $mru->id)
                ->where('billing_month', $month)
                ->where('billing_year', $year)
                ->delete();
        });

        return back()->with('success', "Billing session {$monthLabel} for MRU '{$mru->name}' ({$count} bills and PDF files) deleted successfully.");
    }

    /**
     * Add single consumer to MRU Master List.
     */
    public function addConsumer(Request $request, Mru $mru): RedirectResponse
    {
        $this->authorizeMru($mru);

        $request->validate([
            'ca_number' => 'required|string|max:50',
            'consumer_name' => 'nullable|string|max:255',
            'meter_no' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();
        $ca = trim($request->ca_number);

        ConsumerAccount::updateOrCreate(
            ['user_id' => $userId, 'ca_number' => $ca],
            [
                'mru_id' => $mru->id,
                'consumer_name' => $request->consumer_name,
                'meter_no' => $request->meter_no,
                'mobile' => $request->mobile,
                'address' => $request->address,
                'status' => 'active',
            ]
        );

        return back()->with('success', "Consumer CA '{$ca}' added to MRU '{$mru->name}'.");
    }

    /**
     * Bulk Import / Paste CAs into MRU Master List (Supports plain CA list, CSV, and TSV formats).
     */
    public function importConsumers(Request $request, Mru $mru): RedirectResponse
    {
        $this->authorizeMru($mru);

        $request->validate([
            'ca_data' => 'required|string',
        ]);

        $userId = Auth::id();
        $rawLines = preg_split('/[\r\n]+/', trim($request->ca_data));
        $importedCount = 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($rawLines, $mru, $userId, &$importedCount) {
            foreach ($rawLines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                // Parse line whether TSV, CSV or plain text
                $cols = str_contains($line, "\t") ? explode("\t", $line) : str_getcsv($line);
                $cols = array_map('trim', $cols);

                $ca = $cols[0] ?? '';
                // Skip header or non-numeric CAs
                if (!preg_match('/^\d+$/', $ca)) continue;

                $name = !empty($cols[1]) ? $cols[1] : null;
                $meter = !empty($cols[2]) ? $cols[2] : null;
                $mobile = !empty($cols[3]) ? $cols[3] : null;
                $address = !empty($cols[4]) ? $cols[4] : null;

                $payload = [
                    'mru_id' => $mru->id,
                    'status' => 'active',
                ];
                if ($name !== null) $payload['consumer_name'] = $name;
                if ($meter !== null) $payload['meter_no'] = $meter;
                if ($mobile !== null) $payload['mobile'] = $mobile;
                if ($address !== null) $payload['address'] = $address;

                ConsumerAccount::updateOrCreate(
                    ['user_id' => $userId, 'ca_number' => $ca],
                    $payload
                );
                $importedCount++;
            }
        });

        return back()->with('success', "Successfully imported {$importedCount} consumers into MRU '{$mru->name}'.");
    }

    /**
     * Update a consumer in the MRU master list.
     */
    public function updateConsumer(Request $request, Mru $mru, ConsumerAccount $consumer): RedirectResponse
    {
        $this->authorizeConsumer($mru, $consumer);

        $request->validate([
            'consumer_name' => 'nullable|string|max:255',
            'meter_no' => 'nullable|string|max:50',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'required|string|in:active,inactive',
        ]);

        $consumer->update($request->only(['consumer_name', 'meter_no', 'mobile', 'address', 'status']));

        return back()->with('success', "Consumer '{$consumer->ca_number}' updated.");
    }

    /**
     * Remove a consumer from the MRU master list.
     */
    public function deleteConsumer(Mru $mru, ConsumerAccount $consumer): RedirectResponse
    {
        $this->authorizeConsumer($mru, $consumer);

        $ca = $consumer->ca_number;
        $consumer->delete();

        return back()->with('success', "Consumer '{$ca}' removed from MRU master list.");
    }

    /**
     * Export MRU Master Consumer List as CSV.
     */
    public function exportConsumers(Mru $mru): StreamedResponse
    {
        $this->authorizeMru($mru);

        $consumers = $mru->consumerAccounts()->orderBy('ca_number')->get();
        $fileName = "MRU_{$mru->code}_consumers_" . date('Ymd') . ".csv";

        return response()->streamDownload(function () use ($consumers, $mru) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['CA Number', 'Consumer Name', 'MRU Code', 'MRU Name', 'Meter No', 'Mobile', 'Address', 'Status']);

            foreach ($consumers as $c) {
                fputcsv($output, [
                    $c->ca_number,
                    $c->consumer_name,
                    $mru->code,
                    $mru->name,
                    $c->meter_no,
                    $c->mobile,
                    $c->address,
                    ucfirst($c->status),
                ]);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Create / Initialize a billing cycle session for this MRU without downloading bills immediately.
     */
    public function createCycleOnly(Request $request, Mru $mru): JsonResponse
    {
        $this->authorizeMru($mru);

        $request->validate([
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|min:2020|max:2035',
        ]);

        $month = (int) $request->input('billing_month');
        $year = (int) $request->input('billing_year');
        $userId = Auth::id();

        $monthLabel = strtoupper(date('M, Y', mktime(0, 0, 0, $month, 1, $year)));

        $activeConsumers = $mru->consumerAccounts()
            ->where('status', 'active')
            ->get();

        if ($activeConsumers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "MRU '{$mru->name}' has no active consumers. Please add consumers first.",
            ], 422);
        }

        $payOverage = $request->boolean('pay_overage', false);
        $quotaResult = $this->consumerQuotaService->consumeConsumerQuota(
            user: $userId,
            mru: $mru,
            month: $month,
            year: $year,
            consumerCount: $activeConsumers->count(),
            payOverage: $payOverage
        );

        if (!$quotaResult['allowed']) {
            return response()->json([
                'success' => false,
                'requires_overage' => $quotaResult['requires_payment'] ?? false,
                'overage_type' => 'consumer_cycle',
                'amount_due' => $quotaResult['amount_due'] ?? 0,
                'extra_count' => $quotaResult['extra_count'] ?? 0,
                'message' => $quotaResult['reason'] ?? $quotaResult['message'] ?? 'Consumer quota exceeded.',
            ], 402);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($activeConsumers, $userId, $month, $year, $monthLabel, $mru) {
            foreach ($activeConsumers as $consumer) {
                // Find strictly preceding record in DB if exists
                $prevRecord = BillRecord::where('user_id', $userId)
                    ->where('ca_number', $consumer->ca_number)
                    ->where(function ($q) use ($month, $year) {
                        $q->where('billing_year', '<', $year)
                          ->orWhere(function ($q2) use ($month, $year) {
                              $q2->where('billing_year', $year)
                                 ->where('billing_month', '<', $month);
                          });
                    })
                    ->orderBy('billing_year', 'desc')
                    ->orderBy('billing_month', 'desc')
                    ->first();

                $initialPrevReading = null;
                if ($prevRecord) {
                    $initialPrevReading = $prevRecord->working_reading ?: ($prevRecord->current_reading ?: $prevRecord->previous_reading);
                } else {
                    $initialPrevReading = $consumer->last_working_reading ?: $consumer->baseline_previous_reading;
                }

                BillRecord::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'ca_number' => $consumer->ca_number,
                        'billing_month' => $month,
                        'billing_year' => $year,
                    ],
                    [
                        'mru_id' => $mru->id,
                        'bill_month_label' => $monthLabel,
                        'consumer_name' => $consumer->consumer_name,
                        'meter_no' => $consumer->meter_no,
                        'previous_reading' => $initialPrevReading,
                    ]
                );
            }
        });

        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));

        return response()->json([
            'success' => true,
            'message' => "Billing cycle for {$monthName} {$year} created successfully for {$activeConsumers->count()} consumers.",
            'redirect_url' => route('dashboard', ['mru_id' => $mru->id, 'month' => $month, 'year' => $year]),
        ]);
    }

    /**
     * Start a monthly billing download for all consumers in this MRU for a specified month & year cycle.
     */
    public function startMonthlyBilling(Request $request, Mru $mru): JsonResponse
    {
        $this->authorizeMru($mru);

        $request->validate([
            'billing_month' => 'nullable|integer|between:1,12',
            'billing_year' => 'nullable|integer|min:2020|max:2035',
            'action_type' => 'nullable|string|in:create_only,download_all',
        ]);

        if ($request->input('action_type') === 'create_only') {
            return $this->createCycleOnly($request, $mru);
        }

        $month = (int) $request->input('billing_month', now()->month);
        $year = (int) $request->input('billing_year', now()->year);

        $userId = Auth::id();
        $consumerCAs = $mru->consumerAccounts()
            ->where('status', 'active')
            ->pluck('ca_number')
            ->toArray();

        if (empty($consumerCAs)) {
            return response()->json([
                'success' => false,
                'message' => "MRU '{$mru->name}' has no active consumers. Please add consumers first.",
            ], 422);
        }

        $payOverage = $request->boolean('pay_overage', false);
        $quotaResult = $this->consumerQuotaService->consumeConsumerQuota(
            user: $userId,
            mru: $mru,
            month: $month,
            year: $year,
            consumerCount: count($consumerCAs),
            payOverage: $payOverage
        );

        if (!$quotaResult['allowed']) {
            return response()->json([
                'success' => false,
                'requires_overage' => $quotaResult['requires_payment'] ?? false,
                'overage_type' => 'consumer_cycle',
                'amount_due' => $quotaResult['amount_due'] ?? 0,
                'extra_count' => $quotaResult['extra_count'] ?? 0,
                'message' => $quotaResult['reason'] ?? $quotaResult['message'] ?? 'Consumer quota exceeded.',
            ], 402);
        }

        $results = $this->engineService->downloadAndParseBills($consumerCAs, $userId, $month, $year, $mru->id);
        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));

        return response()->json([
            'success' => true,
            'message' => "Successfully launched {$monthName} {$year} billing cycle for {$results['success']} out of {$results['total']} consumers in MRU {$mru->name}.",
            'results' => $results,
            'redirect_url' => route('dashboard', ['mru_id' => $mru->id, 'month' => $month, 'year' => $year]),
        ]);
    }

    /**
     * Global endpoint to create a new billing cycle for any selected MRU.
     */
    public function createBillingCycle(Request $request): JsonResponse
    {
        $request->validate([
            'mru_id' => 'required|exists:mrus,id',
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|min:2020|max:2035',
            'action_type' => 'nullable|string|in:create_only,download_all',
        ]);

        $mru = Mru::findOrFail($request->mru_id);
        $this->authorizeMru($mru);

        $actionType = $request->input('action_type', 'download_all');

        if ($actionType === 'create_only') {
            return $this->createCycleOnly($request, $mru);
        }

        return $this->startMonthlyBilling($request, $mru);
    }

    /**
     * Incrementally sync missing/failed bills for a specific MRU and period.
     */
    public function syncMissingForMru(Request $request, Mru $mru): JsonResponse
    {
        $this->authorizeMru($mru);

        $request->validate([
            'billing_month' => 'required|integer|between:1,12',
            'billing_year' => 'required|integer|min:2020|max:2035',
        ]);

        $month = (int)$request->input('billing_month');
        $year = (int)$request->input('billing_year');
        $userId = Auth::id();

        $allCAs = $mru->consumerAccounts()->where('status', 'active')->pluck('ca_number')->toArray();

        if (empty($allCAs)) {
            return response()->json([
                'success' => false,
                'message' => "MRU '{$mru->name}' has no active consumers.",
                'missing_count' => 0,
            ], 422);
        }

        $downloadedCAs = BillRecord::where('user_id', $userId)
            ->where('mru_id', $mru->id)
            ->where('billing_month', $month)
            ->where('billing_year', $year)
            ->where('download_status', 'downloaded')
            ->whereNotNull('pdf_path')
            ->pluck('ca_number')
            ->toArray();

        $missingCAs = array_values(array_diff($allCAs, $downloadedCAs));

        if (empty($missingCAs)) {
            return response()->json([
                'success' => true,
                'message' => "All bills for MRU '{$mru->name}' in this cycle are already downloaded!",
                'missing_count' => 0,
                'synced_count' => 0,
            ]);
        }

        $results = $this->engineService->downloadAndParseBills($missingCAs, $userId, $month, $year, $mru->id);

        return response()->json([
            'success' => true,
            'message' => "Synced {$results['success']} out of {$results['total']} missing bills for MRU '{$mru->name}'.",
            'missing_count' => count($missingCAs),
            'synced_count' => $results['success'],
            'results' => $results,
        ]);
    }

    /**
     * Authorize consumer ownership within MRU.
     */
    protected function authorizeConsumer(Mru $mru, ConsumerAccount $consumer): void
    {
        $this->authorizeMru($mru);

        $currentUser = Auth::user();
        if ($consumer->mru_id !== $mru->id || ($consumer->user_id !== $currentUser->id && !$currentUser->hasRole('admin'))) {
            abort(403, 'Unauthorized consumer operation in this MRU.');
        }
    }
}
