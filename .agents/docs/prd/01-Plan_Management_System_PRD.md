# Plan Management System — PRD
**Product:** NBPDCL SaaS Billing Platform
**Module:** Plan Management System
**Version:** 1.0
**Status:** Draft for Development
**Depends on:** Payment Gateway System (done), Wallet System (done)
**Required by:** Billing & Subscription System (next)

> **Terminology:** This platform uses **Agent / User (`user_id`)** throughout. "Tenant" is never used.

---

## 1. Purpose

This system is the single source of truth for everything related to **subscription plans** — their pricing, included quotas, extra-usage rates, duration discounts, and the overage/lock rules that govern what happens when an Agent exceeds their plan.

Nothing about pricing, quotas, or rates is hardcoded anywhere in the application. Every number is created and edited by Admin through this system.

---

## 2. Core Principle

```
Admin creates a Plan.
Admin can modify or delete a Plan at any time.
Existing Agent subscriptions are NEVER affected by a Plan edit —
their rates are locked at the moment they purchased/renewed.
Only new purchases/renewals see the updated Plan numbers.
```

This is the same locked-at-purchase principle already agreed for the platform's admin control model.

---

## 3. Plan Structure

A Plan is fully dynamic. Admin sets every field below when creating one. Tier names (Starter, Basic, Enterprise) are just labels — the system does not hardcode tier behavior; it only reads whatever values Admin configures.

| Field | Description |
|---|---|
| Plan name | e.g. "Starter" |
| Description | Free text |
| Base price | Per month, before duration discount |
| Included MRUs | Quota count |
| Included Consumers | Quota count |
| Extra MRU rate | ₹ per MRU beyond quota |
| Extra Consumer rate | ₹ per Consumer beyond quota |
| Status | Active / Inactive |

### 3.1 Duration Pricing (Per Plan)

Admin sets a price and discount % for each duration option, per plan:

| Duration | Discount % | Final Price (auto-calculated, Admin can override) |
|---|---|---|
| 1 Month | Admin-set (suggested default: 0%) | |
| 2 Months | Admin-set (suggested default: 5%) | |
| 3 Months | Admin-set (suggested default: 10%) | |
| 6 Months | Admin-set (suggested default: 15%) | |
| 12 Months | Admin-set (suggested default: 20%) | |

These percentages are **suggested defaults only** for the Admin UI — not hardcoded values. Admin can change every number per plan.

### 3.2 Extra Rate Discounts Per Duration (Optional)

Admin may optionally set a discounted extra-usage rate for longer durations (e.g. 12-month subscribers pay less per extra consumer than 1-month subscribers). If left blank, the base extra rate applies to all durations.

---

## 4. Quota Consumption Model

This is the core mechanic confirmed across this conversation. Two independent quota types, each consumed differently.

### 4.1 MRU Quota

```
Trigger: MRU creation
Effect: Immediately consumes 1 MRU slot from included quota
Release: Deleting an MRU releases its slot back to available quota
Reset: Does NOT reset monthly — MRU quota is a standing count,
       not a per-cycle count (an MRU, once created, exists until deleted)
```

### 4.2 Consumer Quota

```
Trigger: Billing Cycle creation for an MRU
Effect: Consumes Consumer quota equal to the number of consumers
        currently linked to that MRU, for that cycle
Reset: Resets EVERY billing period (monthly) — a fresh count is
       taken at each new cycle's creation
Recalculation: If consumers are added/removed from the MRU AFTER
        cycle creation, the quota does NOT auto-update. Agent must
        trigger a SYNC action; quota recalculates only at that
        sync/processing moment, not passively
```

---

## 5. Overage Handling — Two Independent Systems

MRU overage and Consumer overage are handled by **completely separate mechanisms.** They are never combined into one charge and never share a lock/gate.

### 5.1 MRU Overage — Pay-Gate + Recurring Renewal + Lock

**Step 1 — Creation-time pay gate**
```
Agent creates an MRU beyond included quota
→ BLOCKED immediately
→ Popup: "This exceeds your plan's MRU limit.
           Pay ₹[extra_mru_rate] to continue."
→ Agent pays via wallet deduction (or tops up if insufficient)
→ MRU created, flagged as "over-quota"
```

**Step 2 — Renewal-time recurring charge**
```
At every renewal, system counts current total active MRUs.
For any MRU still beyond the plan's included quota:

Popup/Prompt shown at renewal:
   "You used 1 extra MRU last month.
    Add it to this renewal? [Yes] [No]"

IF YES:
   → extra_mru_rate charged again, added to renewal total
   → All MRUs remain fully active

IF NO:
   → Agent must select which MRU(s) to LOCK, bringing active
     count back within quota
   → If Agent does not select within the decision window →
     system AUTO-LOCKS the MOST RECENTLY CREATED MRU (default rule)
```

**Step 3 — Locked MRU state**
```
Locked MRU permissions:
✅ View / Read (consumer list, historical data, past cycles)
✅ Rename
✅ Delete permanently
✅ Add consumer (one-by-one or bulk)
✅ Remove consumer

❌ Modify consumer details
❌ Create a new billing cycle
❌ Process / download / extract PDFs for any cycle

Unlock: Agent can unlock a locked MRU at ANY time (not just at
        renewal) by paying extra_mru_rate through the same
        pay-gate popup, triggered when they attempt to create a
        cycle on a locked MRU.
```

**Step 4 — Unlock leads directly into Consumer gate (sequential, independent)**
```
Agent pays to unlock MRU → MRU active
Agent immediately tries to create the cycle again →
System now separately checks CONSUMER quota for this cycle
→ If consumer count for this cycle exceeds remaining quota →
   Consumer pay-gate fires (see 5.2) — completely independent charge
```

### 5.2 Consumer Overage — Pay-Gate Only, Every Cycle, No Lock

```
Trigger: Cycle creation where linked consumer count exceeds
         Agent's remaining included Consumer quota for this period

Action: BLOCKED immediately
Popup: "This cycle has [X] consumers, but you have [Y] remaining
        in your quota. Pay ₹[extra_ca_rate × (X−Y)] to continue."

Agent pays via wallet deduction → cycle creation proceeds

Renewal: NO separate renewal-time charge for Consumer overage.
         This gate fires fresh every single cycle/month —
         charging again at renewal would be double-charging.

Lock: Consumers are NEVER locked. There is no "locked consumer"
      state. Overage is always resolved fully at the moment of
      cycle creation, or the cycle simply isn't created.
```

---

## 6. Wallet Integration

All pay-gate charges and renewal charges deduct from the Agent's Wallet (per Wallet System PRD, `WalletService::debit()`).

```
If wallet balance is insufficient at any pay-gate:
→ Popup shows: "Insufficient wallet balance. Top up ₹[shortfall]
                to continue."
→ Agent redirected to top-up flow (Payment Gateway System)
→ Once topped up, action retried automatically or manually
```

If `WalletService::debit()` returns `WALLET_FROZEN` (per Wallet System PRD), the action is blocked entirely regardless of balance, and Agent is shown a "contact support" message rather than a top-up prompt.

---

## 7. Renewal Screen — Full Behavior

At renewal, the Agent sees:

```
┌─────────────────────────────────────────────┐
│  Renewal Summary                             │
│                                               │
│  Base Plan: Starter — ₹299/month             │
│                                               │
│  Extra MRU used last month: 1                │
│  Add to this renewal? [Yes] [No]             │
│                                               │
│  (Consumer overage is NOT shown here —       │
│   it was already resolved per-cycle,         │
│   nothing carries forward)                   │
│                                               │
│  Total Renewal Amount: ₹[base + extra_mru]   │
│  Wallet Balance: ₹[current]                  │
│                                               │
│  [Confirm Renewal]                           │
└─────────────────────────────────────────────┘
```

If Agent selects **No** on the extra MRU prompt, they are immediately shown the MRU selection list to choose which one(s) to lock, before renewal can be confirmed.

---

## 8. Database Schema

```sql
-- Master plan definition
plans
├── id
├── name
├── description
├── included_mrus
├── included_consumers
├── extra_mru_rate
├── extra_consumer_rate
├── is_active
├── created_at
└── updated_at

-- Duration-based pricing per plan
plan_durations
├── id
├── plan_id                 -- FK plans
├── duration_months         -- 1, 2, 3, 6, 12
├── discount_percent
├── final_price             -- auto-calculated, admin-overridable
├── extra_mru_rate          -- nullable override for this duration
├── extra_consumer_rate     -- nullable override for this duration
└── timestamps

-- Locked snapshot of what an Agent actually purchased
-- (never changes even if the plan itself is edited later)
agent_subscriptions
├── id
├── user_id                 -- FK users (Agent)
├── plan_id                 -- FK plans (for reference only)
├── duration_months
├── base_price_paid         -- locked
├── included_mrus_locked    -- locked
├── included_consumers_locked -- locked
├── extra_mru_rate_locked   -- locked
├── extra_consumer_rate_locked -- locked
├── billing_start
├── billing_end
├── status                  -- active / renewal_due / grace_period / suspended / read_only
└── timestamps

-- MRU-level quota tracking
mrus
├── id
├── user_id
├── name
├── code
├── status                  -- 'active' | 'locked'
├── locked_reason           -- 'over_quota_unpaid', nullable
├── locked_at               -- nullable
├── unlocked_at             -- nullable
├── is_over_quota           -- boolean flag, drives renewal prompt
└── timestamps

-- Per-cycle consumer quota consumption record
billing_cycles
├── id
├── mru_id
├── user_id
├── cycle_month
├── cycle_year
├── consumer_count_at_creation
├── included_quota_used
├── extra_consumer_count
├── extra_consumer_charge
├── status
└── timestamps

-- Every overage payment event (MRU or Consumer), tied back to wallet
plan_overage_charges
├── id
├── user_id
├── charge_type             -- 'mru_creation' | 'mru_renewal' | 'mru_unlock' | 'consumer_cycle'
├── reference_type          -- 'mru' | 'billing_cycle'
├── reference_id
├── amount
├── wallet_transaction_id   -- FK to wallet_transactions
└── created_at
```

---

## 9. Admin Panel Capabilities

```
✅ Create / Edit / Deactivate / Delete a Plan
✅ Set base price, included MRUs, included Consumers
✅ Set extra MRU rate, extra Consumer rate
✅ Set duration pricing table (1/2/3/6/12 months, discounts)
✅ Set optional extra-rate discounts per duration
✅ Soft-delete plan (hides from new signups, existing Agents unaffected)
✅ Force-delete plan (requires migrating existing subscribers to
   another plan first, or forcing it through with explicit confirmation)
✅ View all Agents on a given plan
✅ Migrate an Agent from one plan to another manually
✅ View/unlock any Agent's locked MRUs manually (support scenarios)
✅ View full overage charge history per Agent
```

Admin edits to a live Plan **never** affect `agent_subscriptions` records already locked in. Only new purchases/renewals read the updated Plan values.

---

## 10. What This System Does NOT Do (Out of Scope)

- Actual wallet debit/credit mechanics → **Wallet System** (already built, this system calls it)
- Payment collection (PG/Manual UPI/Bank Transfer) → **Payment Gateway System** (already built)
- Subscription lifecycle state machine (grace period, suspension timing) → **Billing & Subscription System** (next)
- GST invoice generation for renewal payments → **Invoicing & GST System**
- Actual notification delivery → **Notification System** (this system only fires events)

---

## 11. Open Items Requiring Confirmation

| Item | Status |
|---|---|
| Exact default duration discount percentages (0/5/10/15/20 suggested, not final) | ❌ Admin to set at plan creation, no platform-wide hardcoded default required |
| Grace period length before an unresolved "add extra MRU? yes/no" prompt auto-locks the newest MRU | ❌ Not decided — needs a specific time window (e.g. 24 hours, 3 days) |
| Whether Admin gets notified when an Agent's MRU gets auto-locked (support visibility) | ❌ Not decided |
| Whether a locked MRU shows a persistent banner/reminder to the Agent every time they log in | ❌ Not decided — recommended yes, for UX clarity |
