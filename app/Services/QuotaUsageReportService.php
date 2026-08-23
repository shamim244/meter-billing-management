<?php

namespace App\Services;

use App\Models\AgentSubscription;
use App\Models\BillingCycle;
use App\Models\Mru;
use App\Models\PlanOverageCharge;
use App\Models\User;
use Carbon\Carbon;

class QuotaUsageReportService
{
    /**
     * Get monthly quota usage and overage summary for a single Agent.
     */
    public function getMonthlyQuotaUsage(int $userId, int $month, int $year): array
    {
        $subscription = AgentSubscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'grace_period'])
            ->latest('id')
            ->first();

        $includedMrus = $subscription ? (int) $subscription->included_mrus_locked : 0;
        $includedConsumers = $subscription ? (int) $subscription->included_consumers_locked : 0;

        // Active MRUs
        $activeMrus = Mru::where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        $extraMrus = max(0, $activeMrus - $includedMrus);

        // Billing cycles in period
        $cycles = BillingCycle::where('user_id', $userId)
            ->where('cycle_month', $month)
            ->where('cycle_year', $year)
            ->get();

        $consumersUsed = $cycles->sum('consumer_count_at_creation');
        $extraConsumers = $cycles->sum('extra_consumer_count');

        // Sum overage charges from plan_overage_charges table
        $overageCharges = $this->getOverageChargeTotals($userId, $month, $year);

        return [
            'month' => $month,
            'year' => $year,
            'subscription' => $subscription ? [
                'plan_name' => $subscription->plan?->name ?? 'Custom Plan',
                'status' => $subscription->status,
                'starts_at' => $subscription->billing_start?->format('Y-m-d'),
                'expires_at' => $subscription->billing_end?->format('Y-m-d'),
            ] : null,
            'mru' => [
                'included' => $includedMrus,
                'used' => $activeMrus,
                'extra' => $extraMrus,
                'is_over_quota' => $activeMrus > $includedMrus,
                'extra_rate' => $subscription ? (float) $subscription->extra_mru_rate_locked : 0.0,
            ],
            'consumer' => [
                'included' => $includedConsumers,
                'used' => $consumersUsed,
                'extra' => $extraConsumers,
                'is_over_quota' => $consumersUsed > $includedConsumers,
                'extra_rate' => $subscription ? (float) $subscription->extra_consumer_rate_locked : 0.0,
            ],
            'overage_charges' => $overageCharges,
        ];
    }

    /**
     * Sum plan_overage_charges for a user and period split by charge_type.
     */
    public function getOverageChargeTotals(int $userId, int $month, int $year): array
    {
        $charges = PlanOverageCharge::where('user_id', $userId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->get();

        $mruTotal = (float) $charges->where('charge_type', 'mru')->sum('amount');
        $consumerTotal = (float) $charges->where('charge_type', 'consumer')->sum('amount');

        return [
            'mru_charges' => $mruTotal,
            'consumer_charges' => $consumerTotal,
            'total_charges' => $mruTotal + $consumerTotal,
            'count' => $charges->count(),
        ];
    }

    /**
     * Get multi-month usage trend (defaults to last 6 months).
     */
    public function getUsageTrend(int $userId, int $monthsBack = 6): array
    {
        $trend = [];
        $currentDate = Carbon::now()->startOfMonth();

        for ($i = 0; $i < $monthsBack; $i++) {
            $date = $currentDate->copy()->subMonths($i);
            $m = (int) $date->month;
            $y = (int) $date->year;

            $usage = $this->getMonthlyQuotaUsage($userId, $m, $y);

            $trend[] = [
                'month' => $m,
                'year' => $y,
                'label' => $date->format('M Y'),
                'mru_included' => $usage['mru']['included'],
                'mru_used' => $usage['mru']['used'],
                'mru_extra' => $usage['mru']['extra'],
                'consumer_included' => $usage['consumer']['included'],
                'consumer_used' => $usage['consumer']['used'],
                'consumer_extra' => $usage['consumer']['extra'],
                'mru_charges' => $usage['overage_charges']['mru_charges'],
                'consumer_charges' => $usage['overage_charges']['consumer_charges'],
                'total_charges' => $usage['overage_charges']['total_charges'],
            ];
        }

        return array_reverse($trend);
    }

    /**
     * Get cross-Agent aggregate quota usage for Admin.
     */
    public function getAdminAggregateQuotaUsage(int $month, int $year, string $sortBy = 'overage_spend'): array
    {
        // Get all agents (non-admin users)
        $agents = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))->get();

        $rows = [];
        $totals = [
            'total_mrus_used' => 0,
            'total_consumers_used' => 0,
            'total_mru_charges' => 0.0,
            'total_consumer_charges' => 0.0,
            'total_overage_spend' => 0.0,
        ];

        foreach ($agents as $agent) {
            $usage = $this->getMonthlyQuotaUsage($agent->id, $month, $year);

            $row = [
                'user_id' => $agent->id,
                'name' => $agent->name,
                'email' => $agent->email,
                'plan_name' => $usage['subscription']['plan_name'] ?? 'No Plan',
                'subscription_status' => $usage['subscription']['status'] ?? 'none',
                'mru_included' => $usage['mru']['included'],
                'mru_used' => $usage['mru']['used'],
                'mru_extra' => $usage['mru']['extra'],
                'consumer_included' => $usage['consumer']['included'],
                'consumer_used' => $usage['consumer']['used'],
                'consumer_extra' => $usage['consumer']['extra'],
                'mru_charges' => $usage['overage_charges']['mru_charges'],
                'consumer_charges' => $usage['overage_charges']['consumer_charges'],
                'overage_spend' => $usage['overage_charges']['total_charges'],
            ];

            $totals['total_mrus_used'] += $row['mru_used'];
            $totals['total_consumers_used'] += $row['consumer_used'];
            $totals['total_mru_charges'] += $row['mru_charges'];
            $totals['total_consumer_charges'] += $row['consumer_charges'];
            $totals['total_overage_spend'] += $row['overage_spend'];

            $rows[] = $row;
        }

        // Sorting
        usort($rows, function ($a, $b) use ($sortBy) {
            return match ($sortBy) {
                'overage_spend' => $b['overage_spend'] <=> $a['overage_spend'],
                'consumer_usage' => $b['consumer_used'] <=> $a['consumer_used'],
                'mru_usage' => $b['mru_used'] <=> $a['mru_used'],
                'name' => strcmp($a['name'], $b['name']),
                default => $b['overage_spend'] <=> $a['overage_spend'],
            };
        });

        return [
            'month' => $month,
            'year' => $year,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
