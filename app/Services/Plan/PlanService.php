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
            $end = now()->addMonths($duration->duration_months);

            $subscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'duration_months' => $duration->duration_months,
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
    public function migrateAgent(User $user, Plan $targetPlan, int $durationMonths = 1): AgentSubscription
    {
        return DB::transaction(function () use ($user, $targetPlan, $durationMonths) {
            $oldSub = $user->activeSubscription;
            $oldPlan = $oldSub?->plan;

            $duration = $targetPlan->durations()
                ->where('duration_months', $durationMonths)
                ->first();

            if (!$duration) {
                // Fallback default duration
                $duration = $targetPlan->durations()->first() ?? new PlanDuration([
                    'duration_months' => $durationMonths,
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
            $end = now()->addMonths($durationMonths);

            $newSubscription = AgentSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $targetPlan->id,
                'duration_months' => $durationMonths,
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
    protected function syncDurations(Plan $plan, array $durations, float $baseMonthlyPrice = 0.0): void
    {
        if (empty($durations)) {
            // Setup standard durations if empty
            $durations = [
                ['duration_months' => 1, 'discount_percent' => 0.00, 'final_price' => $baseMonthlyPrice * 1],
                ['duration_months' => 2, 'discount_percent' => 5.00, 'final_price' => ($baseMonthlyPrice * 2) * 0.95],
                ['duration_months' => 3, 'discount_percent' => 10.00, 'final_price' => ($baseMonthlyPrice * 3) * 0.90],
                ['duration_months' => 6, 'discount_percent' => 15.00, 'final_price' => ($baseMonthlyPrice * 6) * 0.85],
                ['duration_months' => 12, 'discount_percent' => 20.00, 'final_price' => ($baseMonthlyPrice * 12) * 0.80],
            ];
        }

        foreach ($durations as $d) {
            $months = (int) ($d['duration_months'] ?? 1);
            $discount = (float) ($d['discount_percent'] ?? 0.0);
            $finalPrice = isset($d['final_price']) ? (float)$d['final_price'] : ($baseMonthlyPrice * $months * (1 - ($discount / 100)));

            PlanDuration::updateOrCreate(
                ['plan_id' => $plan->id, 'duration_months' => $months],
                [
                    'discount_percent' => $discount,
                    'final_price' => max(0.0, $finalPrice),
                    'extra_mru_rate' => !empty($d['extra_mru_rate']) ? (float)$d['extra_mru_rate'] : null,
                    'extra_consumer_rate' => !empty($d['extra_consumer_rate']) ? (float)$d['extra_consumer_rate'] : null,
                ]
            );
        }
    }
}
