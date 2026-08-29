# Usage Tracking System — PRD
**Product:** NBPDCL SaaS Billing Platform
**Module:** Usage Tracking System
**Version:** 1.0
**Status:** Draft for Development
**Depends on:** Plan Management System (done), Billing & Subscription System (done)

> **Terminology:** Agent / User (`user_id`) throughout. No "Tenant" anywhere.

---

## 1. Purpose & Scope Decision

Billing-relevant usage tracking (MRU/Consumer quota consumption, overage pay-gates, overage charges) is **already fully built** in the Plan Management System. This system does **not** duplicate any of that.

This system's purpose is narrower and deliberately scoped down: turn data that's **already being captured** into visible, useful signal for Agents and Admin — proving ongoing value and surfacing operational issues that matter to a meter-reading contractor's actual work.

**Explicitly out of scope for this version** (not because they're bad ideas, but because there's no stated need for them yet, and building unused tracking is wasted effort):
```
❌ Peak usage tracking as a standalone metric
   (billing already uses cycle-creation snapshots, not peak;
   no other consumer of this data exists yet)
❌ Anomaly detection, forecasting, risk scoring
   (explicitly marked "future, not now" from the start)
```

If either becomes needed later, it's a clean addition on top of this system — not a rework.

---

## 2. What This System Builds

### 2.1 Agent-Facing Monthly Usage Summary ("ROI Dashboard")

Shown on the Agent's dashboard and optionally emailed/notified monthly (delivery mechanism itself is Notification System's job — this system only assembles the data).

```
This Month Summary:
├── Bills processed: count of successful download+extraction
│                      events this billing period (already recorded
│                      via billing_cycles / plan_overage_charges data)
├── MRUs active: count of currently active MRUs for this Agent
├── Data coverage: (consumers successfully processed) /
│                   (total consumers linked to active MRUs) × 100%
├── Flagged consumers: count of consumers currently on
│                        billing_basis = LK or MD (see 2.2)
└── Historical depth: number of consecutive months of ledger
                        history now stored for this Agent
                        (switching-cost/retention indicator)
```

**Data sourcing — nothing new to instrument:**
```
"Bills processed"      → already exists in billing_cycles /
                          consumer processing records
"MRUs active"           → already exists in mrus table
"Data coverage"          → derived from existing consumer +
                          processing records, no new tracking
"Flagged consumers"      → NEW aggregation, see 2.2 below
"Historical depth"       → derived from billing_cycles.created_at
                          span per Agent, no new tracking
```

### 2.2 Billing Basis Tracking & Consecutive-Estimate Detection

Your PDF parser **already extracts** `billing_basis` (OK / LK / MD / Consumer Master) per your original product overview. This system adds:

```
1. A per-consumer billing_basis_history record — one row per
   consumer per billing cycle, storing the extracted billing_basis
   value (this is the ONLY new table this system introduces for
   this feature — everything else is aggregation of existing data)

2. Consecutive-estimate detection:
   For each consumer, check if billing_basis = LK or MD for 2+
   CONSECUTIVE billing cycles in a row.
   → If yes, flag the consumer as "consecutive_estimate_alert"

3. Surface this flag:
   - On the Agent's dashboard (card/table view, per your existing
     4-box ledger UI) — a visible badge/indicator per flagged
     consumer
   - In the monthly summary count (2.1)
   - Optionally filterable/sortable in the existing table view
     (reuse existing priority/sort system already built for
     Submitted/Critical/Doubt/Pending — this is just a new sortable
     flag alongside those, not a new UI paradigm)
```

**Why this matters operationally:** a consumer stuck on estimated billing for multiple months is exactly the kind of thing a meter reader needs to physically visit and correct — this is genuine operational intelligence, not vanity metrics.

### 2.3 Admin-Facing Aggregate View

```
Admin sees the same signals aggregated across ALL Agents:
- Total bills processed platform-wide, this month
- Total consumers currently flagged for consecutive estimates,
  broken down by Agent
- Per-Agent data coverage % (useful for spotting Agents who may
  be struggling or under-processing relative to their MRU count —
  a support/account-health signal)
```

No new charging or quota logic here — this is read-only reporting.

### 2.4 Monthly Status & Tag Report (Primary Requirement)

This is the report you actually need day-to-day — a monthly breakdown by **Review Status** and by **Tag**. Both fields already exist in your platform (Review Status from the verification workflow, Tag from the already-built Bill Tag System) — this report is pure aggregation on top of existing data, no new tracking required.

```
Monthly Report — Review Status Breakdown
├── Submitted: count
├── Critical:  count
├── Doubt:     count
└── Pending:   count

Monthly Report — Tag Breakdown
├── OK:                          count
├── BQC:                         count
├── RCQ:                         count
├── 24days:                      count
└── NOT_APPROVED_PREV_BQC_RQC:   count

(Tag list is NOT hardcoded here — it must read from whatever tags
are currently configured/active in the Admin Tags Console, since
Admin can add/edit/delete tags at any time per the already-built
Tag System. This report must reflect the LIVE tag configuration,
not a fixed list baked into this PRD.)
```

**Filters required:**
```
- By month/year
- By MRU (single or all)
- By Agent (Admin view only — Agent sees only their own data)
- Combined: e.g. "August 2026, MRU Lahgariya, Tag = 24days" → list
  of exact consumers matching, not just the count
```

**Export:** CSV export of this report, matching the pattern already established for the existing bill export (`/bills/export-csv`) — reuse that export mechanism/style rather than building a new one.

**Data source — no new tables needed for this report:**
```
Review Status count → aggregate query on existing bill_statuses
                       (or wherever review status is stored)
Tag count            → aggregate query on existing bill_records.tag
                       / bill_statuses.tag (already built, per your
                       Tag System implementation)
```

### 2.5 Quota Usage Report (Recommended Addition)

Since the underlying data already exists in Plan Management System (no new tracking needed), this report is cheap to add and gives real value — both to the Agent (visibility before hitting a pay-gate) and to Admin (spotting Agents who repeatedly buy overage and might benefit from a plan upgrade conversation).

```
Monthly Quota Usage Report — Per Agent
├── MRU quota:       included X, used Y, over-quota Z
├── Consumer quota:  included X, used Y (per cycle), extra Z
├── Extra MRU charges this month:      ₹ total
├── Extra Consumer charges this month: ₹ total
└── Trend: last 3-6 months of the above, for pattern visibility
           (e.g. "bought extra MRU 3 months running" — upsell signal)
```

**Data source — no new tables needed:**
```
MRU/Consumer quota data  → already in mrus, billing_cycles,
                            agent_subscriptions (Plan Management System)
Overage charge amounts    → already in plan_overage_charges
                            (Plan Management System)
```

**Admin view:** same report aggregated across all Agents, sortable by "most overage spend" — useful for identifying good upsell candidates or Agents who might benefit from a plan change conversation.

---

## 3. Database Schema

```sql
-- The only new table this system requires for billing basis tracking
billing_basis_history
├── id
├── user_id              -- Agent, denormalized for fast aggregate queries
├── mru_id
├── consumer_id           -- FK to your existing consumer/CA master record
├── billing_cycle_id      -- FK to billing_cycles (already exists)
├── billing_basis         -- 'OK' | 'LK' | 'MD' | 'consumer_master'
├── is_consecutive_alert  -- boolean, computed at insert/update time
├── consecutive_count     -- how many cycles in a row this consumer
│                            has been LK/MD (resets to 0 on 'OK')
└── created_at

-- No new tables needed for the Monthly Status & Tag Report (2.4) —
-- it aggregates the EXISTING bill_records.tag / bill_statuses.tag
-- columns (already added by your separately-built Tag System) and
-- the existing review status field. This report is query-only.

-- No new tables needed for the Quota Usage Report (2.5) — it
-- aggregates EXISTING mrus, billing_cycles, agent_subscriptions,
-- and plan_overage_charges tables (all from Plan Management System).
```

---

## 4. Service Layer

```
UsageSummaryService
├── getMonthlySummary(user_id, month, year)
│     → returns the full 2.1 summary object for one Agent
├── getAdminAggregateSummary(month, year, filters)
│     → returns the 2.3 aggregate view across Agents

BillingBasisTrackingService
├── recordBillingBasis(consumer_id, billing_cycle_id, billing_basis)
│     → called automatically at the same point PDF extraction
│       already saves parsed data to DB — this is a hook into an
│       EXISTING process, not a new user-facing action
├── calculateConsecutiveCount(consumer_id)
│     → looks at billing_basis_history in reverse chronological
│       order, counts consecutive LK/MD entries, resets on first OK
├── getFlaggedConsumers(user_id, mru_id = null)
│     → returns consumers currently flagged is_consecutive_alert = true

StatusTagReportService
├── getMonthlyStatusBreakdown(user_id, month, year, mru_id = null)
│     → returns count per review status (Submitted/Critical/Doubt/
│       Pending), aggregated from existing bill_statuses data
├── getMonthlyTagBreakdown(user_id, month, year, mru_id = null)
│     → returns count per tag, reading the CURRENT active tag list
│       from the Tag System's config (not a hardcoded list — must
│       call whatever service the Tag System exposes for "active
│       tags," e.g. BillTagService::getActiveTags())
├── getConsumersByFilter(user_id, month, year, mru_id, status, tag)
│     → returns the actual consumer list matching a combined filter,
│       for drill-down from the summary counts
├── exportCsv(user_id, month, year, filters)
│     → reuses the existing /bills/export-csv mechanism/style,
│       does not duplicate export logic

QuotaUsageReportService
├── getMonthlyQuotaUsage(user_id, month, year)
│     → aggregates included/used/over-quota for MRU and Consumer
│       from existing mrus, billing_cycles, agent_subscriptions
├── getOverageChargeTotals(user_id, month, year)
│     → sums plan_overage_charges for the period, split MRU vs Consumer
├── getUsageTrend(user_id, months_back = 6)
│     → returns the above two, month by month, for trend display
├── getAdminAggregateQuotaUsage(month, year, sort_by = 'overage_spend')
│     → cross-Agent view for Admin, sortable by highest overage spend
```

---

## 5. Where This Hooks Into Existing Workflow

```
PDF extraction already happens (per your original product overview,
already built, not part of this system) at:
   "Download by CA" → PDF stored → parsed → data saved to DB

This system adds ONE hook at that exact point:
   After billing_basis is extracted and saved for a consumer this
   cycle → call BillingBasisTrackingService::recordBillingBasis()

No changes to the extraction/download system itself — this is a
listener/hook, not a modification of existing PDF processing logic.
```

---

## 6. What This System Does NOT Do (Out of Scope)

```
❌ Peak usage tracking — no stated need, not built (see section 1)
❌ Anomaly detection, forecasting, risk scoring — future, not now
❌ Any billing/quota/overage logic — already fully owned by
   Plan Management System
❌ Actual notification delivery (monthly summary email/SMS/push) —
   this system only assembles the data; Notification System
   handles delivery
❌ GST invoicing — separate system
```

---

## 7. Open Items

| Item | Status |
|---|---|
| Exact UI placement/design of the flagged-consumer badge in card/table view | ❌ Not decided — recommend reusing existing Submitted/Critical/Doubt/Pending badge styling for consistency, but confirm |
| Whether Admin aggregate view needs date-range filtering beyond month/year (e.g. custom range, year-over-year) | ❌ Not decided — recommend starting with month/year only, add range filtering later if actually requested |
| Whether "historical depth" (2.1) should have a minimum threshold before it's shown (e.g. don't show "1 month of history" as if it were a selling point) | ❌ Not decided — recommend only showing this stat after 3+ months, to avoid it looking thin for new Agents |
| Confirm exact method/endpoint the existing Bill Tag System exposes for "list of currently active tags" | ❌ Needs verification against actual Tag System code before this PRD's report can be built — do not hardcode the 5 example tags (OK/BQC/RCQ/24days/NOT_APPROVED_PREV_BQC_RQC) since Admin can add/edit/delete tags at any time |
| Whether deleted tags (Admin deleted a tag that had historical data tagged with it) should still appear in past months' reports, or disappear entirely | ❌ Not decided — recommend: historical reports should still show counts for now-deleted tags (using the tag's last-known label), since the bill records themselves still have that tag value stored; only the "create new" list shrinks |
