# Product Requirements Document (PRD)

**Project Name:** NBPDCL / BSPHCL Electricity Billing & Field Automation Platform  
**Target Locations:** Bihar Power Distribution (NBPDCL / SBPDCL / BSPHCL)  
**Document Version:** 1.0.0  
**Status:** Approved for Development  
**Target Milestone:** Automation API & AI-Ready Integration  

---

## 1. Executive Summary & Vision

The **NBPDCL Billing Platform** is a specialized, multi-tenant B2B SaaS system engineered for electricity meter readers, billing agencies, franchisees, and sub-division distribution contractors working with Indian state power distribution companies (specifically **NBPDCL / BSPHCL**).

The system automates the acquisition, PDF text extraction, historical ledger tracking, 4-box meter reading calculation, verification, and distribution of monthly consumer electricity bills across hundreds of thousands of accounts.

### Current System Status
* **Web Dashboard & Core Engine:** ~85% – 90% completed. Includes MRU workspaces, monthly billing cycles, multi-cURL parallel downloader, Kruti-Dev PDF text extraction, 4-Box Reading Ledger with auto-projection, priority review sorting (P-D-C-S), and Excel/ZIP exports.
* **Next Objective:** Build a **Fast, Efficient, and Secure REST API Layer** that serves as an open integration bridge for:
  1. **Field Automation Tools:** Direct integration with Android automation (e.g., Python ADB scripts automating the official `in.phoenix.bpdcl` app, mobile readers, barcode scanners).
  2. **AI Agents & LLMs:** Function/Tool-calling integration (OpenAPI 3.0 standard) allowing AI copilots to assist in auditing, reporting, and operational execution without custom backend adaptations.

---

## 2. Target User Personas

| Persona | Role & Context | Primary Needs from Platform |
|---|---|---|
| **Field Meter Reader** | Walks village/urban routes with mobile device (`in.phoenix.bpdcl`), capturing meter photos and readings. | Extreme speed (~3.5s camera readiness), offline resilience, rapid hotkeys to mark house verdicts (Submitted, Locked, Burnt, Skip). |
| **Billing Agency Manager** | Manages 10,000 to 50,000 consumer accounts across multiple MRU blocks/feeders. | Live visibility of submitted vs. pending accounts, discrepancy alerts, batch export of bills for DISCOM submission. |
| **Franchisee / Contractor** | High-level commercial operator responsible for complete revenue collection in a sub-division. | Cross-MRU summaries, audit trails of defective meters to report to Junior Engineers (JEs), revenue assurance. |
| **System Administrator** | SaaS platform administrator. | Tenant isolation enforcement, API key governance, tenant account activation/deactivation, global analytics. |

---

## 3. Human Decision Framework (Human-in-the-Loop)

A core foundational principle of this platform is **Human Sovereignty**. The server or AI will **never** guess or override the field operator's verdict. The human on the ground has physical eyes on the meter and premises; the platform is an obedient, reliable ledger.

### Status Definitions & Operational Matrix

```
                      ┌──────────────────────────────────────────────┐
                      │              PENDING ACCOUNT                 │
                      │  (Cycle initialized / Awaiting inspection)   │
                      └──────────────────────┬───────────────────────┘
                                             │
                       Field Inspection & Human Decision
                                             │
         ┌───────────────────────────┬───────┴───────────────────────────┐
         │                           │                                   │
         ▼                           ▼                                   ▼
┌──────────────────┐       ┌──────────────────┐                ┌──────────────────┐
│    SUBMITTED     │       │      DOUBT       │                │     CRITICAL     │
│ Verified Normal  │       │   Suspicious /   │                │   Unworkable /   │
│  Bill Proceeds   │       │  Requires Re-check│               │ Blocked Account  │
└──────────────────┘       └──────────────────┘                └──────────────────┘
```

#### 1. SUBMITTED (`submitted`)
* **Definition:** Everything is physically normal. The meter display is legible, reading is consistent with historical progression, photo is captured, and the bill proceeds for normal collection.
* **Requirements:** Working reading confirmed; optional remark.

#### 2. DOUBT (`doubt` / Pending Doubt)
* **Definition:** The account cannot be submitted immediately because the situation is **suspicious, disputed, or physically unverified**, but could potentially be resolved on re-check.
* **Standard Reasons:**
  * `PREMISES_LOCKED`: Premises locked, gate closed, or consumer unavailable.
  * `SUSPICIOUS_READING`: Meter shows unexpected jump or drop inconsistent with physical baseline.
  * `SUSPICIOUS_AMOUNT`: Arrears or bill balance abnormally high/low, disputed by consumer.
  * `PREV_MONTH_MISMATCH`: Physical meter initial reading differs from previous month database record.
  * `OWNER_RECHECK_REQUEST`: Consumer requested on-site re-inspection before submission.
  * `OTHER`: Custom text observation.

#### 3. CRITICAL (`critical`)
* **Definition:** The account is **physically unworkable** and normal billing cannot proceed without DISCOM Junior Engineer (JE) or Sub-Divisional Officer (SDO) field intervention.
* **Standard Reasons:**
  * `METER_BURNT_DEAD`: Display burnt, cracked, or completely unreadable.
  * `METER_TAMPERED_BYPASS`: Seal broken, meter bypassed, direct hooking detected.
  * `METER_MISSING_STOLEN`: No physical meter found at connection site.
  * `PREMISES_DEMOLISHED`: House/premises demolished, service line disconnected.
  * `JE_INTERVENTION_REQUIRED`: Legal, administrative, or line-fault block.
  * `OTHER`: Custom text observation.

#### 4. REMARKS (`remark`)
* Free-form text or standardized reason string logged alongside any status.
* Remarks are permanently persisted and immediately visible on web dashboard tables and CSV exports.

---

## 4. Operational Lifecycle & Timing Models

To accommodate varied field conditions (including rural zones with zero cellular connectivity), the system supports two timing workflows:

### Workflow A: Real-Time House-by-House Execution (Connected Mode)
1. Field tool queries pending list from API: `GET /api/v1/bills?status=pending`.
2. Tool triggers Android app camera via ADB for Consumer #1.
3. Operator inspects meter and confirms decision via hotkey (`[Enter]` = Submitted, `[d]` = Doubt, `[c]` = Critical).
4. Tool sends immediate atomic update: `PATCH /api/v1/bills/review`.
5. Web dashboard counters update instantly in real time.

### Workflow B: Pre-Staging & Offline Batch Execution (Rural/Disconnected Mode)
1. **Pre-Staging (Office/Home):** The Python script pre-fills cached fields ("Normal", "OK", "Inside") in the Android app. Server accounts remain in `pending`.
2. **Field Execution (Offline in Village):** Operator walks house to house. The local client logs verdicts, remarks, and readings into a local JSON store without requiring cellular data.
3. **Batch Sync (Upon Return):** When connected to 4G/Wi-Fi, client pushes all updates in a single atomic payload: `POST /api/v1/bills/batch-sync`.

---

## 5. Web Dashboard Parity & Filtering Requirements

The API **must** replicate every filter and sorting capability available on the web dashboard to ensure zero behavioral drift between human web users and automation scripts.

### Required Filter & Sort Matrix
* **MRU Scoping:** Filter by `mru_id` or unique MRU code (e.g., `0244`).
* **Cycle Period:** Filter by `month` (1–12) and `year` (e.g., `2026`).
* **Status Filter:** Filter by `status` (`all`, `pending`, `submitted`, `doubt`, `critical`).
* **Priority Sequence Sorting (`status_sort`):**
  * `pdcs`: Pending ➔ Doubt ➔ Critical ➔ Submitted
  * `dcps`: Doubt ➔ Critical ➔ Pending ➔ Submitted
  * `cdps`: Critical ➔ Doubt ➔ Pending ➔ Submitted
  * `spdc`: Submitted ➔ Pending ➔ Doubt ➔ Critical
  * `default`: Normal order
* **Column Sorting (`sort_col` & `sort_asc`):**
  * Sort by Amount (Ascending / Descending)
  * Sort by Units Consumed (Ascending / Descending)
  * Sort by Consumer Number (Ascending / Descending)
  * Sort by Meter Number (Ascending / Descending)

---

## 6. AI Agent & Tool-Calling Compatibility

The system must treat AI agents as **first-class integration clients**:
* No secondary or isolated "AI-only" engine is required.
* The API adheres to strict **OpenAPI 3.0 / JSON Schema** standards.
* Responses use predictable, strongly typed JSON keys.
* Error responses use standard HTTP status codes (`400`, `401`, `403`, `404`, `422`) with structured JSON error explanations.
* This allows LLM agents (ChatGPT, Claude, Gemini, local Ollama agents, or MCP servers) to use the API directly via native Tool / Function Calling.

---

## 7. Non-Functional Requirements (Fast, Efficient, Secure)

### 1. Fast (Low Latency)
* API list query (`GET /api/v1/bills`) response time **< 150ms** for MRU cycles containing up to 2,500 consumers.
* Review update (`PATCH /api/v1/bills/review`) execution time **< 50ms**.

### 2. Efficient (Low Resource Overhead)
* Batch sync endpoint processes up to 1,000 records in a single database transaction under 1 second.
* Zero N+1 query execution on relationships.
* Lightweight JSON payloads with selective field serialization.

### 3. Secure (Multi-Tenant & Role Governed)
* **Authentication:** Token-based authentication via **Laravel Sanctum**.
* **Tenant Isolation:** Enforced globally via `BelongsToUser` query scoping. Regular users cannot access or alter another agency's data under any condition.
* **Admin Governance:** Admin tokens retain global analytics access and the ability to query specific tenant data using scoped headers or parameters (`?user_id=X`).
* **Instant Revocation:** Users can generate and revoke API tokens at will from their web profile.
