# PRD-13: Dynamic Reading Gap & Event-Driven Reconciliation Engine

**Status**: Draft / Aligned Specification  
**Domain**: Billing Engine, Meter Reading Reconciliation, Smart Average Forecasting  
**Target**: NBPDCL SaaS Platform (Mobile & Web)  
**Document Location**: `.agents/docs/prd/13-Dynamic_Reading_Gap_and_Reconciliation_Engine_PRD.md`

---

## 1. Executive Summary & Field Problem Context

During extensive 2–3 month ground observations with NBPDCL rural and semi-urban meter readers, a universal operational challenge was identified: **the persistent divergence between the Physical Meter Reading on the consumer's wall and the Official Department Posted Reading.**

### The Core Dilemma
In an ideal world:
$$\text{Physical Meter Reading} = \text{Official Billed Reading}$$

In field reality:
1. **Case 1: Over-Posted Reading (Negative Gap: $\text{Posted} > \text{Actual Meter}$)**:
   - Historical average estimates or past arbitrary entries have pushed the official bill ahead of the physical meter display (e.g., Bill shows **1,300 kWh**, but physical meter is only at **1,200 kWh**).
   - **System Invariant**: The official department billing app (Suvidha / Parisar / URJA) **strictly forbids** entering $\text{Current Reading} < \text{Previous Reading}$ (triggers fatal validation: *"Reading less than previous"* or flags meter as *"Defective / Dial Inversion"*).
   - **Operational Need**: The agent must hold the bill (+0 or +1 unit) while the consumer's real-world consumption continues advancing in the background until the physical meter catches up.

2. **Case 2: Under-Posted Reading / Massive Gap ($\text{Actual Meter} \gg \text{Posted}$)**:
   - Due to prolonged non-visits, locked houses, or low average entries, the physical meter is far ahead of the bill (e.g., Physical meter is at **7,000 kWh**, but official bill is only at **5,000 kWh** $\rightarrow$ a **+2,000 kWh gap**).
   - **The Spike Catastrophe**: If an agent dumps 2,000 kWh into a single month's bill:
     - The bill explodes through all subsidy slabs into the highest commercial/peak rate (e.g., ₹15,000–₹20,000 bill shock).
     - The consumer refuses to pay; collection stalls.
     - The department portal triggers a **High Consumption Spike Audit**, requiring assistant engineer site verification.
   - **Operational Need**: The agent must smoothly amortize the gap over time:
     $$\text{Monthly Units Billed} = \text{Normal Monthly Usage} + \text{Controlled Gap Slice}$$
     Capped at a safe monthly ceiling (**200 to 250 kWh/month**) that consumers can realistically afford without triggering audit alerts.

---

## 2. Critical Field Discovery: Event-Driven Variable Inspections

### Variable Inspection Frequency (Non-Fixed Intervals)
A foundational insight from field study is that **field inspections do NOT follow a fixed schedule**:
- An agent may visit a house after **15 days**, **1 month**, **2 months**, **3 months**, **6 months**, **8 months**, or even **1–2 years**.
- Visit timing depends on village route geography, seasonal access, and agent logistics.

### The Role of Smart Average ($A_{\text{avg}}$) as the Continuous Anchor
Between physical visits, billing must continue every single month from the desk/office.
- The consumer's actual monthly burn rate is relatively stable around their calculated **Smart Average** ($\pm 10\text{–}20\text{ kWh}$).
- **During unvisited months**: The platform uses $A_{\text{avg}}$ as the mathematical anchor to project meter advancement and calculate the safe monthly working reading.
- **On the day of any physical visit (Event Anchor)**: The agent enters the newly observed physical meter reading. The platform instantly recalculates the exact accumulated drift and recalibrates the recovery trajectory for all upcoming cycles.

---

## 3. Mathematical & Operational Model

### 3.1 Variables
- $M(t)$: Physical Meter Reading observed on wall at inspection time $t$.
- $P(t)$: Official Posted Reading from the last department bill.
- $\Delta(t) = M(t) - P(t)$: Current Net Reading Gap.
- $A_{\text{avg}}$: Consumer's verified monthly Smart Average consumption (kWh).
- $C_{\text{safe}}$: Safe Monthly Consumption Ceiling (Default: **220 kWh**, configurable 200–250 kWh).
- $W(t)$: Working Reading recommended to enter into the official billing app.

---

### 3.2 Strategy 1: Negative Gap (Hold & Dynamic Catch-Up Mode)
**Condition**: $\Delta(t) < 0$ (e.g., Physical = 1,200, Posted = 1,300 $\rightarrow \Delta = -100\text{ kWh}$).

1. **Monthly Working Reading**:
   $$W(t) = P(t) + \delta \quad (\text{where } \delta \in \{0, 1\})$$
   - Default: $+0$ units (Exact Hold) or $+1$ unit (Minimum Advance).
2. **Dynamic Catch-Up Forecast**:
   - Real meter advances by $\approx A_{\text{avg}}$ each month.
   - Expected cycles until physical meter crosses posted reading:
     $$T_{\text{catchup}} = \left\lceil \frac{|\Delta(t)|}{A_{\text{avg}}} \right\rceil \text{ cycles}$$
3. **Convergence**:
   - Once $M(t) \ge P(t)$, the platform automatically transitions the consumer to **In-Sync** status.

---

### 3.3 Strategy 2: Positive Gap (Smooth Amortization & Recovery Mode)
**Condition**: $\Delta(t) > 0$ (e.g., Physical = 7,000, Posted = 5,000 $\rightarrow \Delta = +2,000\text{ kWh}$).

1. **Available Recovery Capacity**:
   $$\text{Max Safe Gap Slice} = \max\left(0, \, C_{\text{safe}} - A_{\text{avg}}\right)$$
   *(Example: If $C_{\text{safe}} = 220\text{ kWh}$ and $A_{\text{avg}} = 100\text{ kWh}$, then up to $120\text{ kWh}$ of old gap can be recovered each month).*

2. **Monthly Billed Units**:
   $$\text{Billed Units}(t) = A_{\text{avg}} + \text{Gap Slice} \le C_{\text{safe}}$$

3. **Working Reading to Post**:
   $$W(t) = P(t) + \text{Billed Units}(t)$$

4. **Dynamic Shrinkage**:
   - Each month, the net gap shrinks by the recovery slice:
     $$\Delta(t + 1) \approx \Delta(t) - \text{Gap Slice}$$
   - Projected months to full recovery:
     $$T_{\text{recovery}} = \left\lceil \frac{\Delta(t)}{\text{Gap Slice}} \right\rceil \text{ cycles}$$

---

## 4. UI & Interaction Architecture

### 4.1 Dual-Reading Data Input (Dashboard & Consumer Account)
Every consumer profile and billing card displays:
1. **Physical Meter Reading ($M$)**: Editable input with "Last Inspected" timestamp.
2. **Working Reading to Post ($W$)**: Auto-calculated recommended number with 1-click copy.
3. **Gap Status Badge**:
   - 🟢 **In-Sync** ($\Delta = 0$): Normal billing.
   - 🟡 **Over-Posted (Hold Mode)** ($\Delta < 0$): Displays deficit and estimated months to catch up.
   - 🔴 **Under-Posted (Recovery Mode)** ($\Delta > 0$): Displays remaining gap, monthly slice, and progress bar.

### 4.2 1-Click Operational Controls
- **Hold Mode Buttons**: `[ +0 Hold ]` and `[ +1 Unit ]` instant setters.
- **Recovery Presets**: Quick selector for Safe Ceiling: `[ 200 kWh ]`, `[ 220 kWh ]`, `[ 250 kWh ]`, `[ Custom ]`.
- **Spike Warning Guard**: If an operator attempts to enter a reading that jumps $> C_{\text{safe}}$ or $> 2 \times A_{\text{avg}}$, an immediate interactive alert appears offering the safe recovery plan instead of triggering an official audit flag.

### 4.3 Inspection Staleness Indicator
A clear visual badge showing time elapsed since the last recorded physical inspection:
- 🟢 Fresh: $\le 60$ days ago.
- 🟡 Moderate: $61\text{–}120$ days ago.
- 🔴 Overdue: $> 120$ days ago (*"Site visit recommended"*).

---

## 5. System Architecture & Data Schema (Proposed)

### 5.1 Database Additions
1. **`consumer_accounts` / `meter_inspections` table**:
   - `last_physical_reading` (decimal/string, nullable)
   - `last_inspected_at` (timestamp, nullable)
   - `inspected_by_user_id` (foreign key, nullable)
   - `gap_recovery_mode` (enum: `auto`, `hold_zero`, `hold_one`, `amortize`, `direct`)
   - `custom_safe_ceiling` (integer, nullable - overrides global 220 kWh cap)
2. **`system_settings` additions**:
   - `gap_default_safe_ceiling` (default: 220 kWh)
   - `gap_hold_default_increment` (default: 0 or 1)
   - `inspection_overdue_days` (default: 120 days)

### 5.2 Service Layer Design
- `app/Services/Reading/ReadingGapReconciliationService.php`:
  - `recordPhysicalInspection(ConsumerAccount $consumer, float $reading, ?Carbon $date)`
  - `calculateRecommendedWorkingReading(BillRecord $bill, ConsumerAccount $consumer): array`
  - `getGapSummary(ConsumerAccount $consumer): array`

---

## 6. Implementation Roadmap

| Phase | Description | Deliverables |
|:---:|:---|:---|
| **Phase 1** | **Database Schema & Migrations** | Add physical inspection fields & audit log tracking. |
| **Phase 2** | **Core Service Layer** | `ReadingGapReconciliationService` with dynamic formulas. |
| **Phase 3** | **Admin Settings & Thresholds** | Configuration card in Engine Settings for safe caps. |
| **Phase 4** | **Dashboard & Table Integration** | Dual-reading display, 1-click hold, recovery progress bar. |
| **Phase 5** | **Automated Tests & Pint** | Comprehensive PHPUnit feature test suite covering all gap cases. |

---

*Document created and persisted for cross-session continuity.*
