<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationDispatchService;
use App\Services\UsageSummaryService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckMonthlyUsageSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to check and notify agents of newly ready monthly reports.
     */
    public function handle(UsageSummaryService $summaryService, NotificationDispatchService $dispatcher): void
    {
        $targetDate = Carbon::now()->subMonth()->startOfMonth();
        $targetMonth = (int) $targetDate->month;
        $targetYear = (int) $targetDate->year;

        $agents = User::whereDoesntHave('roles', fn($q) => $q->where('name', 'admin'))->get();

        foreach ($agents as $agent) {
            $summary = $summaryService->getMonthlySummary($agent->id, $targetMonth, $targetYear);
            $billsProcessed = $summary['roi_summary']['bills_processed'] ?? 0;

            if ($billsProcessed === 0) {
                continue;
            }

            // Check if already notified for this month/year cycle
            $alreadyNotified = Notification::where('user_id', $agent->id)
                ->where('event_type', 'usage.monthly_summary_ready')
                ->whereJsonContains('data->month', (string) $targetMonth)
                ->whereJsonContains('data->year', (string) $targetYear)
                ->exists();

            if (!$alreadyNotified) {
                $dispatcher->dispatch('usage.monthly_summary_ready', $agent, [
                    'month' => $targetMonth,
                    'year' => $targetYear,
                    'month_label' => $targetDate->format('F Y'),
                    'bills_processed' => number_format($billsProcessed),
                    'mrus_active' => $summary['roi_summary']['mrus_active'],
                    'data_coverage' => $summary['roi_summary']['data_coverage_percentage'],
                    'flagged_count' => $summary['roi_summary']['flagged_consumers_count'],
                ]);

                Log::info("[NotificationSystem] Dispatched Monthly Summary Ready notification to Agent #{$agent->id} for {$targetMonth}/{$targetYear}.");
            }
        }
    }
}
