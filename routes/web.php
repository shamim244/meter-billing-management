<?php

use App\Http\Controllers\Admin\AdminBillController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMruController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MruController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Agent & User Routes
Route::middleware(['auth', 'verified', 'active', 'subscription.not_suspended'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData'])->name('dashboard.data');

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
    Route::get('/reports', [\App\Http\Controllers\UsageReportController::class, 'index'])->name('reports.usage');
    Route::get('/reports/status-tag', [\App\Http\Controllers\UsageReportController::class, 'statusTagReport'])->name('reports.status_tag');
    Route::get('/reports/status-tag/export-csv', [\App\Http\Controllers\UsageReportController::class, 'exportStatusTagCsv'])->name('reports.status_tag.export_csv');
    Route::get('/reports/quota', [\App\Http\Controllers\UsageReportController::class, 'quotaReport'])->name('reports.quota');
    Route::get('/reports/flagged-estimates', [\App\Http\Controllers\UsageReportController::class, 'flaggedEstimates'])->name('reports.flagged');

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
    Route::post('/mrus/{mru}/unlock', [MruController::class, 'unlock'])->name('mrus.unlock');
    Route::post('/mrus/{mru}/start-billing', [MruController::class, 'startMonthlyBilling'])->middleware(['throttle:30,1', 'mru.not_locked'])->name('mrus.start-billing');
    Route::post('/mrus/{mru}/sync-missing', [MruController::class, 'syncMissingForMru'])->middleware(['throttle:30,1', 'mru.not_locked'])->name('mrus.sync-missing');
    
    // Data Processing Center (Download & Extraction Hub)
    Route::get('/processing', [\App\Http\Controllers\ProcessingController::class, 'index'])->name('processing.index');
    Route::get('/processing/status', [\App\Http\Controllers\ProcessingController::class, 'getStatus'])->name('processing.status');
    Route::post('/processing/create-cycle', [\App\Http\Controllers\ProcessingController::class, 'createCycle'])->name('processing.create-cycle');
    Route::post('/processing/cycles/{cycle}/sync', [\App\Http\Controllers\ProcessingController::class, 'syncCycle'])->name('processing.cycles.sync');
    Route::post('/processing/downloader', [\App\Http\Controllers\ProcessingController::class, 'runDownloader'])->middleware('throttle:30,1')->name('processing.downloader');
    Route::post('/processing/parser', [\App\Http\Controllers\ProcessingController::class, 'runParser'])->middleware('throttle:30,1')->name('processing.parser');
    Route::get('/processing/logs', [\App\Http\Controllers\ProcessingController::class, 'getLogs'])->name('processing.logs');
    Route::post('/processing/logs/clear', [\App\Http\Controllers\ProcessingController::class, 'clearLogs'])->name('processing.logs.clear');

    // Overall PDF Management Center
    Route::get('/pdf-manager', [\App\Http\Controllers\PdfManagerController::class, 'index'])->name('pdf-manager.index');
    Route::post('/pdf-manager/batch-download', [\App\Http\Controllers\PdfManagerController::class, 'batchDownload'])->middleware('throttle:30,1')->name('pdf-manager.batch-download');
    Route::post('/pdf-manager/batch-reparse', [\App\Http\Controllers\PdfManagerController::class, 'batchReparse'])->middleware('throttle:30,1')->name('pdf-manager.batch-reparse');
    Route::post('/pdf-manager/batch-redownload', [\App\Http\Controllers\PdfManagerController::class, 'batchRedownload'])->middleware('throttle:30,1')->name('pdf-manager.batch-redownload');
    Route::post('/pdf-manager/batch-delete', [\App\Http\Controllers\PdfManagerController::class, 'batchDelete'])->name('pdf-manager.batch-delete');
    Route::get('/pdf-manager/health-check', [\App\Http\Controllers\PdfManagerController::class, 'healthCheck'])->name('pdf-manager.health-check');
    Route::post('/pdf-manager/sync-storage', [\App\Http\Controllers\PdfManagerController::class, 'syncStorage'])->name('pdf-manager.sync-storage');
    Route::post('/pdf-manager/purge-cycle', [\App\Http\Controllers\PdfManagerController::class, 'purgeCyclePdfs'])->name('pdf-manager.purge-cycle');
    Route::post('/pdf-manager/upload', [\App\Http\Controllers\PdfManagerController::class, 'upload'])->name('pdf-manager.upload');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Payments & Checkout Flow
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [\App\Http\Controllers\PaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [\App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/verify', [\App\Http\Controllers\PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('/payments/sandbox', [\App\Http\Controllers\PaymentController::class, 'sandbox'])->name('payments.sandbox');
    Route::post('/payments/sandbox/checkout', [\App\Http\Controllers\PaymentController::class, 'sandboxCheckout'])->name('payments.sandbox.checkout');

    // Agent Wallet Ledger & Dashboard
    Route::get('/wallet', [\App\Http\Controllers\WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/export', [\App\Http\Controllers\WalletController::class, 'export'])->name('wallet.export');

    // User Panel & Operator Control Center (Overview, Subscription, Shortcuts, Preferences, Profile)
    Route::prefix('user-panel')->name('user-panel.')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserPanelController::class, 'index'])->name('index');
        Route::get('/subscription', [\App\Http\Controllers\UserPanelController::class, 'subscription'])->name('subscription');
        Route::get('/shortcuts', [\App\Http\Controllers\UserPanelController::class, 'shortcuts'])->name('shortcuts');
        Route::get('/preferences', [\App\Http\Controllers\UserPanelController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [\App\Http\Controllers\UserPanelController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/profile', [\App\Http\Controllers\UserPanelController::class, 'profile'])->name('profile');
        Route::patch('/profile', [\App\Http\Controllers\UserPanelController::class, 'updateProfile'])->name('profile.update');
        Route::put('/password', [\App\Http\Controllers\UserPanelController::class, 'updatePassword'])->name('password.update');
    });
});

// Payment Gateway Webhooks (Unauthenticated, CSRF excluded)
Route::post('/webhooks/payments/razorpay', [\App\Http\Controllers\PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.razorpay')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::post('/webhooks/payments/cashfree', [\App\Http\Controllers\PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.cashfree')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

Route::post('/webhooks/payments/pg', [\App\Http\Controllers\PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payments.pg')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

// Admin Panel Routes (Protected by role:admin and active)
Route::middleware(['auth', 'role:admin', 'active'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::patch('/users/{user}/update-quota', [AdminUserController::class, 'updateQuota'])->name('users.update-quota');

    // Admin Wallet Management & Ledger Adjustments
    Route::get('/wallets', [\App\Http\Controllers\Admin\AdminWalletController::class, 'index'])->name('wallets.index');
    Route::get('/wallets/{user}', [\App\Http\Controllers\Admin\AdminWalletController::class, 'show'])->name('wallets.show');
    Route::post('/wallets/{user}/adjust', [\App\Http\Controllers\Admin\AdminWalletController::class, 'adjust'])->name('wallets.adjust');
    Route::post('/wallets/{user}/toggle-freeze', [\App\Http\Controllers\Admin\AdminWalletController::class, 'toggleFreeze'])->name('wallets.toggle-freeze');
    Route::get('/wallets/{user}/export', [\App\Http\Controllers\Admin\AdminWalletController::class, 'export'])->name('wallets.export');

    Route::get('/bills', [AdminBillController::class, 'index'])->name('bills.index');

    Route::get('/mrus', [AdminMruController::class, 'index'])->name('mrus.index');
    Route::patch('/mrus/{mru}', [AdminMruController::class, 'update'])->name('mrus.update');

    Route::get('/shortcuts', [\App\Http\Controllers\Admin\AdminShortcutController::class, 'index'])->name('shortcuts.index');
    Route::post('/shortcuts', [\App\Http\Controllers\Admin\AdminShortcutController::class, 'update'])->name('shortcuts.update');
    Route::post('/shortcuts/reset-factory', [\App\Http\Controllers\Admin\AdminShortcutController::class, 'resetToFactory'])->name('shortcuts.reset-factory');
    Route::post('/shortcuts/reset-all-users', [\App\Http\Controllers\Admin\AdminShortcutController::class, 'resetAllUsers'])->name('shortcuts.reset-all-users');

    // Admin Payment Verification Queue & Controls
    Route::get('/payments', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/manual', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'manual'])->name('payments.manual');
    Route::get('/payments/analytics', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'analytics'])->name('payments.analytics');
    Route::get('/payments/audit', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'audit'])->name('payments.audit');
    Route::get('/payments/simulator', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'simulator'])->name('payments.simulator');
    Route::post('/payments/simulator/checkout', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'simulateCheckout'])->name('payments.simulator.checkout');
    Route::post('/payments/simulator/webhook', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'simulateWebhook'])->name('payments.simulator.webhook');
    Route::post('/payments/simulator/seed', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'seedDemoPayments'])->name('payments.simulator.seed');
    Route::get('/payments/settings', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'settings'])->name('payments.settings');
    Route::post('/payments/settings', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'updateSettings'])->name('payments.settings.update');
    Route::get('/payments/{payment}', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{payment}/approve', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('/payments/{payment}/refund', [\App\Http\Controllers\Admin\AdminPaymentController::class, 'refund'])->name('payments.refund');

    // Admin Plan Management & Overage Audit
    Route::get('/plans/overage-charges', [\App\Http\Controllers\Admin\AdminPlanController::class, 'overageCharges'])->name('plans.overage_charges');
    Route::post('/plans/migrate-agent', [\App\Http\Controllers\Admin\AdminPlanController::class, 'migrateAgent'])->name('plans.migrate_agent');
    Route::post('/plans/unlock-mru/{mru}', [\App\Http\Controllers\Admin\AdminPlanController::class, 'unlockMru'])->name('plans.unlock_mru');
    Route::get('/plans/{plan}/agents', [\App\Http\Controllers\Admin\AdminPlanController::class, 'agents'])->name('plans.agents');
    Route::post('/plans/{plan}/force-delete', [\App\Http\Controllers\Admin\AdminPlanController::class, 'forceDelete'])->name('plans.force-delete');
    Route::resource('plans', \App\Http\Controllers\Admin\AdminPlanController::class);

    // Admin Billing & Subscription Lifecycle Management
    Route::get('/subscriptions', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions/{subscription}/state-override', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'stateOverride'])->name('subscriptions.state_override');
    Route::get('/subscriptions/renewal-attempts', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'renewalAttempts'])->name('subscriptions.renewal_attempts');
    Route::get('/subscriptions/upgrade-logs', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'upgradeLogs'])->name('subscriptions.upgrade_logs');
    Route::post('/subscriptions/settings', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'updateSettings'])->name('subscriptions.update_settings');

    // Admin Bill Review Tags Manager
    Route::get('/tags', [\App\Http\Controllers\Admin\AdminTagController::class, 'index'])->name('tags.index');
    Route::post('/tags', [\App\Http\Controllers\Admin\AdminTagController::class, 'update'])->name('tags.update');
    Route::post('/tags/store', [\App\Http\Controllers\Admin\AdminTagController::class, 'store'])->name('tags.store');
    Route::delete('/tags/{code}', [\App\Http\Controllers\Admin\AdminTagController::class, 'destroy'])->name('tags.destroy');
    Route::post('/tags/reset-factory', [\App\Http\Controllers\Admin\AdminTagController::class, 'resetToFactory'])->name('tags.reset_factory');

    // Admin Usage Tracking & Platform Health Reports
    Route::get('/reports/usage', [\App\Http\Controllers\Admin\AdminUsageReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/status-tag', [\App\Http\Controllers\Admin\AdminUsageReportController::class, 'statusTagReport'])->name('reports.status_tag');
    Route::get('/reports/quota', [\App\Http\Controllers\Admin\AdminUsageReportController::class, 'quotaUsageReport'])->name('reports.quota');
    Route::get('/reports/flagged-estimates', [\App\Http\Controllers\Admin\AdminUsageReportController::class, 'flaggedEstimates'])->name('reports.flagged');

    // Admin Notification Engine (Registry, Templates, Failed Critical Queue)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        // Email Provider Instances Registry
        Route::get('/email-providers', [\App\Http\Controllers\Admin\AdminEmailProviderController::class, 'index'])->name('email_providers.index');
        Route::post('/email-providers', [\App\Http\Controllers\Admin\AdminEmailProviderController::class, 'store'])->name('email_providers.store');
        Route::put('/email-providers/{provider}', [\App\Http\Controllers\Admin\AdminEmailProviderController::class, 'update'])->name('email_providers.update');
        Route::post('/email-providers/{provider}/toggle', [\App\Http\Controllers\Admin\AdminEmailProviderController::class, 'toggle'])->name('email_providers.toggle');
        Route::post('/email-providers/{provider}/test-send', [\App\Http\Controllers\Admin\AdminEmailProviderController::class, 'testSend'])->name('email_providers.test_send');
        Route::delete('/email-providers/{provider}', [\App\Http\Controllers\Admin\AdminEmailProviderController::class, 'destroy'])->name('email_providers.destroy');

        // Message Templates & Priority Routing
        Route::get('/templates', [\App\Http\Controllers\Admin\AdminNotificationTemplateController::class, 'index'])->name('templates.index');
        Route::put('/templates/{template}', [\App\Http\Controllers\Admin\AdminNotificationTemplateController::class, 'update'])->name('templates.update');
        Route::post('/templates/preview', [\App\Http\Controllers\Admin\AdminNotificationTemplateController::class, 'preview'])->name('templates.preview');
        Route::post('/templates/reset', [\App\Http\Controllers\Admin\AdminNotificationTemplateController::class, 'resetToDefaults'])->name('templates.reset');

        // Failed Critical Queue
        Route::get('/failed-queue', [\App\Http\Controllers\Admin\AdminFailedNotificationController::class, 'index'])->name('failed_queue');
    });
});

// Agent Notifications & Preferences Routes (Protected by auth and active)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/recent', [\App\Http\Controllers\NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllRead'])->name('notifications.mark_all_read');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark_read');
    Route::get('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('/notifications/preferences', [\App\Http\Controllers\NotificationController::class, 'updatePreferences'])->name('notifications.preferences.update');
});

require __DIR__.'/auth.php';

