# NBPDCL Meter Billing — Field Knowledge Base & Engineering Memory

> **Document Type:** Persistent Field Knowledge Base & System Architecture Record  
> **Source:** Direct 2–3 Months Field Observations with NBPDCL Meter Readers & Consumers  
> **Location:** `docs/field-knowledge-base/01-reading-gap-and-field-inspection-knowledge-base.md`  
> **Status:** Implemented & Verified in Production (October 2026)

---

## 1. Ground Reality & Field Operating Environment

During 2–3 months of direct field visits across rural and semi-urban distribution areas under NBPDCL (North Bihar Power Distribution Company Limited), the following real-world operational realities were identified:

### 1.1 Non-Fixed Visit Schedules (Asynchronous Inspections)
- **Official Model vs Field Reality:** The utility policy assumes monthly physical readings, but in practice, meter readers visit consumers at highly variable, ad-hoc intervals:
  - Some consumers are visited every **15 to 30 days**.
  - Others are visited every **2, 3, 4, or 6 months**.
  - Distant, rural, or locked premises may go unvisited for **1 to 2 years**.
- **Intermediate Billing via Smart Average ($A_{\text{avg}}$):**
  - In all unvisited intermediate cycles, bills are generated using the consumer's **Smart Average** monthly consumption (with variations of $\pm 10\text{--}100\text{ kWh}$).
  - When a reader finally visits after 5, 6, or 12 months, the platform must smoothly reconcile the accumulated physical divergence without breaking billing integrity.

---

## 2. The Core Reading Divergence Problems & Platform Solutions

```
                          ┌──────────────────────────┐
                          │ Physical Meter on Wall   │
                          │        Reading: M        │
                          └─────────────┬────────────┘
                                        │
                         Compare with Posted Bill (P)
                                        │
                  ┌─────────────────────┴─────────────────────┐
                  ▼                                           ▼
       CASE 1: OVER-POSTED                         CASE 2: UNDER-POSTED
      Physical < Posted Bill                      Physical > Posted Bill
          (M < P, Δ < 0)                              (M > P, Δ > 0)
                  │                                           │
       Rule: Cannot Post < P                       Rule: Cannot Dump Gap at Once
                  │                                           │
           Enforce HOLD MODE                           Enforce AMORTIZATION
        Post P (+0) or P+1 (+1)                     Post P + Avg + Safe Gap Slice
       Avg burns gap in background               Capped at Safe Ceiling (220 kWh/mo)
```

---

### 2.1 Case 1: Over-Posted Reading ($\Delta < 0$)
- **The Problem:** 
  - Official billed reading is **ahead of physical meter** on the wall.
  - *Example:* Consumer physical meter shows **1,200 kWh**, but prior bill already posted **1,300 kWh** ("used less but posted high").
- **The Department Rule / Technical Barrier:**
  - The NBPDCL billing system enforces strict monotonic progression:
    $$\text{Current Reading} \ge \text{Previous Reading}$$
  - Entering 1,200 kWh throws a fatal validation error: *"Current reading cannot be less than previous reading"*.
- **The Platform Engine Solution — Hold Mode (+0 or +1):**
  1. **Hold Mode Recommendation:** The platform recommends posting $W = P$ (+0 units) or $W = P + 1$ (+1 unit advance).
  2. **Active Consumption Invariant:** While the bill is held at +0 or +1, the consumer continues using electricity in real life at their normal monthly burn rate ($A_{\text{avg}}$).
  3. **Natural Catch-Up Cycles:**
     $$T_{\text{catchup}} = \left\lceil \frac{|\Delta|}{A_{\text{avg}}} \right\rceil \text{ cycles}$$
     *(Example: 100 kWh deficit at 50 kWh/mo average catches up naturally in exactly 2 billing cycles, after which physical reading crosses 1,300 kWh and normal billing resumes without any penalty).*

---

### 2.2 Case 2: Under-Posted Reading / Large Accumulated Gap ($\Delta > 0$)
- **The Problem:**
  - Official billed reading is **far behind physical meter** on the wall.
  - *Example:* Consumer physical meter has reached **7,000 kWh**, but prior bill is only posted at **5,000 kWh** ($+2,000\text{ kWh}$ accumulated debt).
- **The Danger of Direct Dumping:**
  - Posting 7,000 kWh in a single month charges 2,000 units at once:
    - Causes severe bill shock (**₹15,000+** electricity bill for a rural household).
    - Triggers department high-consumption audit flags and meter inspection squads.
    - Leads to consumer refusal to pay and billing disputes.
- **The Platform Engine Solution — Safe Amortization Slice:**
  1. **Tariff Safe Ceilings ($C_{\text{safe}}$):**
     - **Domestic (DS-1D / DS-II):** Default **220 kWh/month** (configurable up to 250 kWh/mo).
     - **Commercial (NDS-1D):** Default **400 kWh/month**.
     - **Agriculture (IAS-1):** Default **800 kWh/month**.
  2. **Recovery Capacity Formula:**
     $$S_{\text{gap}} = \min\left(\Delta, \, \max\left(0, \, C_{\text{safe}} - A_{\text{avg}}\right)\right)$$
  3. **Recommended Monthly Reading:**
     $$\text{Billed Units}(t) = A_{\text{avg}} + S_{\text{gap}} \le C_{\text{safe}}$$
     $$W(t) = P(t) + \text{Billed Units}(t)$$
  4. **Example Walkthrough:**
     - Previous Posted $P = 5,000\text{ kWh}$, Physical Meter $M = 7,000\text{ kWh}$ ($\Delta = +2,000\text{ kWh}$).
     - Consumer Smart Average $A_{\text{avg}} = 100\text{ kWh/mo}$.
     - Safe Ceiling $C_{\text{safe}} = 220\text{ kWh/mo}$.
     - Recovery slice: $S_{\text{gap}} = 220 - 100 = 120\text{ kWh/mo}$.
     - Total billed units for Month 1: $100 + 120 = 220\text{ kWh}$.
     - Recommended working reading: $5,000 + 220 = \mathbf{5,220\text{ kWh}}$.
     - Remaining gap drops to $1,880\text{ kWh}$.
     - Next month, reading posts at $5,220 + 220 = \mathbf{5,440\text{ kWh}}$.
     - The ₹15,000 bill shock is completely eliminated and absorbed smoothly!

---

## 3. Database Architecture & Schema

### 3.1 `meter_inspections` Table
Dedicated immutable audit log preserving every on-site physical check:
- `id` (bigint, PK)
- `consumer_account_id` (foreign key to `consumer_accounts`)
- `ca_number` (varchar 50, indexed)
- `user_id` (foreign key to `users` — records who did the inspection)
- `physical_reading` (string 50 — exact digits from the meter dial)
- `inspected_at` (timestamp — actual date/time of field visit)
- `notes` (text, nullable — field remarks: e.g. "seal OK", "meter glass cracked")
- `photo_path` (string, nullable — meter photo evidence)

### 3.2 Additions to `consumer_accounts`
- `last_physical_reading` (string 50, nullable)
- `last_inspected_at` (timestamp, nullable)
- `inspected_by_user_id` (bigint, nullable)
- `gap_recovery_mode` (string 20, default `'auto'` — supports `hold_exact`, `hold_advance`, `amortize`)
- `custom_safe_ceiling` (integer, nullable — per-consumer custom ceiling override)

### 3.3 Additions to `bill_records`
- `physical_reading` (string 50, nullable)
- `reading_gap` (integer, nullable)
- `gap_slice_applied` (integer, nullable)
- `gap_action_applied` (string 30, nullable)

---

## 4. UI & Operator Field Controls

### 4.1 Card View & Table View Badges
Every consumer card and table row displays an instant visual status badge:
- 🟡 **Over-Posted:** `⚠️ Over -100 kWh (2mo)`
- 🔴 **Under-Posted Gap:** `⚖️ +2,000 Gap (+120/mo)`
- 🟢 **In-Sync:** `🟢 Sync`
- 🔍 **Uninspected:** `🔍 +Inspect`
- ⏱️ **Stale Inspection:** `⏱️ Stale` (Appears when no visit has occurred for $> 90$ days)

### 4.2 1-Click Operational Buttons
- `[ +0 ]`: Instant Hold Exact (sets working reading $= P$).
- `[ +1 ]`: Instant Hold Advance (sets working reading $= P + 1$).
- `[ ⚖️+Slice ]`: Instantly applies the recommended safe amortized reading.
- `[ 🔍 +Inspect ]`: Opens the inspection modal to enter new wall readings in 2 seconds.

---

## 5. Future Edge Cases & Technical Notes

### 5.1 Dial Rollover (5-Digit Meter Wraparound)
- If a meter dial reaches `99999` and rolls over to `00045`, the raw difference $45 - 99990 = -99945$ looks like an extreme negative gap.
- *Handling Rule:* If $P > 90,000$ and $M < 5,000$, calculate rollover gap:
  $$\Delta_{\text{rollover}} = (100,000 - P) + M$$

### 5.2 Meter Replacement / Burnt Meter Swap
- When the department replaces a burnt or defective meter with a brand-new meter:
  - The meter number (`meter_no`) changes on the consumer account.
  - The initial reading resets to zero or a very low testing number (e.g. 5 kWh).
  - *Handling Rule:* When a new meter number is recorded, reset baseline reading and initialize a new inspection epoch.

---

*This document serves as permanent institutional memory for developers, field supervisors, and agents working on the NBPDCL Meter Billing platform.*
