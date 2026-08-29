# Billing & Subscription System — PRD
**Product:** NBPDCL SaaS Billing Platform
**Module:** Billing & Subscription System
**Version:** 1.0
**Status:** Draft for Development
**Depends on:** Payment Gateway System (done), Wallet System (done), Plan Management System (done)

> **Terminology:** Agent / User (`user_id`) throughout. No "Tenant" anywhere.

---

## 1. Purpose

This system owns the **subscription lifecycle** — the state machine that governs whether an Agent's account is fully active, at risk, or restricted, and drives the renewal process (manual or optional auto) using the Wallet System.

This is the system that turns "Agent has a Plan" (Plan Management System) into "Agent's subscription is currently in X state, expires on Y date, and here's what happens next."

---

## 2. Subscription Lifecycle — State Machine

```
ACTIVE → RENEWAL_DUE → GRACE_PERIOD → SUSPENDED
```

There is no separate `READ_ONLY` state — **SUSPENDED = read-only**, confirmed as the same thing, not a further state after suspension.

### 2.1 State Definitions & Agent Access

| State | Trigger | Agent Access |
|---|---|---|
| **ACTIVE** | Subscription within paid period | Full access, no restrictions |
| **RENEWAL_DUE** | `billing_end` date reached, renewal not yet completed | **Full access**, with a persistent warning banner |
| **GRACE_PERIOD** | Renewal due date passed without payment, within grace window | **Full access**, with a persistent warning banner (same access as RENEWAL_DUE — the only difference is urgency of messaging and countdown) |
| **SUSPENDED** | Grace period expired without payment | **Read-only.** Historical data, past bills, past cycles all remain viewable. No new MRU creation, no new cycle creation, no processing/download, no consumer edits. |

```
ACTIVE
  │ billing_end reached, not renewed
  ▼
RENEWAL_DUE  (full access + warning banner)
  │ grace period window elapses without payment
  ▼
GRACE_PERIOD  (full access + stronger warning banner + countdown)
  │ grace period expires without payment
  ▼
SUSPENDED  (read-only)
```

**Recovery:** Payment at any point (RENEWAL_DUE or GRACE_PERIOD) returns the subscription immediately to ACTIVE with a new `billing_end` date. Payment even after SUSPENDED immediately restores to ACTIVE — suspension is not punitive/permanent, it just reflects unpaid status.

---

## 3. Grace Period Configuration

```
Default: 3 days
Admin can configure ANY value, including 0 days
(0 days = subscription moves straight from RENEWAL_DUE to
 SUSPENDED with no grace window at all — Admin's choice)

Configurable at:
- Platform-wide default (Admin Settings)
- Per-Plan override (optional — a Plan can specify its own grace
  period different from the platform default)
```

Same pattern as the MRU auto-lock timeout (config + SystemSetting override), for consistency across the platform.

---

## 4. Renewal Trigger — Manual by Default, Auto Optional

```
Default behavior: NOT automatic.

On billing_end date:
→ System does NOT attempt wallet deduction automatically
→ Subscription moves to RENEWAL_DUE
→ Agent sees a popup/banner: "Your subscription needs renewal"
→ Banner links to the Renewal page
→ Agent must actively click through and confirm renewal
   (same screen/flow as the Renewal Summary from Plan Management PRD
   — extra MRU yes/no prompt, wallet deduction, etc.)

Optional: Agent can opt into AUTO-RENEWAL (a toggle in their account
settings). If enabled:
→ System automatically attempts wallet deduction on billing_end date
→ If wallet balance sufficient → renewal happens silently, no popup
→ If wallet balance insufficient → falls through to RENEWAL_DUE
   state exactly as if auto-renewal wasn't enabled — no PG mandate
   attempt at all (per the earlier confirmed decision: wallet-only,
   no mandate fallback)
```

This matches the earlier confirmed decision precisely: **wallet-only renewal, no automatic PG mandate charge attempt.** Auto-renewal is just "auto-attempt-wallet-deduction," not "auto-charge-card."

---

## 5. Mid-Cycle Plan Upgrade

### 5.1 Confirmed Rules

```
✅ Agent CAN upgrade their plan at any point mid-cycle
✅ New plan becomes active IMMEDIATELY upon upgrade confirmation
✅ Pricing uses day-based proration (see 5.2)
✅ Agent pays only the calculated difference, deducted from Wallet
✅ New billing_end date does NOT change due to upgrade —
   the cycle continues on its original schedule, just now
   billed at the new plan's rate from this point forward
```

### 5.2 Proration Calculation

```
Given:
  old_plan_price       = price Agent is currently locked into
  new_plan_price        = target plan's price for the SAME duration
                          Agent is currently subscribed to
  total_days_in_cycle   = length of current billing period
  days_remaining        = days left until billing_end from the
                          moment of upgrade

Step 1 — Unused value of current plan:
  old_plan_credit = old_plan_price × (days_remaining / total_days_in_cycle)

Step 2 — Remaining-period cost of new plan:
  new_plan_cost = new_plan_price × (days_remaining / total_days_in_cycle)

Step 3 — Amount due:
  amount_due = new_plan_cost − old_plan_credit

Step 4 — Charge:
  If amount_due > 0 → deduct from Wallet (WalletService::debit())
  If amount_due <= 0 → this is a downgrade scenario, not covered by
  this upgrade flow (see 5.4)
```

**Worked example:**
```
Agent on Starter ₹299/month, 15 of 30 days used, 15 remaining
Upgrading to Basic ₹599/month

old_plan_credit = 299 × (15/30) = ₹149.50
new_plan_cost   = 599 × (15/30) = ₹299.50
amount_due      = 299.50 − 149.50 = ₹150.00

Agent pays ₹150.00 from wallet → Basic plan active immediately
→ Agent's included MRU/Consumer quota immediately updates to
  Basic's limits for the remainder of this cycle
→ billing_end date UNCHANGED — next renewal still calculates
  full Basic price for a fresh cycle
```

### 5.3 What Happens to Locked Quota Snapshot on Upgrade

```
agent_subscriptions record is updated (not replaced) with:
  - plan_id → new plan
  - included_mrus_locked → new plan's included MRU count
  - included_consumers_locked → new plan's included consumer count
  - extra_mru_rate_locked / extra_consumer_rate_locked → new plan's rates
  - base_price_paid → recorded as the NEW plan's price (for future
    renewal reference), while the proration charge itself is logged
    separately as a one-time adjustment transaction

Important: any MRUs the Agent already has that were LOCKED due to
being over the OLD plan's quota should be re-evaluated immediately
after upgrade — if the new plan's higher quota now covers them,
they should auto-unlock without requiring a separate unlock payment.
```

### 5.4 Mid-Cycle Downgrade

Downgrade is allowed mid-cycle, but is gated by **current usage**, not just quota numbers on paper.

**Eligibility Rule:**
```
Agent can downgrade to a new plan ONLY IF their currently
active MRU count fits within the new plan's included MRU quota.

Example — Eligible:
  Current plan: 10 MRU included
  Active MRUs in use: 4
  Downgrading to: 5 MRU plan
  → 4 ≤ 5 → ✅ Eligible, downgrade proceeds immediately

Example — Not Eligible (as-is):
  Current plan: 10 MRU included
  Active MRUs in use: 6
  Downgrading to: 5 MRU plan
  → 6 > 5 → ❌ Blocked

  System response: "You have 6 active MRUs, but the new plan
  only includes 5. Lock or delete at least 1 MRU to proceed."

  Agent must lock/deactivate (or delete) enough MRUs to bring
  active count to 5 or fewer → THEN the downgrade becomes eligible
  and can be confirmed.
```

**Note:** This uses the same MRU lock mechanism already built in the Plan Management System (`Mru.status = 'locked'`) — no new lock concept needed here. A locked MRU doesn't count toward "active" usage for this eligibility check, consistent with how locked MRUs are already excluded from quota consumption elsewhere in the platform.

This eligibility check applies to **MRU quota only** — Consumer quota is not a standing count (it resets every cycle at cycle-creation, per Plan Management PRD section 4.2), so there's nothing to "fit" retroactively; the next cycle created under the downgraded plan will simply use the new, lower consumer quota going forward.

**Proration — Mirrors Upgrade Math, Opposite Direction:**
```
Same day-based proration formula as upgrade (section 5.2):

  old_plan_credit = old_plan_price × (days_remaining / total_days_in_cycle)
  new_plan_cost   = new_plan_price × (days_remaining / total_days_in_cycle)
  adjustment       = old_plan_credit − new_plan_cost

Since new_plan_price < old_plan_price for a downgrade,
adjustment will be POSITIVE (unlike upgrade, where amount_due
is what the Agent pays).

KEY DIFFERENCE FROM UPGRADE:
  Upgrade   → Agent PAYS the adjustment amount (wallet debit)
  Downgrade → Agent is CREDITED the adjustment amount (wallet credit)
```

**Worked example:**
```
Agent on Basic ₹599/month, 15 of 30 days used, 15 remaining
Downgrading to Starter ₹299/month (and MRU eligibility check passed)

old_plan_credit = 599 × (15/30) = ₹299.50
new_plan_cost   = 299 × (15/30) = ₹149.50
adjustment      = 299.50 − 149.50 = ₹150.00

→ ₹150.00 CREDITED to Agent's wallet immediately
→ Starter plan active immediately
→ Agent's included MRU/Consumer quota immediately updates to
  Starter's (lower) limits for the remainder of this cycle
→ billing_end date UNCHANGED — next renewal calculates full
  Starter price for a fresh cycle
```

**Sequencing when downgrade is blocked:**
```
1. Agent requests downgrade → system checks active MRU count
2. If active MRU count > new plan's included MRU quota → BLOCKED,
   show list of active MRUs, Agent must lock/delete enough to fit
3. Once active count fits within new plan's quota → Agent confirms
   downgrade → proration credit applied to wallet → plan switched
   immediately, per section 5.3's snapshot-update logic (same
   mechanism as upgrade, just crediting instead of debiting)
```

---

## 6. Database Schema

```sql
-- Extends agent_subscriptions from Plan Management System
-- (adding lifecycle-specific fields, not duplicating the table)
ALTER TABLE agent_subscriptions ADD COLUMN:
├── lifecycle_status       -- 'active' | 'renewal_due' | 'grace_period' | 'suspended'
├── grace_period_days       -- resolved value (plan override or platform default)
├── grace_period_ends_at    -- calculated timestamp, nullable until RENEWAL_DUE hits
├── auto_renewal_enabled    -- boolean, Agent's own toggle
├── suspended_at            -- nullable timestamp
└── last_state_change_at    -- timestamp, for audit/debugging

-- Every renewal attempt (successful or failed), separate from payments table
renewal_attempts
├── id
├── agent_subscription_id
├── attempt_type            -- 'manual' | 'auto'
├── amount_charged
├── wallet_transaction_id   -- nullable if failed
├── status                  -- 'success' | 'insufficient_balance' | 'failed'
├── attempted_at
└── created_at

-- Mid-cycle upgrade log — proration math audit trail
plan_upgrade_log
├── id
├── agent_subscription_id
├── from_plan_id
├── to_plan_id
├── old_plan_credit
├── new_plan_cost
├── amount_charged
├── wallet_transaction_id
├── days_remaining_at_upgrade
├── total_days_in_cycle
└── created_at
```

---

## 7. Wallet Integration

```
Every renewal charge, auto-renewal attempt, and upgrade proration
charge goes through WalletService::debit(), exactly like Plan
Management's overage charges.

If debit() returns INSUFFICIENT_BALANCE:
  - Manual renewal attempt → Agent shown top-up prompt, subscription
    stays in RENEWAL_DUE (or moves there if not already)
  - Auto-renewal attempt → silently falls to RENEWAL_DUE, Agent
    notified (event fired for Notification System), no PG mandate
    attempted, per confirmed wallet-only policy

If debit() returns WALLET_FROZEN:
  - Renewal blocked entirely regardless of balance — Agent shown
    "contact support," same pattern as Plan Management's frozen-wallet
    handling
```

---

## 8. Admin Panel Capabilities

```
✅ View any Agent's current lifecycle_status and grace_period_ends_at
✅ Manually force a state change (e.g. manually reactivate a
   SUSPENDED Agent as a support gesture, with mandatory reason logged)
✅ Set platform-wide default grace period days
✅ Set per-Plan grace period override
✅ View renewal_attempts history per Agent
✅ View plan_upgrade_log per Agent (proration audit trail)
```

---

## 9. Out of Scope / Open Items

| Item | Status |
|---|---|
| Whether GRACE_PERIOD and RENEWAL_DUE need visually distinct banners, or one shared banner style with different text | ❌ Not decided — recommend distinct styling (RENEWAL_DUE = informational, GRACE_PERIOD = urgent/red) but confirm |
| Does Admin get notified when an Agent enters SUSPENDED state (support visibility)? | ❌ Not decided — fire event either way, notification channel is separate system's job |
| GST invoice generation for renewal/upgrade payments | ❌ Out of scope — **Invoicing & GST System** |
| Actual notification delivery (banners are UI-only; SMS/email/push reminders before grace period ends) | ❌ Out of scope — **Notification System** |
