<?php

use App\Jobs\CheckMonthlyUsageSummaryJob;
use App\Services\Billing\SubscriptionLifecycleService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('subscriptions:process-lifecycle', function (SubscriptionLifecycleService $lifecycleService) {
    $this->info('Running daily subscription lifecycle processor...');
    $lifecycleService->runDailyLifecycleProcessor();
    $this->info('Daily subscription lifecycle processor completed.');
})->purpose('Process expiring subscriptions, auto-renewals, and grace periods');

Artisan::command('usage:check-monthly-summaries', function () {
    $this->info('Dispatching monthly usage summary notification job...');
    CheckMonthlyUsageSummaryJob::dispatchSync();
    $this->info('Monthly usage summary notification job completed.');
})->purpose('Generate and notify agents of newly ready monthly usage summaries');

Artisan::command('referrals:process-payouts', function (\App\Services\Referral\ReferralService $referralService) {
    $this->info('Processing matured referral hold period payouts...');
    $count = $referralService->processExpiredHoldPeriods();
    $this->info("Processed and credited {$count} referral reward payouts.");
})->purpose('Release matured referral bonuses from hold period to referrer wallets');

// Schedules
Schedule::command('subscriptions:process-lifecycle')->dailyAt('00:05');
Schedule::command('referrals:process-payouts')->dailyAt('00:15');
Schedule::command('usage:check-monthly-summaries')->monthlyOn(1, '06:00');
