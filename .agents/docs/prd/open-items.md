# Platform Master Tracking & Open Items

> **Location:** `php/laravel/.agent/docs/prd/open-items.md`  
> **Important**: None of the pending decision items below have been hardcoded or unilaterally resolved in the codebase. Defaults are parameterized through dynamic services or left open for administrative/product decision.

---

## 1. Master Decision Tracking Checklist

- [x] **Wallet-first vs mandate-first renewal priority** *(RESOLVED: Wallet ONLY. No recurring mandate attempt; transitions straight to grace period on insufficient balance)*
- [x] **Refund destination policy** *(RESOLVED: Always refund to Agent wallet as credit, admin-manual only)*
- [x] **Confirm freeze actually blocks debit() calls** *(Verified: `WalletService::debit()` checks `is_wallet_frozen` first, returns `DebitResult::WALLET_FROZEN`, and 100% covered by automated test `test_debit_is_blocked_when_wallet_is_frozen`)*
- [x] **Set real low-balance threshold** *(Configurable: editable in Admin Settings `/admin/payments/settings` and stored via `SystemSetting::get('wallet_low_balance_threshold')`, defaulting to ₹200.00)*
- [x] **Zero hardcoding for duration discounts** *(Verified: Admin sets discount % and final prices per plan; no hardcoded defaults in code)*
- [x] **Auto-lock renewal prompt timeout** *(Configurable: defined in `config/plans.php` as `'mru_autolock_timeout_hours' => 72` and overridable via `SystemSetting`)*
- [x] **Configurable platform grace period** *(Configurable: defined in `config/billing.php` as `'default_grace_period_days' => 3`, overridable via `SystemSetting` and per-Plan overrides)*
- [ ] **RENEWAL_DUE vs GRACE_PERIOD UI banner styling** *(Needs decision: whether to use distinct colors [amber vs red] or shared styling)*
- [ ] **Admin notification on account suspension** *(Needs decision: notification channel preferences for Notification System)*

---

## 2. Wallet System — Open Items & Decisions Details

### 2.1 Wallet Auto-Debit Priority Order (RESOLVED)
- **Decision**: **Wallet ONLY.** No payment gateway recurring mandate attempts. If wallet balance is insufficient at renewal, the subscription transitions directly to the grace period.

### 2.2 Refund Destination Policy (RESOLVED)
- **Decision**: **Always refund to Agent wallet as credit.** Performed via Admin manual adjustment console only; no direct gateway API auto-refunds.

### 2.3 Low Balance Threshold Values
- **Architecture**: Dynamically fetched from Admin Gateway/Wallet Settings (`SystemSetting::get('wallet_low_balance_threshold', 200.00)`), completely configurable from the UI without code modifications.

### 2.4 Negative Balance Overdraft Limits
- **Architecture**: Normal debits are strictly non-negative; `adminAdjust()` is the only method permitted to push balance negative (via `forceWithdrawFloat`).

---

## 3. Plan Management System — Open Items (Section 11)

### 3.1 Default Duration Discount Percentages (RESOLVED)
- **Status**: Fully editable by Admin per Plan in `/admin/plans/create` and `/admin/plans/{plan}/edit`. No hardcoded percentages anywhere in the code.

### 3.2 Grace Period / Auto-Lock Decision Window (CONFIGURED)
- **Status**: Configured in [`config/plans.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/config/plans.php) as `72` hours (`PLAN_MRU_AUTOLOCK_TIMEOUT_HOURS`), dynamically readable via `RenewalService::getAutoLockTimeoutHours()` and overridable in `SystemSetting`.

### 3.3 Admin Notification on MRU Auto-Lock
- **Architecture**: `MruLockedEvent` is dispatched whenever an MRU is locked. Actual delivery channels (email, SMS, push) will be handled by the upcoming Notification System.

### 3.4 Persistent UI Banner for Locked MRUs
- **Architecture**: MRU show view and API responses clearly identify lock reason and provide an instant unlock pay-gate button.

---

## 4. Billing & Subscription System — Open Items (Section 9)

### 4.1 Platform Default Grace Period (CONFIGURED)
- **Status**: Configured in [`config/billing.php`](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/config/billing.php) as `3` days (`BILLING_DEFAULT_GRACE_PERIOD_DAYS`), overridable via Admin Settings (`SystemSetting::get('billing_default_grace_period_days')`) and per-Plan `plans.grace_period_days`. A value of `0` skips grace period straight to `SUSPENDED`.

### 4.2 RENEWAL_DUE vs GRACE_PERIOD UI Banner Styling
- **Item**: Whether `RENEWAL_DUE` (informational) and `GRACE_PERIOD` (urgent countdown) require distinct visual badge/banner components or a shared style.
- **Recommendation**: Distinct styling (amber for RENEWAL_DUE, red countdown for GRACE_PERIOD).

### 4.3 Admin Notification on Account Suspension (RESOLVED)
- **Architecture**: `SubscriptionSuspendedEvent` is dispatched and routed through `DomainNotificationSubscriber` as a CRITICAL notification. Delivered via In-App (un-disableable) and Email with 3-attempt exponential backoff.

---

## 5. Notification System — Open Items (Section 13)

### 5.1 Usage Tracking Monthly Summary Trigger (RESOLVED)
- **Status**: Implemented as a non-intrusive scheduled job `CheckMonthlyUsageSummaryJob` within the Notification System. It queries `UsageSummaryService::getMonthlySummary()` in a safe read-only manner and dispatches `usage.monthly_summary_ready` without modifying Module 05.

### 5.2 In-App Notification UI Placement (RESOLVED)
- **Status**: Implemented both: (1) an interactive header Bell icon with live unread badge and AJAX dropdown in top navigation, and (2) a dedicated full notifications history feed at `/notifications` with category and read-status filtering.

### 5.3 Email Provider Credential Encryption (RESOLVED)
- **Status**: Implemented with Laravel's native `encrypted:array` casting on `email_provider_instances.config`. Raw database records store ciphertext; Eloquent models decrypt credentials seamlessly in memory.

### 5.4 3-Attempt Exponential Backoff & Failed Critical Alert (RESOLVED)
- **Status**: `SendEmailNotificationJob` retries 3 times with exponential backoff (`[60s, 300s, 900s]`). If a CRITICAL event fails all 3 attempts, `AdminNotificationFailedEvent` is dispatched and surfaced on the Admin Failed Critical Queue at `/admin/notifications/failed-queue`.

