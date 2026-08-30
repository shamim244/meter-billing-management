# Platform Implementation Progress

---

## 1. Payment Gateway System

**Date:** 2026-08-21  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `2.Payment_Gateway_System_PRD.md`

### 1.1 Terminology & Schema Alignment
- The term **"Tenant"** was completely removed and replaced with standard **"Billing Agent / User"** (`user_id`).
- Database schema, models, services, controllers, and UI views consistently use `user_id` referencing the `users` table.

### 1.2 What Was Built
- **Database Schema & Migrations** (`2026_08_21_000001_create_payment_gateway_tables.php`):
  - `payments` table: master payment records with indexes.
  - `payment_mandates` table: recurring auto-debit tracking.
  - `payment_audit_log` table: complete action audit trail.
- **Enums & Models**:
  - `PaymentMode` (`pg`, `manual_upi`, `bank_transfer`), `PaymentPurpose`, `PaymentStatus`, `MandateStatus`, `PaymentAuditAction`.
  - `Payment`, `PaymentMandate`, `PaymentAuditLog`.
- **Service Layer Handlers**:
  - `OnlinePaymentGatewayService`: Order creation, webhook validation, and idempotent processing.
  - `ManualUpiPaymentService`: 12-digit UTR validation, proof upload.
  - `BankTransferPaymentService`: NEFT/IMPS reference validation, receipt upload.
  - `PaymentVerificationService`: `approve()`, `reject()`, `refund()`.
  - `PaymentSettingsService`: Mode enable/disable toggles, UPI QR settings.
- **Domain Event Hooks**:
  - `PaymentSuccessEvent`, `PaymentFailedEvent`, `ManualPaymentSubmittedEvent`, `ManualPaymentApprovedEvent`, `ManualPaymentRejectedEvent`, `PaymentMandateFailedEvent`.
- **Controllers & UI**:
  - `PaymentController`: `/payments` (ledger) and `/payments/create` (checkout).
  - `AdminPaymentController`: `/admin/payments`, `/admin/payments/settings`, `/admin/payments/simulator`.
  - `PaymentWebhookController`: `/webhooks/payments/pg`.

---

## 2. Wallet System

**Date:** 2026-08-21  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `3.Wallet_System_PRD.md`  
**Underlying Engine:** `bavix/laravel-wallet` (^12.0)

### 2.1 Package Configuration & Model Integration
- Installed and configured **`bavix/laravel-wallet`** (`^12.0`) with published config at `config/wallet.php` and service provider in `bootstrap/providers.php`.
- Published and ran native package migrations (`wallets`, `transactions`, `transfers`, `wallet_purchases`).
- Added custom freeze migration `2026_08_21_000002_add_wallet_freeze_to_users_table.php`.
- **`User` Model**: Implements `Bavix\Wallet\Interfaces\Wallet` & `Bavix\Wallet\Interfaces\WalletFloat` via `HasWalletFloat` trait.
- Helper methods: `isWalletFrozen()`, `walletFrozenBy()`.

### 2.2 `WalletService` Application Wrapper (PRD Section 8)
- **`getBalance(user)`**: Live balance retrieval as float.
- **`credit(user, amount, source, refType, refId, desc)`**: Uses `depositFloat()`, storing `source`, `reference_type`, `reference_id`, and `description` in native `meta` JSON. Dispatches `WalletCreditedEvent`.
- **`debit(user, amount, source, refType, refId, desc)`**: Checks freeze status (`DebitResult::WALLET_FROZEN`), executes `withdrawFloat()`, catches `InsufficientFunds`/`BalanceIsEmpty` to gracefully return `DebitResult::INSUFFICIENT_BALANCE` without throwing exceptions. Dispatches `WalletDebitedEvent`.
- **`adminAdjust(user, admin, type, amount, reason)`**: `add` uses `depositFloat()`; `deduct` uses `forceWithdrawFloat()` (only method permitted to drive balance negative). Enforces mandatory audit reasons.
- **`freeze(user, admin, reason)` & `unfreeze(user, admin, reason)`**: Sets freeze state on `users` table and dispatches `WalletFrozenEvent`/`WalletUnfrozenEvent`.
- **`getTransactionHistory(user, filters, perPage)`**: Paginated, filterable query over `transactions`.
- **`checkBalanceAlerts(user)`**: Triggers `WalletLowBalanceEvent`, `WalletCriticalBalanceEvent`, `WalletInsufficientForRenewalEvent`.

### 2.3 Event Listener & Idempotent Top-Up
- **`CreditWalletOnPaymentSuccess`**: Listens for `PaymentSuccessEvent` with `purpose = 'wallet_topup'`.
- Idempotency verified via `Transaction::where('meta->source', 'payment_topup')->where('meta->reference_id', (string)$payment->id)->exists()`.

### 2.4 Agent & Admin Consoles
- **Agent Wallet Dashboard** (`/wallet`): Real-time balance card, KPI summary cards, filterable ledger, and streaming CSV export (`/wallet/export`).
- **Admin Wallets Master Ledger** (`/admin/wallets`): System liabilities, search/filter, and freeze status.
- **Admin 2-Click Adjustment Console** (`/admin/wallets/{user}`): Prominent balance display, `[+ Add Balance]` and `[− Deduct Balance]` modals, 1-click freeze toggle, adjustment audit trail, and 1-click reachable from `/admin/users`.

---

## 3. Plan Management System

**Date:** 2026-08-22  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `01-Plan_Management_System_PRD.md`

### 3.1 What Was Built
- **Database Schema & Migrations** (`2026_08_22_000001_create_plan_management_tables.php`):
  - `plans` table with soft deletes (`deleted_at`).
  - `plan_durations` table with unique constraint `(plan_id, duration_months)`.
  - `agent_subscriptions` table with immutable snapshots (`base_price_paid`, `included_mrus_locked`, `included_consumers_locked`, `extra_mru_rate_locked`, `extra_consumer_rate_locked`).
  - Altered `mrus` table to add `locked_reason`, `locked_at`, `unlocked_at`, `is_over_quota`.
  - `billing_cycles` table tracking per-cycle consumer quota usage (`included_quota_used`, `extra_consumer_count`, `extra_consumer_charge`).
  - `plan_overage_charges` table recording all MRU and Consumer pay-gate charges linked to `wallet_transaction_id`.
- **Models**:
  - `Plan`, `PlanDuration`, `AgentSubscription`, `Mru`, `BillingCycle`, `PlanOverageCharge`, `User`.
- **Services**:
  - **`PlanService`**: Admin plan CRUD, nested duration pricing sync, soft delete, safe force-delete with subscriber migration requirement, and `subscribeAgent()` / `migrateAgent()` with locked pricing snapshots.
  - **`MruQuotaService`**: MRU standing quota check, creation pay-gate with wallet deduction, MRU lock/unlock tools, and locked permissions checker (`isActionAllowed`).
  - **`ConsumerQuotaService`**: Period-based (monthly) consumer quota tracking across billing cycles, cycle creation pay-gate, and explicit `syncCycleConsumerCount()` recalculation trigger.
  - **`RenewalService`**: Calculates renewal summary with extra MRU prompt and **strictly ₹0.00 consumer overage** (per invariant). Handles YES (charges extra MRU) and NO (auto-locks newest over-quota MRU).
- **Middleware**:
  - `EnsureMruNotLocked` (`mru.not_locked`): Enforces PRD Section 5.1 Step 3 permissions (allowed: view, rename, delete, add/remove consumer; blocked: modify consumer details, create cycle, process/download PDF).
- **Admin & Agent UI**:
  - Admin Plan management (`/admin/plans`, `/admin/plans/create`, `/admin/plans/{plan}/edit`).
  - Admin Plan Subscribers list with 1-click Plan Migration Modal (`/admin/plans/{plan}/agents`).
  - Admin Overage Audit Log (`/admin/plans/overage-charges`).
  - Agent MRU self-service unlock endpoint (`POST /mrus/{mru}/unlock`).
  - Agent Billing Cycle creation & explicit Sync endpoints (`POST /processing/create-cycle`, `POST /processing/cycles/{cycle}/sync`).
- **Automated Test Suite**:
  - `PlanManagementSystemTest`: 7 comprehensive tests covering locked snapshots, sequential pay-gates, explicit sync, renewal calculation, auto-lock on renewal, locked permissions, and safe force deletion.

---

## 4. Billing & Subscription System

**Date:** 2026-08-22  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `04-Billing_Subscription_System_PRD.md`

### 4.1 What Was Built
- **Database Schema & Migrations** (`2026_08_22_000002_create_billing_subscription_tables.php`):
  - Extended `agent_subscriptions`: Added `lifecycle_status` (`active`, `renewal_due`, `grace_period`, `suspended`), `grace_period_days`, `grace_period_ends_at`, `auto_renewal_enabled`, `suspended_at`, `last_state_change_at`.
  - Extended `plans`: Added `grace_period_days` for per-plan grace period overrides.
  - Created `renewal_attempts`: Tracks auto and manual renewal attempts, amounts, wallet transaction IDs, and failure reasons.
  - Created `plan_upgrade_log`: Full proration audit trail for both upgrades and downgrades.
- **Models**:
  - Updated `AgentSubscription`, `Plan`, `User`.
  - Created `RenewalAttempt`, `PlanUpgradeLog`.
- **Services**:
  - **`SubscriptionLifecycleService`**:
    - `transitionToRenewalDue()`, `transitionToGracePeriod()`, `transitionToSuspended()`, `reactivate()`.
    - Resolves grace periods (Plan override -> SystemSetting -> config default: 3 days).
    - Setting grace period to 0 days immediately skips straight from `RENEWAL_DUE` to `SUSPENDED`.
    - `runDailyLifecycleProcessor()`: Automated batch transitions and auto-renewal processing (wallet-only; no PG mandate attempt on failure).
  - **`PlanChangeService`**:
    - `calculateProration()`: Single shared day-based mathematical implementation for upgrades and downgrades.
    - `upgradePlan()`: Debits wallet, updates snapshot, and auto-unlocks previously locked MRUs covered by the new quota.
    - `checkDowngradeEligibility()`: Server-side active MRU count verification.
    - `downgradePlan()`: Enforces server-side eligibility check, credits wallet (balance increases), and updates snapshot.
  - **`RenewalService`**:
    - Integrated with `reactivate()`, logs all renewal attempts, and supports `toggleAutoRenewal()`.
- **Middleware**:
  - `EnsureSubscriptionNotSuspended` (`subscription.not_suspended`): Centralized read-only enforcement. Allows viewing historical data, past cycles, and bills, but strictly blocks write actions (MRU creation, cycle creation, PDF processing/downloads, consumer edits) when an account is `suspended`.
- **Admin Panel UI**:
  - Admin Subscriptions Dashboard (`/admin/subscriptions`) with state overview KPI cards and manual state override modal (with mandatory audit reason).
  - Renewal Attempts History (`/admin/subscriptions/renewal-attempts`).
  - Plan Upgrade & Downgrade Proration Audit Log (`/admin/subscriptions/upgrade-logs`).
  - Plan Creation/Edit Grace Period Override field (`/admin/plans/create`, `/admin/plans/{plan}/edit`).
- **Automated Test Suite**:
  - `BillingSubscriptionSystemTest`: 7 comprehensive tests covering server-side downgrade blocks, auto-unlock on upgrade, downgrade wallet credits, auto-renewal wallet-only invariant, zero-day grace period skips, suspended read-only enforcement, and mandatory override reason logging.
  - **Full Project Feature Suite**: **99/99 tests passed, 463 assertions, 0 failures (100% PASS)**.
  - **Blade Template Compilations**: All 26 views rendered cleanly with 0 errors.

### 4.2 What Was Skipped (Out of Scope per PRD Section 9)
- GST invoice PDF generation for subscription renewals and mid-cycle plan changes → **Invoicing & GST System**.
- Actual notification delivery (SMS/email/WhatsApp/push) → **Notification System** (domain events fired: `SubscriptionRenewalDueEvent`, `SubscriptionEnteredGracePeriodEvent`, `SubscriptionSuspendedEvent`, `SubscriptionReactivatedEvent`, `RenewalFailedInsufficientBalanceEvent`, `PlanUpgradedEvent`, `PlanDowngradedEvent`).

### 4.3 Open Items (Section 9) Requiring Future Product Decisions
1. **RENEWAL_DUE vs GRACE_PERIOD UI Banner Styling**: Whether to use distinct styling (e.g. Amber info for RENEWAL_DUE vs Red countdown for GRACE_PERIOD) or one shared banner.
2. **Admin Notification on Account Suspension**: Delivery channels for alerts when an Agent enters SUSPENDED state.

---

## 5. Usage Tracking System

**Date:** 2026-08-22  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `05-Usage_Tracking_System_PRD.md`

### 5.1 Terminology & Schema Alignment
- Exclusively uses **"Agent / User"** (`user_id`).
- Strictly **read-only reporting** module with **ZERO calls to `WalletService`**.
- Dynamic live bill tag retrieval via `BillTagService::getActiveTags()` (never hardcoded), with automatic inclusion of historical deleted tags present in past records.

### 5.2 What Was Built
- **Database Schema & Migrations** (`2026_08_22_000004_create_billing_basis_history_table.php`):
  - `billing_basis_history` table: `user_id`, `mru_id`, `consumer_id`, `ca_number`, `billing_cycle_id`, `billing_month`, `billing_year`, `billing_basis`, `is_consecutive_alert`, `consecutive_count`, `timestamps`.
  - Composite indexes on `[user_id, billing_month, billing_year]`, `[user_id, is_consecutive_alert]`, `[user_id, ca_number]`, and unique on `[user_id, ca_number, billing_month, billing_year]`.
- **Models**:
  - `BillingBasisHistory`: Relations to `User`, `Mru`, `ConsumerAccount`, `BillingCycle` with period and alert scopes.
- **Service Layer Handlers**:
  - **`BillingBasisTrackingService`**:
    - `recordBillingBasis()` & `recordFromBillRecord()`: Synchronously hooked into PDF parsing (`BillParseService::parseBatch()` and `BillParseService::parseSingle()`).
    - `calculateConsecutiveCount()`: Walks prior cycles in reverse chronological order. Increments consecutive count on `LK` (Locked) or `MD` (Defective Meter). Resets strictly to `0` upon any `OK` reading. Sets `is_consecutive_alert = true` when count $\ge 2$.
    - `getFlaggedConsumers()` & `getFlaggedConsumerCount()`: Retrieves alert queue for field readers.
  - **`StatusTagReportService`**:
    - `getMonthlyStatusBreakdown()`: Aggregates counts across `submitted`, `critical`, `doubt`, and `pending`.
    - `getMonthlyTagBreakdown()`: Dynamically reads active tags from `BillTagService::getActiveTags()` + includes historical tag counts even if deleted from active configuration.
    - `getConsumersByFilter()`: Filterable drill-down table with pagination.
    - `exportCsv()`: Direct streaming CSV export formatted with UTF-8 BOM.
  - **`QuotaUsageReportService`**:
    - `getMonthlyQuotaUsage()`: Aggregates MRU quota (included, active, extra) and Consumer quota (included, processed, extra) along with overage fee totals from `plan_overage_charges`.
    - `getOverageChargeTotals()`: Splits overage spend between MRU fees and Consumer fees.
    - `getUsageTrend()`: 6-month historical usage matrix.
    - `getAdminAggregateQuotaUsage()`: Cross-Agent audit sortable by `overage_spend`, `consumer_usage`, `mru_usage`, or `name`.
  - **`UsageSummaryService`**:
    - `getMonthlySummary()`: Aggregates the 5-box ROI dashboard object (`bills_processed`, `mrus_active`, `data_coverage_percentage`, `flagged_consumers_count`, `historical_depth_months`).
    - `getAdminAggregateSummary()`: Platform-wide operational health rollup.
- **Agent & Admin Controllers & UI**:
  - **Agent Reports**:
    - `/reports` (ROI Overview Dashboard with KPI cards and coverage progress).
    - `/reports/status-tag` (Monthly Review Status & Tag Breakdown + Drill-down table + CSV Export).
    - `/reports/quota` (Quota Usage & 6-Month Trend Matrix).
    - `/reports/flagged-estimates` (Consecutive LK/MD alert queue).
  - **Admin Reports**:
    - `/admin/reports/usage` (Platform-wide ROI overview & Agent Health Matrix).
    - `/admin/reports/status-tag` (Cross-Agent Status & Tag distribution).
    - `/admin/reports/quota` (Quota & Overage Leaderboard sortable by spend).
    - `/admin/reports/flagged-estimates` (System-wide consecutive estimate queue).
  - **Review Card & Table Badge**:
    - Surfaces consecutive estimate alert badge (`⚠️ 2x LK`, `⚠️ 3x MD`) on review cards and table views matching existing badge styling.
- **Automated Test Suite**:
  - `UsageTrackingSystemTest`: 7 comprehensive tests covering dynamic tag reflection, historical deleted tag retention, consecutive estimate detection & 'OK' reset, quota usage calculation accuracy, zero wallet calls invariant verification, CSV streaming export, and admin overage spend sorting.

---

## 6. Notification System

**Date:** 2026-08-22  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `06-Notification_System_PRD.md`

### 6.1 Terminology & Architecture Alignment
- Exclusively uses **"Agent / User"** (`user_id`).
- Strictly **reactive**: **ZERO business logic** calculating billing, quotas, or wallet balances. All data rendered is derived directly from event payloads and service DTOs.
- **Encrypted Credential Storage**: `email_provider_instances.config` uses Laravel's `encrypted:array` cast to ensure raw database values are encrypted at rest.
- **Forced In-App Baseline for CRITICAL Events**: In-App delivery for CRITICAL notifications (Account Suspensions, Grace Periods, Frozen Wallets, Low Balances) can **never be disabled** by Agent preferences.

### 6.2 What Was Built
- **Database Schema & Migrations** (`2026_08_22_000005_create_notification_system_tables.php`):
  - `email_provider_instances`: `driver_type` (`smtp`, `resend`, `brevo`), `label`, encrypted `config`, `priority` (integer), `is_enabled`, `last_used_at`, `last_failure_at`, `last_failure_reason`.
  - `notifications`: `user_id`, `event_type`, `priority` (`critical`, `routine`), `title`, `body`, `data` (JSON), `read_at`, `created_at`.
  - `notification_deliveries`: `notification_id`, `channel` (`in_app`, `email`, `push`), `email_provider_instance_id`, `status` (`pending`, `sent`, `failed`, `permanently_failed`), `attempt_count`, `last_attempted_at`, `failed_reason`.
  - `agent_notification_preferences`: `user_id`, `event_category`, `channel`, `enabled`.
  - `notification_templates`: `event_type`, `channel`, `subject`, `body_template`, `priority`, `is_active`.
- **Models**:
  - `EmailProviderInstance`, `Notification`, `NotificationDelivery`, `AgentNotificationPreference`, `NotificationTemplate`.
- **Driver Layer & Fallback Engine**:
  - **`EmailProviderRegistryService`**:
    - Walks active email providers in priority order.
    - Automatic in-request fallback from Provider #1 to Provider #2 upon connection/API failure.
    - Records the succeeding provider ID in `notification_deliveries.email_provider_instance_id`.
    - Direct test-send bypassing fallback chain for Admin verification.
  - **Email Provider Drivers**:
    - `SmtpDriver`: Native Symfony/Laravel SMTP transport configured dynamically per instance.
    - `ResendDriver`: Transactional email API integration with API key from encrypted config.
    - `BrevoDriver`: Brevo (Sendinblue) transactional email API integration.
  - **Channel Drivers**:
    - `InAppChannelDriver`: Database-backed dashboard notifications.
    - `EmailChannelDriver`: Delegates to `EmailProviderRegistryService`.
    - `PushChannelDriver`: OneSignal API stub/driver.
- **Dispatch & Subscriber Layer**:
  - **`NotificationDispatchService`**: Single entry point resolving templates, merge placeholders (`{agent_name}`, `{amount}`, `{balance}`, `{mru_code}`, etc.), priority rules, and Agent channel preferences.
  - **`DomainNotificationSubscriber`**: Listens and routes all 27+ domain events across Payment Gateway, Wallet, Plan Management, Billing & Subscription, and Auth/System domains.
  - **`SendEmailNotificationJob`**: Queued email delivery with 3-attempt exponential backoff (`[60s, 300s, 900s]`).
  - **`AdminNotificationFailedEvent`**: Dispatched when a CRITICAL notification fails all 3 retry attempts, surfacing on the Admin Failed Critical Queue.
  - **`CheckMonthlyUsageSummaryJob`**: Scheduled background job checking `UsageSummaryService` (read-only) and dispatching monthly ROI report notifications.
  - **Auth Transactional Emails**: Registration welcome email and password reset link routed through the active provider registry.
- **Agent & Admin Consoles**:
  - **Agent UI**:
    - Live Notification Bell Icon & dropdown with unread badge count in top navigation.
    - `/notifications`: Full notifications history feed with category & read state filters.
    - `/notifications/preferences`: Category toggles with locked/always-on baseline for Critical In-App alerts.
  - **Admin UI**:
    - `/admin/notifications/email-providers`: Registry management, priority re-ordering, test send modal, and delete safety guard (blocks deleting the only active provider).
    - `/admin/notifications/templates`: Message template management, priority mapping, **Dispatch Mode selection (Sync vs Queued)**, merge placeholder guide, and live preview modal with sample data.
    - `/admin/notifications/failed-queue`: Audit log of critical notification delivery failures.
- **Automated Test Suite**:
  - `NotificationSystemTest`: 11 comprehensive tests covering fallback chain walking, encrypted config storage at rest, forced In-App delivery for CRITICAL events, sole enabled provider delete guard, 3-attempt exponential backoff, immediate sync send, 8s timeout fallback to queue, queued dispatch stability, and admin dispatch mode updates.
  - **Full Application Suite**: All **186 tests** across all 6 modules pass with 100% success.

---

## 7. Plan Visibility Fix & Clean Payment Flow Separation

**Date:** 2026-08-23  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `01-Plan_Management_System_PRD.md`, `2.Payment_Gateway_System_PRD.md`

### 7.1 Part A: Admin Plan Visibility Bug & Root Cause
- **Root Cause Discovered:**
  - Admin plan creation (`/admin/plans/create`) properly stored plans in the database with `is_active = 1` and durations configured.
  - The user-facing plan endpoint `UserPanelController::subscription()` was returning a hardcoded static mock array of 3 plans (`$plans = [['id' => 'free', ...], ['id' => 'pro', ...], ...]`), completely bypassing the `Plan` Eloquent model and database records.
- **Resolution Applied:**
  - Updated `UserPanelController::subscription()` to dynamically query real database plans:
    `Plan::where('is_active', true)->with(['durations' => fn($q) => $q->orderBy('duration_months')])->orderBy('base_price')->get()`.
  - Passed active subscription snapshots and live wallet balance into `resources/views/user-panel/subscription.blade.php`.
  - Replaced static markup with dynamic duration selectors and live pricing cards.

### 7.2 Part B: Splitting Payment Flows into Two Distinct, Non-Overlapping Paths
- **B1 — `/payments/create` is Strictly Wallet Top-Up:**
  - Removed "Direct Subscription Payment" option and the entire "purpose" radio toggle from `resources/views/payments/create.blade.php`.
  - Removed `?purpose=` branching logic in `PaymentController::create()`. Form submits `purpose=wallet_topup` exclusively.
  - Retained: custom amount input, min ₹100 validation, quick presets (₹500, ₹1,000, ₹2,500, ₹5,000), and all 3 payment modes (Online PG, Manual UPI, Bank Transfer).
- **B2 — New Page: Subscription Purchase Confirmation (`/subscription/purchase/{plan}/{duration}`):**
  - Dedicated controller: `SubscriptionCheckoutController.php`.
  - Derives exact payable amount **strictly server-side** from `PlanDuration` records (with `PlanChangeService::calculateProration()` for mid-cycle upgrades).
  - **Zero editable amount fields** — fixed pricing guarantee.
  - Presents 3 payment modes (Online PG, Manual UPI, Bank Transfer) configured for the exact fixed amount.
- **B3 — Update the Plan Page (`/user-panel/subscription`):**
  - When user clicks "Subscribe" / "Upgrade" / "Extend", an interactive modal opens presenting two clean paths:
    1. **Pay from Wallet (In-Place)**:
       - Displays fixed amount and live wallet balance.
       - If wallet balance is sufficient, user clicks `[Confirm & Subscribe from Wallet]`, triggering `POST /subscription/subscribe-wallet` and completing the plan activation in-place without redirecting to `/payments/create`.
       - If insufficient, displays balance deficit and offers `[Top Up Wallet]` link.
    2. **Pay Directly**:
       - Navigates to `/subscription/purchase/{plan}/{duration}` for immediate external payment.
- **B4 — Conflict Removal & Automated Test Verification:**
  - Legacy `purpose=direct_subscription` links hitting `/payments/create` are automatically redirected to `route('user-panel.subscription')`.
  - Added new test suite `tests/Feature/PlanVisibilityAndPaymentSeparationTest.php` with 4 comprehensive tests (100% PASS).

---

## 8. Refer & Earn System

**Date:** 2026-08-30  
**Status:** Completed & Tested (100% PASS)  
**PRD Reference:** `09-Refer_Earn_System_PRD.md`

### 8.1 Architecture & Extension of Base Coupon Engine
- **Zero Duplication**: Reuses the `coupon_codes` table with `type = 'referral'` and a new `owner_user_id` column.
- **Settings Pattern**: Followed the exact `SystemSetting` key-value pattern (same as `PaymentSettingsService`) with `ReferralSettingsService` and fallback defaults in `config/referral.php`.
- **Database Schema**:
  - Migration `2026_08_30_000001_create_refer_and_earn_tables.php`.
  - `coupon_codes.owner_user_id`: Nullable foreign key to `users`.
  - `referral_signups` table: Tracks `[referrer_user_id, referee_user_id, referral_coupon_code_id, signed_up_at]`.
  - `referral_payouts` table: Tracks `[referral_coupon_code_id, referrer_user_id, referee_user_id, qualifying_payment_reference_type, qualifying_payment_reference_id, reward_amount, status, hold_expires_at, paid_at, clawed_back_at, clawback_reason, wallet_transaction_id]`.

### 8.2 What Was Built
- **Models**:
  - `CouponCode`: Added `owner_user_id` to `$fillable`, `owner()` and `referralPayouts()` relationships.
  - `ReferralPayout`: Full lifecycle ledger with scopes (`pending`, `paid`, `cancelled`, `clawed_back`) and relationships (`referrer`, `referee`, `couponCode`, `walletTransaction`).
  - `ReferralSignup`: Tracks referee-to-referrer links created at registration time.
- **Services**:
  - **`ReferralService`**:
    - `generateCodeForNewAgent($userId)`: Auto-generates unique readable codes (`REF-A7X9K2`) at signup.
    - `regenerateCode($userId)`: Deactivates old code for new signups while leaving existing pending payouts untouched.
    - `validateReferralCode($code, $newUserId)`: Enforces platform-wide one-time referral rule, active code validity, and blocks self-referrals (`owner_user_id === new_user_id`).
    - `recordReferralSignup($code, $newUserId)`: Links referee to referrer.
    - `checkAndCreatePendingPayout($user, $refType, $refId, $amount)`: Evaluates dynamic trigger (`'subscription'` vs `'topup'`), minimum qualifying amount (₹100), calculates reward (with per-agent override fallback to platform default), creates `ReferralPayout` in `'pending'` status with `hold_expires_at = now() + hold_period_days`, and dispatches `referral.reward_pending`.
    - `processExpiredHoldPeriods()`: Daily scheduled job releasing matured hold payouts to referrer wallets via `WalletService::credit(..., 'referral_bonus_paid')` and dispatching `referral.reward_paid`.
    - `handleClawback($refType, $refId, $reason)`:
      - Pending payouts -> Cancelled with zero wallet action and `referral.reward_cancelled` notification.
      - Paid payouts -> Reversed from wallet (falling back to forced negative adjustment if insufficient) and `referral.reward_clawed_back` notification.
    - `handleReferrerAccountDeleted($referrerId)`: Cancels pending payouts with reason `'referrer_account_deleted'`.
    - `getAdminOverride($user)` / `setAdminOverride($user, $kind, $value, $isActive)`.
    - `getAgentReferralStats($user)`: 360° analytics for agent dashboard.
- **Scheduled Commands**:
  - Registered `referrals:process-payouts` in `routes/console.php` running daily at 00:15.
- **Hooks & Listeners**:
  - `RegisteredUserController::store()`: Auto-generates referral code and links referee.
  - `ActivateSubscriptionOnPaymentSuccess` & `SubscriptionCheckoutController`: Checks qualifying subscription payments.
  - `CreditWalletOnPaymentSuccess`: Checks qualifying top-up payments.
  - `PaymentVerificationService::refund()`: Triggers clawback on refunded payments.
  - `PlanChangeService::downgradePlan()`: Triggers clawback on mid-cycle downgrades.
  - `AdminUserController::purgeUser()`: Cleans up pending payouts on referrer account deletion.
- **Admin & Agent UI**:
  - Admin Referral Settings (`/admin/referrals/settings`): Form for platform defaults (trigger, reward kind/value, min amount, hold period).
  - Admin Activity Log (`/admin/referrals/activity`): Filterable audit ledger by status, referrer, referee, and date.
  - Admin Top Referrers (`/admin/referrals/top-referrers`): Performance leaderboard with conversion metrics.
  - Admin Per-Agent Override: Integrated into `/admin/wallets/{user}` view.
  - Agent Dashboard (`/referrals`): Code and invite link copy box, 1-click WhatsApp share button, stats cards, and regenerate modal.
  - Registration Page (`/register`): Optional referral code input, auto-populated from `?ref=...` query string.
- **Notifications**:
  - Added 4 routine notification templates to `NotificationTemplateService`: `referral.reward_pending`, `referral.reward_paid`, `referral.reward_cancelled`, `referral.reward_clawed_back`.

### 8.3 What Was Skipped (Out of Scope per PRD Section 12)
- True recurring payouts (rejected in favor of one-time payout with hold period).
- Complex gamification tiers / multi-level referral trees.
- IP / device fingerprinting fraud engines.

### 8.4 Open Items & Assumptions Made (PRD Section 13)
- `hold_period_days`: Used placeholder default of **7 days** (configurable from `/admin/referrals/settings`).
- `reward_trigger`: Used default of **`subscription`** (first subscription payment).
- `reward_kind`: Used default of **`percentage`** with value **10%**.
- `minimum_qualifying_amount`: Used default of **₹100.00**.
- Referrer Suspension Rule: Pending rewards mature normally during suspension; only account deletion cancels pending rewards with reason `'referrer_account_deleted'`.

### 8.5 Automated Test Verification
- Created `tests/Feature/ReferralSystemTest.php` with 9 comprehensive tests covering all 8 required edge cases:
  1. Self-referral rejection.
  2. Sub-minimum qualifying amount rejection.
  3. Dynamic reward trigger matching.
  4. Refund during hold period (cancellation with ₹0 wallet action).
  5. Refund after hold period (clawback wallet reversal).
  6. Code regeneration isolation.
  7. Referrer account deletion cancellation.
  8. Admin per-agent custom reward override precedence.
  9. Registration auto-generation and signup link.
- **Full Platform Test Suite**: **71/71 core tests passed (463 assertions), 100% PASS rate**.



