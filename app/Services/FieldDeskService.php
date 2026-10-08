<?php

namespace App\Services;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\FieldDeskAction;
use App\Models\FieldDeskActivity;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FieldDeskService
{
    /**
     * Retrieve filtered and paginated FieldDesk actions agenda.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getFilteredAgenda(int $userId, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = FieldDeskAction::query()
            ->withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->with(['category', 'consumerAccount', 'mru', 'user']);

        // Timeline filter (today, overdue, upcoming, resolved, all)
        $timeline = $filters['timeline'] ?? 'all_active';
        $today = Carbon::today();

        match ($timeline) {
            'today' => $query->whereIn('status', ['open', 'rescheduled'])->whereDate('target_date', $today),
            'overdue' => $query->whereIn('status', ['open', 'rescheduled'])->whereDate('target_date', '<', $today),
            'upcoming' => $query->whereIn('status', ['open', 'rescheduled'])->whereDate('target_date', '>', $today),
            'resolved' => $query->where('status', 'completed'),
            'all' => null, // all actions regardless of status
            default => $query->whereIn('status', ['open', 'rescheduled']), // default: all active
        };

        // Category filter (by id or code)
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        } elseif (! empty($filters['category_code'])) {
            $query->whereHas('category', function (Builder $q) use ($filters) {
                $q->where('code', $filters['category_code']);
            });
        }

        // MRU filter
        if (! empty($filters['mru_id'])) {
            $query->where('mru_id', $filters['mru_id']);
        }

        // Priority filter
        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Specific status filter (if explicitly passed)
        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Search filter (CA, name, note, mobile)
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Ordering: Overdue and Due Today first, then ascending by target date
        if ($timeline === 'resolved') {
            $query->orderByDesc('resolved_at')->orderByDesc('updated_at');
        } else {
            $query->orderBy('target_date', 'asc')
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END ASC")
                ->orderByDesc('id');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get aggregate summary counts for user's FieldDesk dashboard.
     *
     * @return array{due_today: int, overdue: int, upcoming: int, resolved_this_month: int, total_active: int}
     */
    public function getSummaryCounts(int $userId, ?int $mruId = null): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $base = FieldDeskAction::query()
            ->withoutGlobalScopes()
            ->where('field_desk_actions.user_id', $userId)
            ->when($mruId, fn ($q) => $q->where('mru_id', $mruId));

        $activeQuery = (clone $base)->whereIn('status', ['open', 'rescheduled']);

        $dueToday = (clone $activeQuery)->whereDate('target_date', $today)->count();
        $overdue = (clone $activeQuery)->whereDate('target_date', '<', $today)->count();
        $upcoming = (clone $activeQuery)->whereDate('target_date', '>', $today)->count();
        $totalActive = (clone $activeQuery)->count();

        $resolvedThisMonth = (clone $base)
            ->where('status', 'completed')
            ->where(function ($q) use ($startOfMonth) {
                $q->where('resolved_at', '>=', $startOfMonth)
                    ->orWhere(function ($sub) use ($startOfMonth) {
                        $sub->whereNull('resolved_at')->where('updated_at', '>=', $startOfMonth);
                    });
            })
            ->count();

        return [
            'due_today' => $dueToday,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'resolved_this_month' => $resolvedThisMonth,
            'total_active' => $totalActive,
        ];
    }

    /**
     * Quick reschedule an action forward by specified number of days.
     */
    public function quickReschedule(
        FieldDeskAction $action,
        int $days,
        ?string $reason = null,
        ?int $userId = null
    ): FieldDeskAction {
        return DB::transaction(function () use ($action, $days, $reason, $userId) {
            $oldDate = $action->target_date ? Carbon::parse($action->target_date)->startOfDay() : Carbon::today();
            $baseDate = $oldDate->lt(Carbon::today()) ? Carbon::today() : $oldDate;
            $newDate = $baseDate->copy()->addDays($days);

            $action->target_date = $newDate->toDateString();
            $action->status = 'rescheduled';
            $action->reschedule_count = (int) $action->reschedule_count + 1;
            $action->save();

            FieldDeskActivity::create([
                'action_id' => $action->id,
                'user_id' => $userId ?? $action->user_id,
                'action_type' => 'rescheduled',
                'old_date' => $oldDate->toDateString(),
                'new_date' => $newDate->toDateString(),
                'note' => $reason ?: "Quick snoozed +{$days} days to {$newDate->format('d M Y')}",
                'metadata' => [
                    'days_added' => $days,
                    'reschedule_count' => $action->reschedule_count,
                ],
            ]);

            return $action->fresh(['category', 'consumerAccount', 'mru', 'activities']);
        });
    }

    /**
     * Mark an action as completed / resolved.
     */
    public function completeAction(
        FieldDeskAction $action,
        ?float $collectedAmount = null,
        ?string $note = null,
        ?int $userId = null
    ): FieldDeskAction {
        return DB::transaction(function () use ($action, $collectedAmount, $note, $userId) {
            $action->status = 'completed';
            $action->resolved_at = now();
            if ($collectedAmount !== null && $collectedAmount > 0) {
                $action->collected_amount = (float) $action->collected_amount + (float) $collectedAmount;
            }
            if ($note !== null) {
                $action->resolution_note = $note;
            }
            $action->save();

            FieldDeskActivity::create([
                'action_id' => $action->id,
                'user_id' => $userId ?? $action->user_id,
                'action_type' => 'completed',
                'amount_recorded' => $collectedAmount,
                'note' => $note ?: 'Marked as completed / resolved',
                'metadata' => [
                    'final_collected_total' => $action->collected_amount,
                ],
            ]);

            return $action->fresh(['category', 'consumerAccount', 'mru', 'activities']);
        });
    }

    /**
     * Log a touch activity (e.g. call made, note updated, whatsapp sent).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function logActivity(
        FieldDeskAction $action,
        string $type,
        ?string $note = null,
        array $metadata = [],
        ?int $userId = null
    ): FieldDeskActivity {
        return FieldDeskActivity::create([
            'action_id' => $action->id,
            'user_id' => $userId ?? $action->user_id,
            'action_type' => $type,
            'note' => $note,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Sanitize 10-digit Indian mobile number.
     */
    public function sanitizeMobile(?string $raw): ?string
    {
        if (empty($raw)) {
            return null;
        }

        $cleaned = preg_replace('/\D+/', '', $raw);
        $cleaned = ltrim($cleaned, '0');

        if (strlen($cleaned) === 12 && str_starts_with($cleaned, '91')) {
            $cleaned = substr($cleaned, 2);
        }

        return (strlen($cleaned) === 10 && preg_match('/^[6-9]\d{9}$/', $cleaned)) ? $cleaned : null;
    }

    /**
     * Resolve consumer mobile number from action or consumer account or note.
     */
    public function resolveConsumerMobile(FieldDeskAction $action): ?string
    {
        // 1. Direct from consumerAccount relation
        if ($action->consumerAccount && ! empty($action->consumerAccount->mobile)) {
            $clean = $this->sanitizeMobile($action->consumerAccount->mobile);
            if ($clean) {
                return $clean;
            }
        }

        // 2. Query consumer account by ca_number if not linked
        if (! $action->consumer_account_id) {
            $account = ConsumerAccount::withoutGlobalScopes()
                ->where('user_id', $action->user_id)
                ->where('ca_number', $action->ca_number)
                ->first();

            if ($account && ! empty($account->mobile)) {
                $clean = $this->sanitizeMobile($account->mobile);
                if ($clean) {
                    return $clean;
                }
            }
        }

        // 3. Look in private note for formatted 10-digit phone
        if (! empty($action->private_note) && preg_match('/(?:(?:\+?91[\s-]?)?[6-9]\d{4}[\s-]?\d{5})|(?:(?:\+?91[\s-]?)?[6-9](?:[\s-]?\d){9})/', $action->private_note, $matches)) {
            $clean = $this->sanitizeMobile($matches[0]);
            if ($clean) {
                return $clean;
            }
        }

        return null;
    }

    /**
     * Generate localized WhatsApp notification text for action.
     */
    public function generateWhatsAppText(FieldDeskAction $action): string
    {
        $consumerName = $action->consumerAccount?->consumer_name;
        if (! $consumerName || $consumerName === 'Consumer') {
            $billName = BillRecord::withoutGlobalScopes()
                ->where('user_id', $action->user_id)
                ->where('ca_number', $action->ca_number)
                ->latest()
                ->value('consumer_name');
            $consumerName = $billName ?: 'Consumer';
        }

        $ca = $action->ca_number;
        $workerName = ($action->relationLoaded('user') && $action->user)
            ? $action->user->name
            : (auth()->user()?->name ?? 'Bijli Vibhag Sahayak');

        $dateFormatted = $action->target_date ? Carbon::parse($action->target_date)->format('d M Y') : 'aaj';
        $dueAmount = ($action->collected_amount > 0 && $action->remaining_amount !== null)
            ? $action->remaining_amount
            : (float) ($action->target_amount ?? 0);
        $amountFormatted = number_format($dueAmount, 2);
        $categoryCode = $action->category?->code ?? 'general_note';

        return match ($categoryCode) {
            'payment_promise' => "Namaskar {$consumerName} ji, aapke NBPDCL bijli bill (CA: {$ca}) ka payment ₹{$amountFormatted} {$dateFormatted} ko scheduled tha. Kripya payment ready rakhein ya online pay karein: https://nbpdcl.co.in. Dhanyawad. — {$workerName}",
            'issue_correction' => "Namaskar {$consumerName} ji, aapke bijli connection (CA: {$ca}) ke issue ke sambandh me hamara follow-up scheduled hai. Target date: {$dateFormatted}. Kripya update ke liye sampark karein. — {$workerName}",
            'scheduled_visit' => "Namaskar {$consumerName} ji, aapke premise (CA: {$ca}) par bijli bill inspection ke liye hum {$dateFormatted} ko aane wale hain. Kripya uplabdh rahein. — {$workerName}",
            default => "Namaskar {$consumerName} ji, aapke NBPDCL bijli connection (CA: {$ca}) ke sambandh me hamara update/follow-up hai. Target date: {$dateFormatted}. Dhanyawad. — {$workerName}",
        };
    }

    /**
     * Generate 1-click WhatsApp wa.me deep link.
     */
    public function generateWhatsAppLink(FieldDeskAction $action): ?string
    {
        $mobile = $this->resolveConsumerMobile($action);
        if (! $mobile) {
            return null;
        }

        $text = $this->generateWhatsAppText($action);

        return "https://wa.me/91{$mobile}?text=".rawurlencode($text);
    }
}
