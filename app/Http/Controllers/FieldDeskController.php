<?php

namespace App\Http\Controllers;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\FieldDeskCategory;
use App\Models\Mru;
use App\Services\FieldDeskService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FieldDeskController extends Controller
{
    public function __construct(
        protected FieldDeskService $fieldDeskService
    ) {}

    /**
     * Display the Dedicated FieldDesk Workspace (Tier 1).
     */
    public function index(Request $request): View
    {
        $userId = (int) Auth::id();
        $currentUser = Auth::user();
        $isAdmin = $currentUser && method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin');

        $mrusQuery = Mru::query();
        if (! $isAdmin) {
            $mrusQuery->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('consumerAccounts', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })->orWhereHas('billRecords', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
            });
        }
        $mrus = $mrusQuery->orderBy('code')->get();

        $categories = FieldDeskCategory::active()->ordered()->get();
        $counts = $this->fieldDeskService->getSummaryCounts($userId);
        $initialCa = $request->query('ca', '');

        return view('field-desk.index', compact('categories', 'mrus', 'counts', 'initialCa'));
    }

    /**
     * Fetch filtered agenda items and summary counts as JSON for Alpine.js.
     */
    public function getData(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $filters = [
            'timeline' => $request->query('timeline', 'all_active'),
            'category_id' => $request->query('category_id'),
            'category_code' => $request->query('category_code'),
            'mru_id' => $request->query('mru_id'),
            'priority' => $request->query('priority'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ];

        $perPage = max(5, min(100, (int) $request->query('per_page', 25)));
        $paginator = $this->fieldDeskService->getFilteredAgenda($userId, $filters, $perPage);
        $counts = $this->fieldDeskService->getSummaryCounts($userId, $filters['mru_id'] ? (int) $filters['mru_id'] : null);

        // Decorate items with frontend-ready properties
        $today = Carbon::today();
        $decoratedItems = collect($paginator->items())->map(function (FieldDeskAction $action) use ($today) {
            $targetDate = $action->target_date ? Carbon::parse($action->target_date) : null;
            $isDueToday = $targetDate && $targetDate->isToday() && in_array($action->status, ['open', 'rescheduled']);
            $isOverdue = $targetDate && $targetDate->isPast() && ! $targetDate->isToday() && in_array($action->status, ['open', 'rescheduled']);
            $isUpcoming = $targetDate && $targetDate->isFuture() && ! $targetDate->isToday() && in_array($action->status, ['open', 'rescheduled']);
            $daysDiff = $targetDate ? (int) $today->diffInDays($targetDate, false) : 0;

            $mobile = $this->fieldDeskService->resolveConsumerMobile($action);
            $waLink = $this->fieldDeskService->generateWhatsAppLink($action);
            $waText = $this->fieldDeskService->generateWhatsAppText($action);

            $lat = $action->effective_latitude;
            $lng = $action->effective_longitude;
            $accuracy = $action->effective_accuracy;
            $mapLink = $action->map_link;
            $consumerAcc = $action->consumerAccount;

            return [
                'id' => $action->id,
                'ca_number' => $action->ca_number,
                'consumer_name' => $consumerAcc?->consumer_name ?? 'Consumer '.$action->ca_number,
                'mobile' => $mobile,
                'latitude' => $lat,
                'longitude' => $lng,
                'location_accuracy' => $accuracy,
                'map_link' => $mapLink,
                'consumer_latitude' => $consumerAcc?->latitude ? (float) $consumerAcc->latitude : null,
                'consumer_longitude' => $consumerAcc?->longitude ? (float) $consumerAcc->longitude : null,
                'consumer_accuracy' => $consumerAcc?->location_accuracy ? (float) $consumerAcc->location_accuracy : null,
                'consumer_mobile' => $consumerAcc?->mobile,
                'category_id' => $action->category_id,
                'category_name' => $action->category?->name ?? 'Action',
                'category_code' => $action->category?->code ?? 'general_note',
                'category_icon' => $action->category?->icon ?? '📋',
                'category_color' => $action->category?->color ?? '#10b981',
                'priority' => $action->priority,
                'status' => $action->status,
                'target_date' => $targetDate?->toDateString(),
                'target_date_formatted' => $targetDate?->format('d M Y'),
                'original_target_date' => $action->original_target_date?->toDateString(),
                'days_diff' => $daysDiff,
                'is_due_today' => $isDueToday,
                'is_overdue' => $isOverdue,
                'is_upcoming' => $isUpcoming,
                'target_amount' => (float) ($action->target_amount ?? 0),
                'collected_amount' => (float) ($action->collected_amount ?? 0),
                'remaining_amount' => $action->remaining_amount,
                'payment_mode' => $action->payment_mode,
                'private_note' => $action->private_note,
                'resolution_note' => $action->resolution_note,
                'reschedule_count' => (int) $action->reschedule_count,
                'mru_id' => $action->mru_id,
                'mru_code' => $action->mru?->code,
                'mru_name' => $action->mru?->name,
                'whatsapp_link' => $waLink,
                'whatsapp_text' => $waText,
                'created_at' => $action->created_at?->format('d M Y, h:i A'),
                'resolved_at' => $action->resolved_at?->format('d M Y, h:i A'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $decoratedItems,
            'counts' => $counts,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * Store a newly created FieldDesk action.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $validated = $request->validate([
            'ca_number' => ['required', 'string', 'max:50'],
            'category_id' => ['required', 'integer', 'exists:field_desk_categories,id'],
            'target_date' => ['required', 'date'],
            'priority' => ['nullable', 'in:urgent,high,normal,low'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'private_note' => ['nullable', 'string', 'max:1000'],
            'mru_id' => ['nullable', 'integer', 'exists:mrus,id'],
            'billing_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'billing_year' => ['nullable', 'integer', 'min:2020', 'max:2040'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric', 'min:0'],
            'save_to_consumer' => ['nullable', 'boolean'],
            'mobile' => ['nullable', 'string', 'max:20'],
        ]);

        // Auto-link consumer account if exists, or auto-create from existing BillRecord
        $consumer = ConsumerAccount::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('ca_number', $validated['ca_number'])
            ->first();

        if (! $consumer) {
            $bill = BillRecord::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('ca_number', $validated['ca_number'])
                ->latest()
                ->first();

            if ($bill) {
                $consumer = ConsumerAccount::create([
                    'user_id' => $userId,
                    'ca_number' => trim($validated['ca_number']),
                    'mru_id' => $bill->mru_id,
                    'consumer_name' => $bill->consumer_name ?: ('Consumer '.trim($validated['ca_number'])),
                    'meter_no' => $bill->meter_no,
                    'tariff_category' => $bill->tariff_category ?? 'DS-II',
                    'billing_basis' => $bill->billing_basis ?? 'OK',
                    'baseline_amount' => $bill->total_amount,
                ]);
            }
        }

        // Save mobile number to consumer if passed
        if (! empty($validated['mobile']) && $consumer) {
            $consumer->mobile = $this->fieldDeskService->sanitizeMobile($validated['mobile']) ?: trim($validated['mobile']);
            $consumer->save();
        }

        // Save GPS coordinates to consumer if requested
        $saveToConsumer = $request->boolean('save_to_consumer', true);
        if (isset($validated['latitude']) && isset($validated['longitude']) && $consumer && $saveToConsumer) {
            $consumer->latitude = (float) $validated['latitude'];
            $consumer->longitude = (float) $validated['longitude'];
            $consumer->location_accuracy = isset($validated['location_accuracy']) && is_numeric($validated['location_accuracy']) ? (float) $validated['location_accuracy'] : null;
            $consumer->location_updated_at = now();
            $consumer->save();
        }

        $action = new FieldDeskAction;
        $action->user_id = $userId;
        $action->ca_number = trim($validated['ca_number']);
        $action->consumer_account_id = $consumer?->id;
        $action->category_id = (int) $validated['category_id'];
        $action->priority = $validated['priority'] ?? 'normal';
        $action->target_date = $validated['target_date'];
        $action->original_target_date = $validated['target_date'];
        $action->target_amount = isset($validated['target_amount']) && $validated['target_amount'] !== '' ? (float) $validated['target_amount'] : null;
        $action->collected_amount = 0.00;
        $action->payment_mode = $validated['payment_mode'] ?? null;
        $action->private_note = $validated['private_note'] ?? null;
        $action->latitude = isset($validated['latitude']) ? (float) $validated['latitude'] : null;
        $action->longitude = isset($validated['longitude']) ? (float) $validated['longitude'] : null;
        $action->location_accuracy = isset($validated['location_accuracy']) && is_numeric($validated['location_accuracy']) ? (float) $validated['location_accuracy'] : null;
        $action->status = 'open';
        $action->reschedule_count = 0;
        $action->mru_id = $validated['mru_id'] ?? $consumer?->mru_id;
        $action->billing_month = $validated['billing_month'] ?? null;
        $action->billing_year = $validated['billing_year'] ?? null;
        $action->save();

        // Log initial activity
        $this->fieldDeskService->logActivity(
            $action,
            'created',
            'Action registered in FieldDesk',
            ['target_date' => $action->target_date->toDateString()],
            $userId
        );

        $action->load(['category', 'consumerAccount', 'mru']);

        return response()->json([
            'success' => true,
            'message' => 'Action created successfully in FieldDesk',
            'action' => array_merge($action->toArray(), [
                'effective_latitude' => $action->effective_latitude,
                'effective_longitude' => $action->effective_longitude,
                'location_accuracy' => $action->effective_accuracy,
                'map_link' => $action->map_link,
            ]),
        ], 201);
    }

    /**
     * Show a single FieldDesk action with full activity timeline history.
     */
    public function show(int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        $action = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('id', $id)
            ->with(['category', 'consumerAccount', 'mru', 'activities'])
            ->firstOrFail();

        $activities = $action->activities->map(function ($act) {
            return [
                'id' => $act->id,
                'action_type' => $act->action_type,
                'note' => $act->note,
                'old_date' => $act->old_date?->format('d M Y'),
                'new_date' => $act->new_date?->format('d M Y'),
                'amount_recorded' => $act->amount_recorded,
                'metadata' => $act->metadata,
                'created_at' => $act->created_at?->format('d M Y, h:i A'),
            ];
        });

        $mobile = $this->fieldDeskService->resolveConsumerMobile($action);
        $waLink = $this->fieldDeskService->generateWhatsAppLink($action);
        $waText = $this->fieldDeskService->generateWhatsAppText($action);

        return response()->json([
            'success' => true,
            'action' => array_merge($action->toArray(), [
                'effective_latitude' => $action->effective_latitude,
                'effective_longitude' => $action->effective_longitude,
                'location_accuracy' => $action->effective_accuracy,
                'map_link' => $action->map_link,
            ]),
            'mobile' => $mobile,
            'consumer' => $action->consumerAccount ? [
                'id' => $action->consumerAccount->id,
                'name' => $action->consumerAccount->consumer_name,
                'mobile' => $action->consumerAccount->mobile,
                'latitude' => $action->consumerAccount->latitude ? (float) $action->consumerAccount->latitude : null,
                'longitude' => $action->consumerAccount->longitude ? (float) $action->consumerAccount->longitude : null,
                'location_accuracy' => $action->consumerAccount->location_accuracy ? (float) $action->consumerAccount->location_accuracy : null,
                'location_updated_at' => $action->consumerAccount->location_updated_at?->format('d M Y, h:i A'),
                'map_link' => $action->consumerAccount->map_link,
            ] : null,
            'whatsapp_link' => $waLink,
            'whatsapp_text' => $waText,
            'activities' => $activities,
        ]);
    }

    /**
     * Update an existing FieldDesk action.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        $action = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:field_desk_categories,id'],
            'target_date' => ['sometimes', 'date'],
            'priority' => ['sometimes', 'in:urgent,high,normal,low'],
            'target_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_mode' => ['nullable', 'string', 'max:50'],
            'private_note' => ['nullable', 'string', 'max:1000'],
            'mru_id' => ['nullable', 'integer', 'exists:mrus,id'],
            'status' => ['sometimes', 'in:open,completed,rescheduled,cancelled'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric', 'min:0'],
            'save_to_consumer' => ['nullable', 'boolean'],
            'mobile' => ['nullable', 'string', 'max:20'],
        ]);

        $oldDate = $action->target_date ? Carbon::parse($action->target_date)->toDateString() : null;
        $newDate = isset($validated['target_date']) ? Carbon::parse($validated['target_date'])->toDateString() : $oldDate;

        $action->fill($validated);

        if (array_key_exists('latitude', $validated)) {
            $action->latitude = $validated['latitude'] !== null ? (float) $validated['latitude'] : null;
        }
        if (array_key_exists('longitude', $validated)) {
            $action->longitude = $validated['longitude'] !== null ? (float) $validated['longitude'] : null;
        }
        if (array_key_exists('location_accuracy', $validated)) {
            $action->location_accuracy = isset($validated['location_accuracy']) && is_numeric($validated['location_accuracy']) ? (float) $validated['location_accuracy'] : null;
        }

        // Also update consumer if mobile or coordinates passed
        $consumer = $action->consumerAccount;
        if (! empty($validated['mobile']) && $consumer) {
            $consumer->mobile = $this->fieldDeskService->sanitizeMobile($validated['mobile']) ?: trim($validated['mobile']);
            $consumer->save();
        }

        $saveToConsumer = $request->boolean('save_to_consumer', true);
        if (isset($validated['latitude']) && isset($validated['longitude']) && $consumer && $saveToConsumer) {
            $consumer->latitude = (float) $validated['latitude'];
            $consumer->longitude = (float) $validated['longitude'];
            $consumer->location_accuracy = isset($validated['location_accuracy']) && is_numeric($validated['location_accuracy']) ? (float) $validated['location_accuracy'] : null;
            $consumer->location_updated_at = now();
            $consumer->save();
        }

        // Check if date changed
        if ($oldDate && $newDate && $oldDate !== $newDate) {
            $action->reschedule_count = (int) $action->reschedule_count + 1;
            if ($action->status === 'open') {
                $action->status = 'rescheduled';
            }
            $action->save();

            $this->fieldDeskService->logActivity(
                $action,
                'rescheduled',
                "Target date adjusted from {$oldDate} to {$newDate}",
                ['old_date' => $oldDate, 'new_date' => $newDate],
                $userId
            );
        } else {
            if (isset($validated['status']) && $validated['status'] === 'completed' && empty($action->resolved_at)) {
                $action->resolved_at = now();
            }
            $action->save();

            $activityType = (isset($validated['status']) && $validated['status'] === 'completed')
                ? 'completed'
                : 'updated';

            $this->fieldDeskService->logActivity(
                $action,
                $activityType,
                $activityType === 'completed' ? 'Action marked as completed / resolved' : 'Action details modified',
                [],
                $userId
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Action updated successfully',
            'action' => array_merge($action->fresh(['category', 'consumerAccount', 'mru'])->toArray(), [
                'effective_latitude' => $action->effective_latitude,
                'effective_longitude' => $action->effective_longitude,
                'location_accuracy' => $action->effective_accuracy,
                'map_link' => $action->map_link,
            ]),
        ]);
    }

    /**
     * Delete or cancel an action.
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        $action = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $action->delete();

        return response()->json([
            'success' => true,
            'message' => 'Action deleted successfully',
        ]);
    }

    /**
     * Quick reschedule an action (+2d, +5d, custom days).
     */
    public function quickReschedule(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        $action = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $updatedAction = $this->fieldDeskService->quickReschedule(
            $action,
            (int) $validated['days'],
            $validated['reason'] ?? null,
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => "Action snoozed +{$validated['days']} days",
            'action' => $updatedAction,
        ]);
    }

    /**
     * Complete / Resolve an action with optional collected amount and note.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        $action = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $collectedAmount = isset($validated['collected_amount']) ? (float) $validated['collected_amount'] : null;
        $updatedAction = $this->fieldDeskService->completeAction(
            $action,
            $collectedAmount,
            $validated['note'] ?? null,
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Action marked as completed',
            'action' => $updatedAction,
        ]);
    }

    /**
     * Log an ad-hoc field activity touch (e.g. Call made, WhatsApp sent, Remark).
     */
    public function logActivity(Request $request, int $id): JsonResponse
    {
        $userId = (int) Auth::id();

        $action = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'action_type' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
            'metadata' => ['nullable', 'array'],
        ]);

        $activity = $this->fieldDeskService->logActivity(
            $action,
            $validated['action_type'],
            $validated['note'] ?? null,
            $validated['metadata'] ?? [],
            $userId
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity logged',
            'activity' => $activity,
        ]);
    }

    /**
     * Lightweight endpoint for Main Dashboard Quick Pop-up bridge.
     */
    public function forConsumer(Request $request, string $ca): JsonResponse
    {
        $userId = (int) Auth::id();
        $ca = trim($ca);

        $activeAction = FieldDeskAction::withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->where('ca_number', $ca)
            ->whereIn('status', ['open', 'rescheduled'])
            ->with(['category', 'consumerAccount', 'mru'])
            ->orderBy('target_date', 'asc')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END ASC")
            ->first();

        $consumer = ConsumerAccount::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('ca_number', $ca)
            ->with('mru')
            ->first();

        if (! $consumer) {
            $bill = BillRecord::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('ca_number', $ca)
                ->with('mru')
                ->latest()
                ->first();

            if ($bill) {
                $consumer = (object) [
                    'consumer_name' => $bill->consumer_name,
                    'meter_no' => $bill->meter_no,
                    'mobile' => null,
                    'mru' => $bill->mru,
                    'baseline_amount' => $bill->total_amount,
                ];
            }
        }

        $waLink = $activeAction ? $this->fieldDeskService->generateWhatsAppLink($activeAction) : null;
        $waText = $activeAction ? $this->fieldDeskService->generateWhatsAppText($activeAction) : null;
        $mobile = $activeAction ? $this->fieldDeskService->resolveConsumerMobile($activeAction) : ($consumer ? $this->fieldDeskService->sanitizeMobile($consumer->mobile) : null);

        return response()->json([
            'success' => true,
            'ca_number' => $ca,
            'consumer' => $consumer ? [
                'id' => $consumer->id ?? null,
                'name' => $consumer->consumer_name,
                'meter_no' => $consumer->meter_no,
                'mobile' => $consumer->mobile,
                'clean_mobile' => $mobile,
                'mru_code' => $consumer->mru?->code,
                'mru_name' => $consumer->mru?->name,
                'baseline_amount' => $consumer->baseline_amount,
                'latitude' => isset($consumer->latitude) && $consumer->latitude !== null ? (float) $consumer->latitude : null,
                'longitude' => isset($consumer->longitude) && $consumer->longitude !== null ? (float) $consumer->longitude : null,
                'location_accuracy' => isset($consumer->location_accuracy) && $consumer->location_accuracy !== null ? (float) $consumer->location_accuracy : null,
                'location_updated_at' => isset($consumer->location_updated_at) && $consumer->location_updated_at ? Carbon::parse($consumer->location_updated_at)->format('d M Y, h:i A') : null,
                'map_link' => (isset($consumer->latitude) && isset($consumer->longitude) && $consumer->latitude && $consumer->longitude)
                    ? "https://www.google.com/maps?q={$consumer->latitude},{$consumer->longitude}"
                    : null,
            ] : null,
            'action' => $activeAction ? [
                'id' => $activeAction->id,
                'category_id' => $activeAction->category_id,
                'category_code' => $activeAction->category?->code,
                'category_name' => $activeAction->category?->name,
                'category_icon' => $activeAction->category?->icon,
                'category_color' => $activeAction->category?->color,
                'priority' => $activeAction->priority,
                'status' => $activeAction->status,
                'target_date' => $activeAction->target_date?->toDateString(),
                'target_date_formatted' => $activeAction->target_date?->format('d M Y'),
                'is_due_today' => $activeAction->isDueToday(),
                'is_overdue' => $activeAction->isOverdue(),
                'is_upcoming' => $activeAction->isUpcoming(),
                'target_amount' => (float) ($activeAction->target_amount ?? 0),
                'collected_amount' => (float) ($activeAction->collected_amount ?? 0),
                'remaining_amount' => $activeAction->remaining_amount,
                'payment_mode' => $activeAction->payment_mode,
                'private_note' => $activeAction->private_note,
                'reschedule_count' => (int) $activeAction->reschedule_count,
                'latitude' => $activeAction->effective_latitude,
                'longitude' => $activeAction->effective_longitude,
                'location_accuracy' => $activeAction->effective_accuracy,
                'map_link' => $activeAction->map_link,
                'whatsapp_link' => $waLink,
                'whatsapp_text' => $waText,
                'clean_mobile' => $mobile,
            ] : null,
        ]);
    }

    /**
     * Update consumer mobile number and/or GPS coordinates directly.
     */
    public function updateConsumerContact(Request $request, string $ca): JsonResponse
    {
        $userId = (int) Auth::id();
        $ca = trim($ca);

        $validated = $request->validate([
            'mobile' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $consumer = ConsumerAccount::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->where('ca_number', $ca)
            ->first();

        if (! $consumer) {
            $bill = BillRecord::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('ca_number', $ca)
                ->latest()
                ->first();

            if ($bill) {
                $consumer = ConsumerAccount::create([
                    'user_id' => $userId,
                    'ca_number' => $ca,
                    'mru_id' => $bill->mru_id,
                    'consumer_name' => $bill->consumer_name ?: ('Consumer '.$ca),
                    'meter_no' => $bill->meter_no,
                    'tariff_category' => $bill->tariff_category ?? 'DS-II',
                    'billing_basis' => $bill->billing_basis ?? 'OK',
                    'baseline_amount' => $bill->total_amount,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Consumer account not found for CA: '.$ca,
                ], 404);
            }
        }

        if (array_key_exists('mobile', $validated)) {
            $consumer->mobile = ! empty($validated['mobile'])
                ? ($this->fieldDeskService->sanitizeMobile($validated['mobile']) ?: trim($validated['mobile']))
                : null;
        }

        if (array_key_exists('latitude', $validated) && array_key_exists('longitude', $validated)) {
            if ($validated['latitude'] !== null && $validated['longitude'] !== null) {
                $consumer->latitude = (float) $validated['latitude'];
                $consumer->longitude = (float) $validated['longitude'];
                $consumer->location_accuracy = isset($validated['location_accuracy']) && is_numeric($validated['location_accuracy'])
                    ? (float) $validated['location_accuracy']
                    : null;
                $consumer->location_updated_at = now();
            } else {
                $consumer->latitude = null;
                $consumer->longitude = null;
                $consumer->location_accuracy = null;
                $consumer->location_updated_at = null;
            }
        }

        $consumer->save();

        return response()->json([
            'success' => true,
            'message' => 'Consumer contact & location updated successfully',
            'consumer' => [
                'id' => $consumer->id,
                'ca_number' => $consumer->ca_number,
                'mobile' => $consumer->mobile,
                'clean_mobile' => $this->fieldDeskService->sanitizeMobile($consumer->mobile),
                'latitude' => $consumer->latitude ? (float) $consumer->latitude : null,
                'longitude' => $consumer->longitude ? (float) $consumer->longitude : null,
                'location_accuracy' => $consumer->location_accuracy ? (float) $consumer->location_accuracy : null,
                'location_updated_at' => $consumer->location_updated_at?->format('d M Y, h:i A'),
                'map_link' => $consumer->map_link,
            ],
        ]);
    }

    /**
     * Retrieve all active FieldDesk categories for dropdowns.
     */
    public function getCategories(): JsonResponse
    {
        $categories = FieldDeskCategory::active()->ordered()->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }
}
