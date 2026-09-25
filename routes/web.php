<?php

use App\Http\Controllers\Admin\AdminApiHubController;
use App\Http\Controllers\Admin\AdminBackupController;
use App\Http\Controllers\Admin\AdminBillController;
use App\Http\Controllers\Admin\AdminCompressionController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailProviderController;
use App\Http\Controllers\Admin\AdminFailedNotificationController;
use App\Http\Controllers\Admin\AdminIssueController;
use App\Http\Controllers\Admin\AdminMailboxController;
use App\Http\Controllers\Admin\AdminMruController;
use App\Http\Controllers\Admin\AdminNotificationTemplateController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminPlanDurationController;
use App\Http\Controllers\Admin\AdminRateLimitController;
use App\Http\Controllers\Admin\AdminReferralController;
use App\Http\Controllers\Admin\AdminServerMigrationController;
use App\Http\Controllers\Admin\AdminShortcutController;
use App\Http\Controllers\Admin\AdminSubscriptionController;
use App\Http\Controllers\Admin\AdminTagController;
use App\Http\Controllers\Admin\AdminUsageReportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminWalletController;
use App\Http\Controllers\AgentReferralController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\IssueReportController;
use App\Http\Controllers\MruController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PdfManagerController;
use App\Http\Controllers\ProcessingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\UsageReportController;
use App\Http\Controllers\UserPanel\AgentBackupController;
use App\Http\Controllers\UserPanelController;
use App\Http\Controllers\WalletController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs/api', [DocsController::class, 'api'])->name('docs.api');

// Lightweight health-check ping for real-time connectivity detection & CSRF refresh
Route::get('/dashboard/ping', [DashboardController::class, 'ping'])->name('dashboard.ping');

// Agent & User Routes
Route::middleware(['auth', 'verified', 'active', 'subscription.not_suspended'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');
    Route::post('/dashboard/tuning', [DashboardController::class, 'saveTuning'])->name('dashboard.tuning');
    Route::post('/dashboard/tuning/reset', [DashboardController::class, 'resetTuning'])->name('dashboard.tuning.reset');
    Route::get('/bills/matrix/{ca_number}', [DashboardController::class, 'getMeterMatrix'])->name('bills.matrix');

    Route::post('/bills/process', [BillController::class, 'process'])->middleware('throttle:30,1')->name('bills.process');
    Route::post('/bills/download-single', [BillController::class, 'downloadSingle'])->middleware('throttle:60,1')->name('bills.download-single');
    Route::post('/bills/sync-missing', [BillController::class, 'syncMissing'])->middleware('throttle:30,1')->name('bills.sync-missing');
    Route::post('/bills/status', [BillController::class, 'updateStatus'])->name('bills.status');
    Route::post('/bills/remark', [BillController::class, 'saveRemark'])->name('bills.remark');
    Route::post('/bills/update-working-reading', [DashboardController::class, 'updateWorkingReading'])->name('bills.update-working-reading');
    Route::post('/bills/review-status', [DashboardController::class, 'updateReviewStatus'])->name('bills.review-status');
    Route::post('/bills/update-remark', [DashboardController::class, 'updateRemark'])->name('bills.update-remark');
    Route::post('/bills/tag', [DashboardController::class, 'updateTag'])->name('bills.tag');
    Route::post('/bills/bulk-project-readings', [DashboardController::class, 'bulkProjectReadings'])->name('bills.bulk-project-readings');
    Route::get('/user/shortcuts', [DashboardController::class, 'getShortcuts'])->name('user.shortcuts');
    Route::post('/user/shortcuts', [DashboardController::class, 'saveShortcuts'])->name('user.shortcuts.save');
    Route::post('/user/shortcuts/reset', [DashboardController::class, 'resetShortcuts'])->name('user.shortcuts.reset');
    Route::post('/bills/delete-pdf', [BillController::class, 'deletePdf'])->name('bills.delete-pdf');
    Route::get('/bills/pdf/{bill}', [BillController::class, 'viewPdf'])->name('bills.pdf');
    Route::get('/bills/export-zip', [BillController::class, 'exportZip'])->middleware('throttle:30,1')->name('bills.export-zip');
    Route::get('/bills/export-csv', [BillController::class, 'exportCsv'])->name('bills.export-csv');
    Route::get('/bills/history/{ca_number}', [BillController::class, 'history'])->name('bills.history');

    // Usage Tracking & ROI Reports
    Route::get('/reports', [UsageReportController::class, 'index'])->name('reports.usage');
    Route::get('/reports/status-tag', [UsageReportController::class, 'statusTagReport'])->name('reports.status_tag');
    Route::get('/reports/status-tag/export-csv', [UsageReportController::class, 'exportStatusTagCsv'])->name('reports.status_tag.export_csv');
    Route::get('/reports/quota', [UsageReportController::class, 'quotaReport'])->name('reports.quota');
    Route::get('/reports/flagged-estimates', [UsageReportController::class, 'flaggedEstimates'])->name('reports.flagged');

    // MRU Workspaces & Permanent Consumer Master Lists
    Route::get('/mrus', [MruController::class, 'index'])->name('mrus.index');
    Route::post('/mrus', [MruController::class, 'store'])->name('mrus.store');
    Route::post('/mrus/billing-cycle', [MruController::class, 'createBillingCycle'])->middleware('throttle:30,1')->name('mrus.billing-cycle');
    Route::get('/mrus/{mru}', [MruController::class, 'show'])->name('mrus.show');
    Route::put('/mrus/{mru}', [MruController::class, 'update'])->name('mrus.update');
    Route::delete('/mrus/{mru}', [MruController::class, 'destroy'])->name('mrus.destroy');
    Route::delete('/mrus/{mru}/sessions/{month}/{year}', [MruController::class, 'deleteSession'])->name('mrus.sessions.destroy');

    Route::post('/mrus/{mru}/consumers', [MruController::class, 'addConsumer'])->name('mrus.consumers.store');
    Route::post('/mrus/{mru}/consumers/import', [MruController::class, 'importConsumers'])->name('mrus.consumers.import');
    Route::put('/mrus/{mru}/consumers/{consumer}', [MruController::class, 'updateConsumer'])->name('mrus.consumers.update');
    Route::delete('/mrus/{mru}/consumers/{consumer}', [MruController::class, 'deleteConsumer'])->name('mrus.consumers.destroy');
    Route::get('/mrus/{mru}/consumers/export', [MruController::class, 'exportConsumers'])->name('mrus.consumers.export');
    Route::post('/mrus/{mru}/lock', [MruController::class, 'lock'])->name('mrus.lock');
    Route::post('/mrus/{mru}/unlock', [MruController::class, 'unlock'])->name('mrus.unlock');
    Route::post('/mrus/{mru}/start-billing', [MruController::class, 'startMonthlyBilling'])->middleware(['throttle:30,1', 'mru.not_locked'])->name('mrus.start-billing');
    Route::post('/mrus/{mru}/sync-missing', [MruController::class, 'syncMissingForMru'])->middleware(['throttle:30,1', 'mru.not_locked'])->name('mrus.sync-missing');

    // Data Processing Center (Download & Extraction Hub)
    Route::get('/processing', [ProcessingController::class, 'index'])->name('processing.index');
    Route::get('/processing/status', [ProcessingController::class, 'getStatus'])->name('processing.status');
    Route::post('/processing/create-cycle', [ProcessingController::class, 'createCycle'])->name('processing.create-cycle');
    Route::post('/processing/cycles/{cycle}/sync', [ProcessingController::class, 'syncCycle'])->name('processing.cycles.sync');
    Route::post('/processing/downloader', [ProcessingController::class, 'runDownloader'])->middleware('throttle:30,1')->name('processing.downloader');
    Route::post('/processing/parser', [ProcessingController::class, 'runParser'])->middleware('throttle:30,1')->name('processing.parser');
    Route::get('/processing/logs', [ProcessingController::class, 'getLogs'])->name('processing.logs');
    Route::post('/processing/logs/clear', [ProcessingController::class, 'clearLogs'])->name('processing.logs.clear');

    // Overall PDF Management Center
    Route::get('/pdf-manager', [PdfManagerController::class, 'index'])->name('pdf-manager.index');
    Route::post('/pdf-manager/batch-download', [PdfManagerController::class, 'batchDownload'])->middleware('throttle:30,1')->name('pdf-manager.batch-download');
    Route::post('/pdf-manager/batch-reparse', [PdfManagerController::class, 'batchReparse'])->middleware('throttle:30,1')->name('pdf-manager.batch-reparse');
    Route::post('/pdf-manager/batch-redownload', [PdfManagerController::class, 'batchRedownload'])->middleware('throttle:30,1')->name('pdf-manager.batch-redownload');
    Route::post('/pdf-manager/batch-delete', [PdfManagerController::class, 'batchDelete'])->name('pdf-manager.batch-delete');
    Route::get('/pdf-manager/health-check', [PdfManagerController::class, 'healthCheck'])->name('pdf-manager.health-check');
    Route::post('/pdf-manager/sync-storage', [PdfManagerController::class, 'syncStorage'])->name('pdf-manager.sync-storage');
    Route::post('/pdf-manager/purge-cycle', [PdfManagerController::class, 'purgeCyclePdfs'])->name('pdf-manager.purge-cycle');
    Route::post('/pdf-manager/upload', [PdfManagerController::class, 'upload'])->name('pdf-manager.upload');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Payments & Checkout Flow
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/validate-coupon', [PaymentController::class, 'validateCoupon'])->middleware('throttle:30,1')->name('payments.validate-coupon');
    Route::get('/payments/verify', [PaymentController::class, 'verify'])->name('payments.verify');

    // Agent Wallet Ledger & Dashboard
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/export', [WalletController::class, 'export'])->name('wallet.export');

    // Subscription Checkout & Purchase Flow
    Route::get('/subscription/quote/{plan}/{duration}', [SubscriptionCheckoutController::class, 'quote'])->name('subscription.quote');
    Route::get('/subscription/purchase/{plan}/{duration}', [SubscriptionCheckoutController::class, 'show'])->name('subscription.purchase');
    Route::post('/subscription/purchase/{plan}/{duration}/process', [SubscriptionCheckoutController::class, 'process'])->name('subscription.purchase.process');
    Route::post('/subscription/subscribe-wallet', [SubscriptionCheckoutController::class, 'subscribeWallet'])->name('subscription.subscribe_wallet');

    // User Panel & Operator Control Center (Overview, Subscription, Shortcuts, Preferences, Profile)
    Route::prefix('user-panel')->name('user-panel.')->group(function () {
        Route::get('/', [UserPanelController::class, 'index'])->name('index');
        Route::get('/subscription', [UserPanelController::class, 'subscription'])->name('subscription');
        Route::get('/shortcuts', [UserPanelController::class, 'shortcuts'])->name('shortcuts');
        Route::get('/preferences', [UserPanelController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [UserPanelController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/backup', [AgentBackupController::class, 'index'])->name('backup');
        Route::post('/backup/download', [AgentBackupController::class, 'download'])->name('backup.download');
        Route::get('/profile', [UserPanelController::class, 'profile'])->name('profile');
        Route::patch('/profile', [UserPanelController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [UserPanelController::class, 'updatePassword'])->name('password.update');
        Route::get('/issues', [UserPanelController::class, 'issues'])->name('issues');
        Route::get('/api-keys', [UserPanelController::class, 'apiKeys'])->name('api-keys');
        Route::post('/api-keys', [UserPanelController::class, 'storeApiKey'])->name('api-keys.store');
        Route::delete('/api-keys/{id}', [UserPanelController::class, 'revokeApiKey'])->name('api-keys.revoke');
    });
});

// Payment Gateway Webhooks (Unauthenticated, CSRF excluded)
Route::post('/webhooks/payments/razorpay', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.razorpay')
    ->withoutMiddleware([ValidateCsrfToken::class]);

Route::post('/webhooks/payments/cashfree', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.cashfree')
    ->withoutMiddleware([ValidateCsrfToken::class]);

Route::post('/webhooks/payments/pg', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.pg')
    ->withoutMiddleware([ValidateCsrfToken::class]);

// Admin Panel Routes (Protected by role:admin and active, auto-restoring admin if impersonating)
Route::middleware(['auth', 'admin.restore_impersonation', 'role:admin', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/export', [AdminUserController::class, 'exportCsv'])->name('users.export');
    Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::put('/users/{user}/password', [AdminUserController::class, 'updatePassword'])->name('users.update-password');
    Route::post('/users/{user}/grant-plan', [AdminUserController::class, 'grantPlan'])->name('users.grant-plan');
    Route::post('/users/{user}/override-quotas', [AdminUserController::class, 'overrideQuotas'])->name('users.override-quotas');
    Route::post('/users/{user}/send-notification', [AdminUserController::class, 'sendDirectNotification'])->name('users.send-notification');
    Route::post('/users/{user}/clean-pdfs', [AdminUserController::class, 'cleanPdfs'])->name('users.clean_pdfs');
    Route::post('/users/{user}/clean-mrus', [AdminUserController::class, 'cleanMrus'])->name('users.clean_mrus');
    Route::post('/users/{user}/clean-bills', [AdminUserController::class, 'cleanBills'])->name('users.clean_bills');
    Route::delete('/users/{user}/purge', [AdminUserController::class, 'purgeUser'])->name('users.purge');
    Route::post('/users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
    Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/update-quota', [AdminUserController::class, 'updateQuota'])->name('users.update-quota');

    // Admin Wallet Management & Ledger Adjustments
    Route::get('/wallets', [AdminWalletController::class, 'index'])->name('wallets.index');
    Route::get('/wallets/{user}', [AdminWalletController::class, 'show'])->name('wallets.show');
    Route::post('/wallets/{user}/adjust', [AdminWalletController::class, 'adjust'])->name('wallets.adjust');
    Route::post('/wallets/{user}/toggle-freeze', [AdminWalletController::class, 'toggleFreeze'])->name('wallets.toggle-freeze');
    Route::get('/wallets/{user}/export', [AdminWalletController::class, 'export'])->name('wallets.export');

    Route::get('/bills', [AdminBillController::class, 'index'])->name('bills.index');
    Route::get('/bills/engine-settings', [AdminBillController::class, 'engineSettings'])->name('bills.engine-settings');
    Route::post('/bills/engine-settings', [AdminBillController::class, 'updateEngineSettings'])->name('bills.engine-settings.update');
    Route::post('/bills/engine-settings/reset', [AdminBillController::class, 'resetEngineSettings'])->name('bills.engine-settings.reset');
    Route::post('/bills/engine-settings/diagnostic', [AdminBillController::class, 'testEngineDiagnostic'])->name('bills.engine-settings.diagnostic');

    // Admin Adaptive Compression Management & Live Diagnostics
    Route::get('/compression', [AdminCompressionController::class, 'index'])->name('compression.index');
    Route::post('/compression', [AdminCompressionController::class, 'update'])->name('compression.update');
    Route::post('/compression/reset', [AdminCompressionController::class, 'reset'])->name('compression.reset');
    Route::post('/compression/diagnostic', [AdminCompressionController::class, 'diagnostic'])->name('compression.diagnostic');

    Route::get('/mrus', [AdminMruController::class, 'index'])->name('mrus.index');
    Route::patch('/mrus/{mru}', [AdminMruController::class, 'update'])->name('mrus.update');

    Route::get('/shortcuts', [AdminShortcutController::class, 'index'])->name('shortcuts.index');
    Route::post('/shortcuts', [AdminShortcutController::class, 'update'])->name('shortcuts.update');
    Route::post('/shortcuts/reset-factory', [AdminShortcutController::class, 'resetToFactory'])->name('shortcuts.reset-factory');
    Route::post('/shortcuts/reset-all-users', [AdminShortcutController::class, 'resetAllUsers'])->name('shortcuts.reset-all-users');

    // Admin API & Automation Management Hub (Analytics, Toggles, Rate Limits, Policies, Keys)
    Route::get('/api-hub', [AdminApiHubController::class, 'index'])->name('api_hub.index');
    Route::post('/api-hub/settings', [AdminApiHubController::class, 'updateSettings'])->name('api_hub.settings.update');
    Route::post('/api-hub/reset', [AdminApiHubController::class, 'resetSettings'])->name('api_hub.settings.reset');
    Route::delete('/api-hub/keys/{apiKey}', [AdminApiHubController::class, 'revokeKey'])->name('api_hub.keys.revoke');
    Route::post('/api-hub/analytics/clear', [AdminApiHubController::class, 'clearAnalytics'])->name('api_hub.analytics.clear');

    // Backward-compatible routes for rate-limits
    Route::get('/rate-limits', [AdminRateLimitController::class, 'index'])->name('rate_limits.index');
    Route::post('/rate-limits', [AdminRateLimitController::class, 'update'])->name('rate_limits.update');
    Route::post('/rate-limits/reset', [AdminRateLimitController::class, 'reset'])->name('rate_limits.reset');

    // Admin Payment Verification Queue & Controls
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/manual', [AdminPaymentController::class, 'manual'])->name('payments.manual');
    Route::get('/payments/analytics', [AdminPaymentController::class, 'analytics'])->name('payments.analytics');
    Route::get('/payments/audit', [AdminPaymentController::class, 'audit'])->name('payments.audit');
    Route::get('/payments/simulator', [AdminPaymentController::class, 'simulator'])->name('payments.simulator');
    Route::post('/payments/simulator/checkout', [AdminPaymentController::class, 'simulateCheckout'])->name('payments.simulator.checkout');
    Route::post('/payments/simulator/webhook', [AdminPaymentController::class, 'simulateWebhook'])->name('payments.simulator.webhook');
    Route::post('/payments/simulator/seed', [AdminPaymentController::class, 'seedDemoPayments'])->name('payments.simulator.seed');
    Route::get('/payments/settings', [AdminPaymentController::class, 'settings'])->name('payments.settings');
    Route::post('/payments/settings', [AdminPaymentController::class, 'updateSettings'])->name('payments.settings.update');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/approve', [AdminPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('/payments/{payment}/refund', [AdminPaymentController::class, 'refund'])->name('payments.refund');

    // Admin Plan Management & Overage Audit
    Route::get('/plans/overage-charges', [AdminPlanController::class, 'overageCharges'])->name('plans.overage_charges');
    Route::post('/plans/migrate-agent', [AdminPlanController::class, 'migrateAgent'])->name('plans.migrate_agent');
    Route::post('/plans/unlock-mru/{mru}', [AdminPlanController::class, 'unlockMru'])->name('plans.unlock_mru');
    Route::get('/plans/{plan}/agents', [AdminPlanController::class, 'agents'])->name('plans.agents');

    // Admin Coupon Code Management Hub
    Route::get('/coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
    Route::get('/coupons/create', [AdminCouponController::class, 'create'])->name('coupons.create');
    Route::post('/coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
    Route::post('/coupons/bulk-deactivate', [AdminCouponController::class, 'bulkDeactivate'])->name('coupons.bulk-deactivate');
    Route::get('/coupons/{coupon}', [AdminCouponController::class, 'show'])->name('coupons.show');
    Route::get('/coupons/{coupon}/edit', [AdminCouponController::class, 'edit'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
    Route::patch('/coupons/{coupon}/toggle', [AdminCouponController::class, 'toggle'])->name('coupons.toggle');
    Route::delete('/coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');
    Route::post('/plans/{plan}/force-delete', [AdminPlanController::class, 'forceDelete'])->name('plans.force-delete');
    Route::resource('plans', AdminPlanController::class);

    // Dedicated Plan Duration Management Console
    Route::get('/plans/{plan}/durations', [AdminPlanDurationController::class, 'index'])->name('plans.durations.index');
    Route::post('/plans/{plan}/durations', [AdminPlanDurationController::class, 'store'])->name('plans.durations.store');
    Route::put('/plans/{plan}/durations/{duration}', [AdminPlanDurationController::class, 'update'])->name('plans.durations.update');
    Route::patch('/plans/{plan}/durations/{duration}/toggle', [AdminPlanDurationController::class, 'toggleActive'])->name('plans.durations.toggle');
    Route::delete('/plans/{plan}/durations/{duration}', [AdminPlanDurationController::class, 'destroy'])->name('plans.durations.destroy');

    // Admin Billing & Subscription Lifecycle Management
    Route::get('/subscriptions', [AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{subscription}/state-override', [AdminSubscriptionController::class, 'stateOverride'])->name('subscriptions.state_override');
    Route::get('/subscriptions/renewal-attempts', [AdminSubscriptionController::class, 'renewalAttempts'])->name('subscriptions.renewal_attempts');
    Route::get('/subscriptions/upgrade-logs', [AdminSubscriptionController::class, 'upgradeLogs'])->name('subscriptions.upgrade_logs');
    Route::post('/subscriptions/settings', [AdminSubscriptionController::class, 'updateSettings'])->name('subscriptions.update_settings');

    // Admin Bill Review Tags Manager
    Route::get('/tags', [AdminTagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [AdminTagController::class, 'update'])->name('tags.update');
    Route::post('/tags/store', [AdminTagController::class, 'store'])->name('tags.store');
    Route::delete('/tags/{code}', [AdminTagController::class, 'destroy'])->name('tags.destroy');
    Route::post('/tags/reset-factory', [AdminTagController::class, 'resetToFactory'])->name('tags.reset_factory');

    // Admin Usage Tracking & Platform Health Reports
    Route::get('/reports/usage', [AdminUsageReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/status-tag', [AdminUsageReportController::class, 'statusTagReport'])->name('reports.status_tag');
    Route::get('/reports/quota', [AdminUsageReportController::class, 'quotaUsageReport'])->name('reports.quota');
    Route::get('/reports/flagged-estimates', [AdminUsageReportController::class, 'flaggedEstimates'])->name('reports.flagged');

    // Admin Notification Engine (Registry, Templates, Failed Critical Queue)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        // Email Provider Instances Registry
        Route::get('/email-providers', [AdminEmailProviderController::class, 'index'])->name('email_providers.index');
        Route::post('/email-providers', [AdminEmailProviderController::class, 'store'])->name('email_providers.store');
        Route::put('/email-providers/{provider}', [AdminEmailProviderController::class, 'update'])->name('email_providers.update');
        Route::post('/email-providers/{provider}/toggle', [AdminEmailProviderController::class, 'toggle'])->name('email_providers.toggle');
        Route::post('/email-providers/{provider}/test-send', [AdminEmailProviderController::class, 'testSend'])->name('email_providers.test_send');
        Route::delete('/email-providers/{provider}', [AdminEmailProviderController::class, 'destroy'])->name('email_providers.destroy');

        // Message Templates & Priority Routing
        Route::get('/templates', [AdminNotificationTemplateController::class, 'index'])->name('templates.index');
        Route::put('/templates/{template}', [AdminNotificationTemplateController::class, 'update'])->name('templates.update');
        Route::post('/templates/preview', [AdminNotificationTemplateController::class, 'preview'])->name('templates.preview');
        Route::post('/templates/reset', [AdminNotificationTemplateController::class, 'resetToDefaults'])->name('templates.reset');

        // Failed Critical Queue
        Route::get('/failed-queue', [AdminFailedNotificationController::class, 'index'])->name('failed_queue');

        // Live Hostinger Mailbox Inspector
        Route::get('/mailbox', [AdminMailboxController::class, 'index'])->name('mailbox.index');
        Route::get('/mailbox/{uid}/content', [AdminMailboxController::class, 'showMessage'])->name('mailbox.show');
        Route::post('/mailbox/send', [AdminMailboxController::class, 'send'])->name('mailbox.send');
    });

    // Admin Refer & Earn System
    Route::prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/settings', [AdminReferralController::class, 'settings'])->name('settings');
        Route::post('/settings', [AdminReferralController::class, 'updateSettings'])->name('settings.update');
        Route::get('/activity', [AdminReferralController::class, 'activity'])->name('activity');
        Route::get('/top-referrers', [AdminReferralController::class, 'topReferrers'])->name('top_referrers');
        Route::patch('/coupon/{coupon}/toggle', [AdminReferralController::class, 'toggleCoupon'])->name('coupon.toggle');
        Route::post('/users/{user}/override', [AdminReferralController::class, 'updateAgentOverride'])->name('users.override');
    });

    // Admin System Backups & Disaster Recovery Cockpit
    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [AdminBackupController::class, 'index'])->name('index');
        Route::post('/', [AdminBackupController::class, 'store'])->name('store');
        Route::get('/{backup}/download', [AdminBackupController::class, 'download'])->name('download');
        Route::get('/{backup}/manifest', [AdminBackupController::class, 'manifest'])->name('manifest');
        Route::delete('/{backup}', [AdminBackupController::class, 'destroy'])->name('destroy');
        Route::post('/clean', [AdminBackupController::class, 'clean'])->name('clean');
    });

    // Admin Bug Tracker & AI Desk
    Route::prefix('issues')->name('issues.')->group(function () {
        Route::get('/', [AdminIssueController::class, 'index'])->name('index');
        Route::get('/{issue}', [AdminIssueController::class, 'show'])->name('show');
        Route::post('/{issue}/verify', [AdminIssueController::class, 'verify'])->name('verify');
        Route::post('/{issue}/spam', [AdminIssueController::class, 'markSpam'])->name('spam');
        Route::post('/{issue}/resolve', [AdminIssueController::class, 'resolve'])->name('resolve');
        Route::get('/{issue}/ai-prompt', [AdminIssueController::class, 'aiPrompt'])->name('ai_prompt');
    });

    // Admin Universal Server & Cloud Migration Cockpit
    Route::prefix('server-migration')->name('server_migration.')->group(function () {
        Route::get('/', [AdminServerMigrationController::class, 'index'])->name('index');
        Route::post('/export', [AdminServerMigrationController::class, 'export'])->name('export');
        Route::post('/inspect', [AdminServerMigrationController::class, 'inspect'])->name('inspect');
        Route::post('/import', [AdminServerMigrationController::class, 'import'])->name('import');
        Route::get('/download/{filename}', [AdminServerMigrationController::class, 'download'])->name('download');
        Route::delete('/{filename}', [AdminServerMigrationController::class, 'destroy'])->name('destroy');
    });
});

// Agent Referrals, Notifications & Preferences Routes (Protected by auth and active)
Route::middleware(['auth', 'active'])->group(function () {
    // User / Worker Issue & Bug Reporting & Live Tracking
    Route::post('/issues/report', [IssueReportController::class, 'store'])->name('issues.report');
    Route::get('/issues/track/{code?}', [IssueReportController::class, 'track'])->name('issues.track');
    Route::get('/issues/my-reports', [IssueReportController::class, 'myReports'])->name('issues.my_reports');

    // Agent Refer & Earn Dashboard
    Route::get('/referrals', [AgentReferralController::class, 'index'])->name('referrals.index');
    Route::post('/referrals/regenerate', [AgentReferralController::class, 'regenerate'])->name('referrals.regenerate');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark_all_read');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    Route::get('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('/notifications/preferences', [NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
});

// Impersonation Exit Route (Any authenticated session with impersonation flag)
Route::middleware(['auth'])->post('/impersonate/leave', [AdminUserController::class, 'leaveImpersonation'])->name('impersonate.leave');

require __DIR__.'/auth.php';
