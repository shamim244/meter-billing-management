# Notification System — PRD
**Product:** NBPDCL SaaS Billing Platform
**Module:** Notification System
**Version:** 1.0
**Status:** Draft for Development
**Depends on:** All prior systems (Payment Gateway, Wallet, Plan Management, Billing & Subscription, Usage Tracking) — this system only LISTENS to events they already fire, never triggers business logic itself

> **Terminology:** Agent / User (`user_id`) throughout. No "Tenant" anywhere.

---

## 1. Purpose

Every prior system in this platform fires domain events (`SubscriptionSuspendedEvent`, `WalletLowBalanceEvent`, `MruLockedEvent`, `PaymentSuccessEvent`, etc.) but **none of them are actually delivered anywhere.** This system's entire job is to listen for those events and deliver them to the Agent (and Admin, where relevant) through actual channels.

**This system never creates new business logic or triggers.** It only consumes events that already exist across the platform and routes them to a channel.

---

## 2. Core Architecture Principle — Channel-Pluggable by Design

This is the single most important architectural requirement for this system, repeated because it shapes every decision below:

```
Adding a new channel later (WhatsApp, SMS, Push) must be:
  - A new "channel driver" class implementing a shared interface
  - A config/database entry enabling it
  - NEVER a rewrite of routing logic, templates, or Agent
    preference handling

The routing logic, template system, and Agent preference system
must all be channel-AGNOSTIC — they operate on abstract concepts
("send this notification via whatever channels are enabled for
this event + this Agent's preferences"), not hardcoded to
"email" or "in-app" specifically.
```

---

## 3. Channels — V1 Scope

| Channel | Status |
|---|---|
| In-App (dashboard banner/notification center) | ✅ Build now — always succeeds, just a DB record |
| Email | ✅ Build now — see section 6 for provider config |
| Push (OneSignal) | 🔶 Build now ONLY if integration proves straightforward during development; otherwise defer. This is explicitly NOT a hard requirement for V1 — do not spend significant effort here if it's not simple. |
| WhatsApp | ❌ Not this version — architecture must allow adding later without rework |
| SMS | ❌ Not this version — same as above |

---

## 4. Event Priority & Channel Routing

Every event is tagged with a priority level. Priority determines which channels fire, not the event type directly — this keeps the system extensible (a future WhatsApp channel just gets added to the "critical" routing rule, no per-event rework needed).

### 4.1 Priority Levels

```
CRITICAL  → In-App + Email (+ Push if built, + future WhatsApp/SMS
            when added) — fire on ALL enabled channels simultaneously
ROUTINE   → In-App only by default (Agent can opt into Email too,
            see section 7)
```

### 4.2 Event Classification (Confirmed)

| Event | Priority |
|---|---|
| Subscription enters GRACE_PERIOD | CRITICAL |
| Subscription SUSPENDED | CRITICAL |
| Wallet critical balance threshold crossed | CRITICAL |
| Payment success confirmation | ROUTINE |
| Monthly usage summary ready | ROUTINE |
| MRU locked due to overage | ROUTINE |
| MRU auto-locked (no action taken at renewal) | ROUTINE |
| Wallet low balance (non-critical threshold) | ROUTINE |
| Manual payment approved/rejected (admin action) | ROUTINE |
| Subscription reactivated | ROUTINE |
| Plan upgraded / downgraded confirmation | ROUTINE |

**This table is admin-editable** (see section 8) — priority-to-channel mapping should not be hardcoded per event in application code; it should be configurable, so Admin can promote/demote an event's urgency later without a code deploy.

---

## 5. Notification Event Catalog (What Already Exists, To Be Wired)

This system does not invent these events — it subscribes to what's already fired:

```
From Payment Gateway System:
  PaymentSuccessEvent, PaymentFailedEvent, ManualPaymentSubmittedEvent,
  ManualPaymentApprovedEvent, ManualPaymentRejectedEvent,
  PaymentMandateFailedEvent

From Wallet System:
  WalletCreditedEvent, WalletDebitedEvent, WalletLowBalanceEvent,
  WalletCriticalBalanceEvent, WalletInsufficientForRenewalEvent,
  WalletFrozenEvent, WalletUnfrozenEvent

From Plan Management System:
  MruLockedEvent (and its unlock counterpart)

From Billing & Subscription System:
  SubscriptionRenewalDueEvent, SubscriptionEnteredGracePeriodEvent,
  SubscriptionSuspendedEvent, SubscriptionReactivatedEvent,
  RenewalFailedInsufficientBalanceEvent, PlanUpgradedEvent,
  PlanDowngradedEvent

From Usage Tracking System:
  (Monthly summary ready — may need a new lightweight event fired
  from a scheduled job once the monthly summary is computed, since
  Usage Tracking System is read-only and doesn't currently fire
  this on its own; confirm this is a NEW small addition needed
  there, not something this system invents independently)
```

---

## 6. Email Configuration

```
Provider Architecture: A PROVIDER REGISTRY, not a fixed single choice.

Admin can:
  - Add multiple email providers (SMTP, Resend, Brevo, and any
    future provider type added to the codebase)
  - Configure MULTIPLE INSTANCES of providers if needed (e.g. two
    different SMTP accounts, or SMTP + Resend + Brevo all configured
    simultaneously)
  - Set a PRIORITY ORDER / fallback chain across configured providers
  - Enable/disable any configured provider without removing its config

Fallback Chain Behavior:
  1. System attempts send via the HIGHEST PRIORITY enabled provider
  2. If that provider's send() call fails (API error, timeout,
     connection refused, etc.) → automatically attempt the NEXT
     enabled provider in priority order
  3. Continue down the chain until one succeeds, or all configured
     providers have been exhausted
  4. Only after ALL providers in the chain fail does this count as
     a genuine "failed" delivery (feeding into the retry/backoff
     logic in section 9 — the chain fallback happens WITHIN a single
     attempt, retry/backoff happens ACROSS attempts over time)

This means adding a new provider type in the future (e.g. a 4th
option) requires only:
  - A new driver class implementing the shared send() contract
  - Admin registering it through the UI (see below)
  NOT any change to routing, retry, or dispatch logic.

Built-in provider types (drivers) at V1:
  1. SMTP         (PRIMARY / default focus — any SMTP server:
                    hosting provider SMTP, Google Workspace relay,
                    or similar; Laravel supports this natively,
                    zero external API keys required to get started)
  2. Resend       (transactional email API —
                    github.com/resend/resend-laravel,
                    resend.com/docs/introduction)
  3. Brevo        (transactional email API —
                    github.com/getbrevo/brevo-php,
                    developers.brevo.com/guides/php)

  Amazon SES is explicitly NOT used — removed from scope.

Since SMTP is the primary focus, it should be the DEFAULT enabled
provider instance out of the box (priority 1), with Resend/Brevo
available as optional additional instances Admin can register for
better deliverability or as fallback options later — not the other
way around.

Admin Provider Console (new section, see below) lets Admin:
  - Add a new provider instance (choose driver type, enter its
    credentials/config — API key, or SMTP host/port/user/pass)
  - Set/reorder priority among all configured provider instances
  - Enable/disable any instance
  - Test-send a sample email through a specific provider instance
    before relying on it
  - View recent delivery attempts per provider instance (useful for
    spotting "Provider X has been failing a lot lately")

Domain (development): nexgenhub.site
Domain (production):  TBD — either a new domain or a subdomain of
          nexgenhub.site (e.g. billing.nexgenhub.site). Domain
          itself is a config value, not hardcoded anywhere.

Sender Identity:
  Display Name: "NBPDCL Billing Platform"
  From Address (dev): notifications@nexgenhub.site
  From Address (prod): notifications@[final-domain, TBD]
  (Sender identity can be set per-provider-instance if needed, or
  platform-wide as a default — platform-wide default is sufficient
  for V1, per-provider override is a nice-to-have, not required)

Requires: SPF + DKIM DNS records configured for whichever domain
          is finalized, before production sending begins (applies
          to Resend/SES; SMTP deliverability depends on the SMTP
          provider's own reputation/setup).
```

---

## 7. Agent Notification Preferences

```
Agent can control notification settings per event CATEGORY
(not necessarily per individual event — keep this simple):

Example preference screen:
┌─────────────────────────────────────────────┐
│  Notification Preferences                    │
│                                               │
│  Billing & Subscription Alerts               │
│    In-App:  [always on, cannot disable]      │
│    Email:   [x] Enabled                      │
│                                               │
│  Wallet Alerts                                │
│    In-App:  [always on, cannot disable]      │
│    Email:   [x] Enabled                      │
│                                               │
│  Usage Reports & Summaries                    │
│    In-App:  [ ] Enabled                       │
│    Email:   [ ] Enabled                       │
└─────────────────────────────────────────────┘
```

**Important constraint:** Agent can turn OFF the Email channel for ROUTINE events, but **CANNOT turn off CRITICAL event delivery entirely.** They may be able to choose which enabled channels a CRITICAL event uses, but at least one delivery channel must always remain active for CRITICAL events — an Agent should never be able to fully silence "your account is suspended."

```
Enforcement: In-App notifications for CRITICAL events are ALWAYS
sent regardless of Agent preference (this is the one channel
that cannot be disabled, ensuring baseline visibility).
```

---

## 8. Admin-Editable Templates & Email Provider Management

Same pattern as your existing Tag System admin console — Admin manages templates and providers through a UI, nothing hardcoded in Blade/PHP strings or `.env` alone.

### 8.1 Notification Templates
```
Admin Template Console:
├── List of all notification event types
├── For each: edit subject line (email), body template (email + in-app)
├── Placeholder/variable support: {agent_name}, {amount}, {plan_name},
│   {grace_period_ends_at}, etc. — merge fields resolved at send time
├── Priority level assignment per event (CRITICAL / ROUTINE) — 
    editable here, not hardcoded (per section 4.2)
├── Channel enablement per priority level (e.g. admin can decide
    CRITICAL = In-App + Email only, or later add Push/WhatsApp
    to that list without code changes)
├── Preview function — see how a template renders with sample data
    before saving
└── Reset to factory default per template (same pattern as Tag
    System's "reset to factory defaults")
```

### 8.2 Email Provider Console (New — Registry Management)
```
Admin Provider Console (/admin/notifications/email-providers):
├── List of all configured provider instances, showing:
│     label, driver type, priority order, enabled/disabled, last
│     used, last failure (if any)
├── [+ Add Provider] — choose driver type (Resend / SES / SMTP /
│     any future type), enter its required config fields (form
│     changes based on driver type selected — API key for Resend/
│     SES, host/port/user/pass for SMTP)
├── Drag-to-reorder or explicit priority number — sets the
│     fallback chain order
├── Enable/disable toggle per instance (disabled instances are
│     skipped in the chain entirely, not just deprioritized)
├── [Test Send] — send a test email through one specific instance,
│     bypassing the chain, to confirm it actually works before
│     relying on it in production
├── Delete instance (blocked if it's the ONLY enabled instance —
│     system should always have at least one working provider,
│     same safety principle as Plan Management System's "can't
│     delete the only active plan" logic)
└── Recent delivery attempts log, filterable per instance —
      shows which provider actually delivered each recent email,
      useful for spotting a provider that's silently degrading
```

---

## 9. Failure Handling & Retry

```
In-App: Never "fails" — it's a database write, not a delivery
         attempt over an external channel

Email:
  - Retry: 3 automatic attempts, exponential backoff
    (e.g. 1 min → 5 min → 15 min between attempts)
  - After 3 failures: mark as permanently failed, log for audit
  - If a CRITICAL event's email fails all 3 attempts:
      → Fire an internal AdminNotificationFailedEvent
      → Surfaces in an Admin "Failed Critical Notifications" panel
      → Reason: an Agent not knowing they've been suspended is a
        real support/trust problem, worth Admin's attention
  - ROUTINE event failures: logged only, no special admin alert
```

---

## 10. Database Schema

```sql
-- Configured email provider instances (the registry itself)
email_provider_instances
├── id
├── driver_type              -- 'smtp' | 'resend' | 'brevo' (extensible —
│                                new driver types just add a new value here)
├── label                     -- Admin-friendly name, e.g. "Primary Resend",
│                                "Backup SMTP - Hostinger"
├── config                     -- JSON, encrypted at rest: API keys,
│                                SMTP host/port/user/pass, whatever
│                                the specific driver_type needs
├── priority                    -- integer, lower = tried first
├── is_enabled
├── last_used_at
├── last_failure_at
├── last_failure_reason
└── timestamps

-- Every notification instance, regardless of channel
notifications
├── id
├── user_id                -- recipient Agent (nullable if Admin-only)
├── event_type              -- e.g. 'subscription.suspended'
├── priority                -- 'critical' | 'routine'
├── title
├── body
├── data                     -- JSON, merge field values used
├── read_at                  -- nullable, for in-app "read" state
└── created_at

-- One row per channel delivery ATTEMPT for a given notification
-- (a single notification can have multiple delivery attempts,
-- one per enabled channel, and for email specifically, one row
-- can track which provider instance within the chain succeeded)
notification_deliveries
├── id
├── notification_id         -- FK notifications
├── channel                  -- 'in_app' | 'email' | 'push' (extensible)
├── email_provider_instance_id  -- FK email_provider_instances, nullable
│                                 (which provider in the chain actually
│                                 delivered it, or attempted last)
├── status                   -- 'pending' | 'sent' | 'failed' | 'permanently_failed'
├── attempt_count
├── last_attempted_at
├── failed_reason             -- nullable
└── created_at

-- Agent's channel preferences per event category
agent_notification_preferences
├── id
├── user_id
├── event_category           -- e.g. 'billing', 'wallet', 'usage_reports'
├── channel                   -- 'email' | 'push' (in_app not stored here —
│                                always on for critical, always available
│                                as a baseline)
├── enabled                   -- boolean
└── updated_at

-- Admin-editable templates
notification_templates
├── id
├── event_type
├── channel                   -- template can differ slightly per channel
├── subject                    -- email only, nullable for in-app
├── body_template              -- with {merge_field} placeholders
├── priority                    -- editable here, drives routing (section 4.2)
├── is_active
└── updated_at
```

---

## 11. Service Layer

```
NotificationDispatchService
├── dispatch(event) — the single entry point every event listener
│     calls. Looks up the event's template + priority, resolves
│     which channels apply (priority rules × Agent preferences),
│     creates the notifications + notification_deliveries records,
│     and hands off to the relevant ChannelDriver(s)

ChannelDriverInterface (contract all channels implement)
├── send(notification): DeliveryResult
   Implementations:
   ├── InAppChannelDriver — writes to notifications table, always succeeds
   ├── EmailChannelDriver — does NOT send directly. It reads the
   │     enabled email_provider_instances in priority order and
   │     delegates to EmailProviderRegistryService (see below) to
   │     walk the fallback chain.
   └── PushChannelDriver — OneSignal, ONLY if built in this pass
       (stub/interface reserved either way, for future WhatsApp/SMS
       drivers to follow the same pattern)

EmailProviderRegistryService
├── getEnabledProvidersInPriorityOrder()
├── sendViaChain(notification) — walks the priority-ordered list,
│     tries EmailProviderDriverInterface::send() on each in turn,
│     stops at first success, records which instance succeeded
│     (or that all failed) on notification_deliveries
├── testSend(email_provider_instance_id, test_recipient) — Admin
│     "send test email" action, bypasses the chain, targets one
│     specific instance directly

EmailProviderDriverInterface (contract each driver type implements)
├── send(to, subject, body, config): bool
   Implementations:
   ├── SmtpDriver     (primary focus — Laravel's native Mailer,
   │                    configured per-instance with host/port/
   │                    encryption/user/pass)
   ├── ResendDriver   (uses resend/resend-laravel package)
   └── BrevoDriver    (uses getbrevo/brevo-php package)
   (A new provider type later = one new class implementing this
   interface + Admin registers an instance of it. No changes needed
   to EmailChannelDriver, EmailProviderRegistryService, or anything
   above this layer.)

NotificationTemplateService (admin-facing)
├── CRUD for notification_templates
├── renderPreview(template, sample_data)
├── resetToDefault(event_type)

AgentPreferenceService
├── getPreferences(user_id)
├── updatePreference(user_id, event_category, channel, enabled)
├── enforces: CRITICAL events cannot be fully disabled
   (In-App always fires regardless of preference for CRITICAL)
```

---

## 12. What This System Does NOT Do (Out of Scope)

```
❌ Does not create any new business logic/triggers — purely reactive
❌ Does not calculate billing, quota, or wallet amounts — only
   displays values already computed elsewhere
❌ WhatsApp / SMS delivery — architecture reserved, not built this pass
❌ GST invoicing — separate system
```

---

## 13. Open Items Requiring Confirmation

| Item | Status |
|---|---|
| Whether Usage Tracking System needs a small addition to fire a "monthly summary ready" event (since it's currently read-only and doesn't fire events on its own) | ❌ Needs verification against actual Usage Tracking System code — confirm whether this event needs to be added there, or if a scheduled job in THIS system can compute readiness independently |
| Exact in-app notification UI placement (bell icon dropdown vs. dedicated notifications page vs. both) | ❌ Not decided — recommend a bell icon + dropdown for quick access, plus a full history page, but confirm |
| Final production email domain (new domain vs. nexgenhub.site subdomain) | ❌ Not decided — use nexgenhub.site for development now, finalize before production launch |
| Whether OneSignal Push gets built in this pass | ❌ Contingent on integration ease during development — default to NOT building if it adds meaningful time, per your stated recommendation |
| How `email_provider_instances.config` (API keys, SMTP passwords) gets encrypted at rest | ❌ Must use Laravel's built-in encrypted casting on this column — flagging explicitly since this is real credential storage, not optional hardening |
