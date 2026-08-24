<?php

namespace App\Services\Plan;

use App\Events\AgentPlanMigratedEvent;
use App\Events\AgentSubscribedEvent;
use App\Events\PlanCreatedEvent;
use App\Events\PlanDeletedEvent;
use App\Events\PlanUpdatedEvent;
use App\Models\AgentSubscription;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlanService
{
    /**
     * Create a new subscription plan with duration pricing.
     */
    public function createPlan(array $data, array $durations = []): Plan
    {
        return DB::transaction(function () use ($data, $durations) {
            $plan = Plan::create([
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'included_mrus' => (int) ($data['included_mrus'] ?? 0),
                'included_consumers' => (int) ($data['included_consumers'] ?? 0),
                'extra_mru_rate' => (float) ($data['extra_mru_rate'] ?? 0.0),
                'extra_consumer_rate' => (float) ($data['extra_consumer_rate'] ?? 0.0),
                'grace_period_days' => array_key_exists('grace_period_days', $data) ? ($data['grace_period_days'] !== null ? (int)$data['grace_period_days'] : null) : null,
                'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
            ]);

            $this->syncDurations($plan, $durations, (float)($data['base_price'] ?? 0.0));

            event(new PlanCreatedEvent($plan));

            return $plan->load('durations');
        });
    }

    /**
     * Update an existing subscription plan and its duration pricing.
     */
    public function updatePlan(Plan $plan, array $data, array $durations = []): Plan
    {
        return DB::transaction(function () use ($plan, $data, $durations) {
            $plan->update([
                'name' => trim($data['name'] ?? $plan->name),
                'description' => array_key_exists('description', $data) ? $data['description'] : $plan->description,
                'included_mrus' => isset($data['included_mrus']) ? (int)$data['included_mrus'] : $plan->included_mrus,
                'included_consumers' => isset($data['included_consumers']) ? (int)$data['included_consumers'] : $plan->included_consumers,
                'extra_mru_rate' => isset($data['extra_mru_rate']) ? (float)$data['extra_mru_rate'] : $plan->extra_mru_rate,
                'extra_consumer_rate' => isset($data['extra_consumer_rate']) ? (float)$data['extra_consumer_rate'] : $plan->extra_consumer_rate,
                'grace_period_days' => array_key_exists('grace_period_days', $data) ? ($data['grace_period_days'] !== null ? (int)$data['grace_period_days'] : null) : $plan->grace_period_days,
                'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : $plan->is_active,
            ]);

            if (!empty($durations)) {
                $basePrice = (float) ($data['base_price'] ?? 0.0);
                $this->syncDurations($plan, $durations, $basePrice);
            }

            event(new PlanUpdatedEvent($plan));

            return $plan->fresh(['durations']);
        });
    }

    /**
     * Soft delete a plan (hides from new purchases, existing subscribers unaffected).
     */
    public function softDeletePlan(Plan $plan): bool
    {
        $result = $plan->delete();
        if ($result) {
            event(new PlanDeletedEvent($plan, false));
        }
        return (bool) $result;
    }

    /**
     * Force delete a plan.
     * Requires either an explicit migration target plan for active subscribers,
     * or an explicit force flag with confirmation.
     */
    public function forceDeletePlan(Plan $plan, ?int $migrationPlanId = null, bool $force = false): bool
    {
        return DB::transaction(function () use ($plan, $migrationPlanId, $force) {
            $activeSubscribersCount = $plan->subscriptions()
                ->where('status', 'active')
                ->where('billing_end', '>', now())
                ->count();

            if ($activeSubscribersCount > 0) {
                if ($migrationPlanId) {
                    $targetPlan = Plan::findOrFail($migrationPlanId);
                    $subscriptions = $plan->subscriptions()
                        ->where('status', 'active')
                        ->where('billing_end', '>', now())
                        ->get();

                    foreach ($subscriptions as $sub) {
                        $this->migrateAgent($sub->user, $targetPlan, $sub->duration_months);
                    }
                } elseif (!$force) {
                    throw new InvalidArgumentException("Cannot force delete plan with {$activeSubscribersCount} active subscribers without a migration target plan or explicit force confirmation.");
                }
            }

            $plan->durations()->delete();
            $result = $plan->forceDelete();

            event(new PlanDeletedEvent($plan, true));

            return (bool) $result;
        });
    }

    /**
     * Subscribe an agent/user to a plan, locking all pricing and quota snapshots.
     */
    public function subscribeAgent(User $user, Plan $plan, PlanDuration $duration): AgentSubscription
    {
        return DB::transaction(function () use ($user, $plan, $duration) {
            // Deactivate any existing active subscriptions for this user
            $user->subscriptions()
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $start = now();
            $end = $duration->calculateBillingEnd($start);
            $durationValue = $duration->duration_value ?: $duration->duration_months ?: 1;
            $durationUnit = $duration->duration_unit ?: 'month';
            $durationMonths = $durationUnit === 'month' ? $durationValue : max(1, (int)ceil($durationValue / 30));

            $subscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'duration_unit' => $durationUnit,
                'duration_value' => $durationValue,
                'duration_months' => $durationMonths,
                'base_price_paid' => $duration->final_price,
                'included_mrus_locked' => $plan->included_mrus,
                'included_consumers_locked' => $plan->included_consumers,
                'extra_mru_rate_locked' => $duration->extra_mru_rate ?? $plan->extra_mru_rate,
                'extra_consumer_rate_locked' => $duration->extra_consumer_rate ?? $plan->extra_consumer_rate,
                'billing_start' => $start,
                'billing_end' => $end,
                'status' => 'active',
            ]);

            event(new AgentSubscribedEvent($subscription));

            return $subscription;
        });
    }

    /**
     * Manually migrate an agent to a new plan.
     */
    public function migrateAgent(User $user, Plan $targetPlan, int $durationValue = 1, string $durationUnit = 'month'): AgentSubscription
    {
        return DB::transaction(function () use ($user, $targetPlan, $durationValue, $durationUnit) {
            $oldSub = $user->activeSubscription;
            $oldPlan = $oldSub?->plan;

            $duration = $targetPlan->durations()
                ->where('duration_unit', $durationUnit)
                ->where('duration_value', $durationValue)
                ->first();

            if (!$duration) {
                // Fallback search by duration_months
                $duration = $targetPlan->durations()
                    ->where('duration_months', $durationValue)
                    ->first() ?? $targetPlan->durations()->first() ?? new PlanDuration([
                        'duration_unit' => $durationUnit,
                        'duration_value' => $durationValue,
                        'duration_months' => $durationUnit === 'month' ? $durationValue : 1,
                        'discount_percent' => 0.00,
                        'final_price' => 0.00,
                        'extra_mru_rate' => $targetPlan->extra_mru_rate,
                        'extra_consumer_rate' => $targetPlan->extra_consumer_rate,
                    ]);
            }

            if ($oldSub) {
                $oldSub->update(['status' => 'migrated']);
            }

            $start = now();
            $end = $duration->calculateBillingEnd($start);
            $val = $duration->duration_value ?: $duration->duration_months ?: 1;
            $unit = $duration->duration_unit ?: 'month';
            $months = $unit === 'month' ? $val : max(1, (int)ceil($val / 30));

            $newSubscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $targetPlan->id,
                'duration_unit' => $unit,
                'duration_value' => $val,
                'duration_months' => $months,
                'base_price_paid' => $duration->final_price,
                'included_mrus_locked' => $targetPlan->included_mrus,
                'included_consumers_locked' => $targetPlan->included_consumers,
                'extra_mru_rate_locked' => $duration->extra_mru_rate ?? $targetPlan->extra_mru_rate,
                'extra_consumer_rate_locked' => $duration->extra_consumer_rate ?? $targetPlan->extra_consumer_rate,
                'billing_start' => $start,
                'billing_end' => $end,
                'status' => 'active',
            ]);

            event(new AgentPlanMigratedEvent($newSubscription, $oldPlan));

            return $newSubscription;
        });
    }

    /**
     * Synchronize duration pricing table for a plan.
     */
    public function syncDurations(Plan $plan, array $durations, float $baseMonthlyPrice = 0.0): void
    {
        if (empty($durations)) {
            // Setup standard durations if empty
            $durations = [
                ['duration_unit' => 'month', 'duration_value' => 1, 'duration_months' => 1, 'discount_percent' => 0.00, 'final_price' => $baseMonthlyPrice * 1, 'is_active' => true],
                ['duration_unit' => 'month', 'duration_value' => 2, 'duration_months' => 2, 'discount_percent' => 5.00, 'final_price' => ($baseMonthlyPrice * 2) * 0.95, 'is_active' => true],
                ['duration_unit' => 'month', 'duration_value' => 3, 'duration_months' => 3, 'discount_percent' => 10.00, 'final_price' => ($baseMonthlyPrice * 3) * 0.90, 'is_active' => true],
                ['duration_unit' => 'month', 'duration_value' => 6, 'duration_months' => 6, 'discount_percent' => 15.00, 'final_price' => ($baseMonthlyPrice * 6) * 0.85, 'is_active' => true],
                ['duration_unit' => 'month', 'duration_value' => 12, 'duration_months' => 12, 'discount_percent' => 20.00, 'final_price' => ($baseMonthlyPrice * 12) * 0.80, 'is_active' => true],
            ];
        }

        $processedIds = [];

        foreach ($durations as $d) {
            $unit = in_array($d['duration_unit'] ?? '', ['day', 'month']) ? $d['duration_unit'] : 'month';
            $val = (int) ($d['duration_value'] ?? $d['duration_months'] ?? 1);
            if ($val <= 0) $val = 1;

            $months = $unit === 'month' ? $val : max(1, (int)ceil($val / 30));
            $discount = (float) ($d['discount_percent'] ?? 0.0);

            if (isset($d['final_price']) && $d['final_price'] !== '') {
                $finalPrice = (float) $d['final_price'];
            } else {
                if ($unit === 'day') {
                    $finalPrice = ($baseMonthlyPrice / 30) * $val * (1 - ($discount / 100));
                } else {
                    $finalPrice = $baseMonthlyPrice * $val * (1 - ($discount / 100));
                }
            }

            $isActive = array_key_exists('is_active', $d) ? (bool)$d['is_active'] : true;
            $name = !empty($d['name']) ? trim($d['name']) : null;

            // Search by id if passed, or by (plan_id, duration_unit, duration_value)
            $existing = null;
            if (!empty($d['id'])) {
                $existing = PlanDuration::where('plan_id', $plan->id)->where('id', $d['id'])->first();
            }
            if (!$existing) {
                $existing = PlanDuration::where('plan_id', $plan->id)
                    ->where('duration_unit', $unit)
                    ->where('duration_value', $val)
                    ->first();
            }

            if ($existing) {
                $existing->update([
                    'duration_unit' => $unit,
                    'duration_value' => $val,
                    'duration_months' => $months,
                    'name' => $name,
                    'discount_percent' => $discount,
                    'final_price' => max(0.0, $finalPrice),
                    'extra_mru_rate' => !empty($d['extra_mru_rate']) ? (float)$d['extra_mru_rate'] : null,
                    'extra_consumer_rate' => !empty($d['extra_consumer_rate']) ? (float)$d['extra_consumer_rate'] : null,
                    'is_active' => $isActive,
                ]);
                $processedIds[] = $existing->id;
            } else {
                $created = PlanDuration::create([
                    'plan_id' => $plan->id,
                    'duration_unit' => $unit,
                    'duration_value' => $val,
                    'duration_months' => $months,
                    'name' => $name,
                    'discount_percent' => $discount,
                    'final_price' => max(0.0, $finalPrice),
                    'extra_mru_rate' => !empty($d['extra_mru_rate']) ? (float)$d['extra_mru_rate'] : null,
                    'extra_consumer_rate' => !empty($d['extra_consumer_rate']) ? (float)$d['extra_consumer_rate'] : null,
                    'is_active' => $isActive,
                ]);
                $processedIds[] = $created->id;
            }
        }

        // Delete any duration rows that were explicitly removed by the admin
        if (!empty($processedIds)) {
            PlanDuration::where('plan_id', $plan->id)->whereNotIn('id', $processedIds)->delete();
        }
    }
}
