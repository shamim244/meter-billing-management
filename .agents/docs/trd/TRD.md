# Technical Requirements & Architecture Document (TRD)

**Project Name:** NBPDCL / BSPHCL Electricity Billing & Field Automation Platform  
**Target Locations:** Bihar Power Distribution (NBPDCL / SBPDCL / BSPHCL)  
**Document Version:** 1.0.0  
**Status:** Approved for Implementation  
**Architecture Theme:** Fast, Efficient, and Secure  

---

## 1. System Architecture Overview

The system employs a multi-tenant Laravel 13.x service-oriented architecture with strict row-level tenant isolation, optimized MySQL database indexing, and a unified REST API layer serving both traditional client software and AI tool-calling agents.

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                     CLIENT LAYER                                        │
│  ┌────────────────────────┐  ┌─────────────────────────┐  ┌──────────────────────────┐  │
│  │ Web Dashboard (Blade/  │  │ Python ADB Automation   │  │ AI Agents / LLMs         │  │
│  │ Alpine.js/Tailwind)    │  │ (Field Reader Script)   │  │ (OpenAPI / Tool Calling) │  │
│  └───────────┬────────────┘  └────────────┬────────────┘  └────────────┬─────────────┘  │
└──────────────┼────────────────────────────┼────────────────────────────┼────────────────┘
               │ Session Auth               │ Bearer Token               │ Bearer Token
               ▼                            ▼                            ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                   SECURITY & ROUTING                                    │
│  ┌────────────────────────────────────────┐  ┌───────────────────────────────────────┐  │
│  │ Web Middleware (auth, role:admin)      │  │ API Middleware (auth:sanctum, throttle│  │
│  └───────────────────┬────────────────────┘  └───────────────────┬───────────────────┘  │
└──────────────────────┼───────────────────────────────────────────┼──────────────────────┘
                       │                                           │
                       ▼                                           ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                    BUSINESS LOGIC LAYER                                 │
│  ┌─────────────────────────┐  ┌──────────────────────────┐  ┌─────────────────────────┐ │
│  │ DashboardController     │  │ BillApiController (V1)   │  │ ProcessingController    │ │
│  │ (Web data & sorting)    │  │ (REST Endpoints & Sync)  │  │ (Batch Downloader/Parse)│ │
│  └─────────────┬───────────┘  └────────────┬─────────────┘  └────────────┬────────────┘ │
│                │                           │                             │              │
│                ▼                           ▼                             ▼              │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐  │
│  │ Core Services: BillDownloadService (cURL) | BillParseService | LedgerEngine       │  │
│  └─────────────────────────────────────────┬─────────────────────────────────────────┘  │
└────────────────────────────────────────────┼────────────────────────────────────────────┘
                                             │
                                             ▼
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                                DATA ISOLATION & PERSISTENCE                             │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐  │
│  │ BelongsToUser Global Scope (Enforces: WHERE user_id = Auth::id())                 │  │
│  └─────────────────────────────────────────┬─────────────────────────────────────────┘  │
│                                            │                                            │
│                                            ▼                                            │
│  ┌───────────────────────────────────────────────────────────────────────────────────┐  │
│  │ MySQL Database (nbpdcl_billing)                                                   │  │
│  │ Tables: users, mrus, consumer_accounts, bill_records, bill_statuses, personal_tokens│
│  └───────────────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Technology Stack

| Layer | Component | Version / Specification | Rationale |
|---|---|---|---|
| **Language** | PHP | 8.3 / 8.4 (Constructor promotion, strict typing) | High execution speed, strong typing, low memory footprint. |
| **Framework** | Laravel | 13.x | Robust routing, Eloquent ORM, built-in migration engine, Sanctum auth. |
| **Database** | MySQL | 8.0+ (`nbpdcl_billing`) | ACID compliance, compound composite unique indexes, relational integrity. |
| **API Authentication** | Laravel Sanctum | Latest | Token-based authentication (`Bearer <token>`) with ability scopes. |
| **Network Engine** | Multi-cURL | Native PHP `curl_multi_*` | Concurrency up to 10–20 parallel HTTP streams without Python overhead. |
| **PDF Extraction** | Smalot PDFParser | v2.11+ | Native PHP text extraction for Kruti-Dev Hindi PDF streams. |
| **Frontend** | Tailwind CSS + Alpine.js | CDN / Bundled Vite | Reactive client-side sorting/filtering with zero bloated JavaScript frameworks. |

---

## 3. Database Schema & Data Models

All operational tables enforce tenant isolation via `user_id` foreign keys and compound unique constraints to guarantee data integrity and prevent race-condition duplicates.

### Entity Relationship Model

```
 ┌─────────────────┐
 │      users      │
 └──┬───────────┬──┘
    │ 1:N       │ 1:N
    ▼           ▼
┌─────────┐   ┌───────────────────────┐
│  mrus   │   │   consumer_accounts   │
└───┬─────┘   └──┬────────────────────┘
    │ 1:N        │ 1:N
    ▼            ▼
┌─────────────────────────────────────┐
│            bill_records             │
│ (Period, Readings, Status, Remarks) │
└─────────────────────────────────────┘
```

### Table Definitions & Compound Keys

#### 1. `users`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `name`: VARCHAR(255)
* `email`: VARCHAR(255) UNIQUE
* `phone`: VARCHAR(20)
* `status`: ENUM('active', 'inactive') DEFAULT 'active'
* `created_at`, `updated_at`: TIMESTAMP

#### 2. `mrus`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED FK (`users.id` CASCADE)
* `code`: VARCHAR(50) (e.g. `0244`, `HALA`)
* `name`: VARCHAR(255) (e.g. `NISARBHATI`)
* `full_identifier`: VARCHAR(255)
* `status`: ENUM('active', 'inactive') DEFAULT 'active'
* **Unique Index:** `UNIQUE(user_id, code)`

#### 3. `consumer_accounts` (Master Registry)
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED FK (`users.id` CASCADE)
* `mru_id`: BIGINT UNSIGNED NULL FK (`mrus.id` SET NULL)
* `ca_number`: VARCHAR(50) (11–12 digit Bihar CA number)
* `consumer_name`: VARCHAR(255) NULL
* `father_name`: VARCHAR(255) NULL
* `meter_no`: VARCHAR(50) NULL
* `tariff_category`: VARCHAR(50) NULL (e.g. `KJ`, `DS-II`)
* `last_working_reading`: VARCHAR(30) NULL
* `last_working_month`: TINYINT UNSIGNED NULL
* `last_working_year`: SMALLINT UNSIGNED NULL
* `baseline_previous_reading`: VARCHAR(30) NULL
* `status`: ENUM('active', 'inactive') DEFAULT 'active'
* **Unique Index:** `UNIQUE(user_id, ca_number)`
* **Indexes:** `INDEX(ca_number)`, `INDEX(user_id, mru_id)`

#### 4. `bill_records` (Monthly Cycle Bills & Ledger)
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED FK (`users.id` CASCADE)
* `mru_id`: BIGINT UNSIGNED NULL FK (`mrus.id` SET NULL)
* `ca_number`: VARCHAR(50)
* `billing_month`: TINYINT UNSIGNED (1–12)
* `billing_year`: SMALLINT UNSIGNED (e.g. 2026)
* `bill_month_label`: VARCHAR(50) NULL (e.g. `APR, 2026`)
* `consumer_name`: VARCHAR(255) NULL
* `total_amount`: DECIMAL(12, 2) NULL
* `current_reading`: VARCHAR(20) NULL (from official PDF)
* `previous_reading`: VARCHAR(20) NULL (from official PDF or local history)
* `working_reading`: VARCHAR(30) NULL (auto-projected or field-entered)
* `units_consumed`: INT UNSIGNED NULL
* `meter_no`: VARCHAR(50) NULL
* `tariff_category`: VARCHAR(50) NULL
* `billing_basis`: VARCHAR(20) DEFAULT 'OK' (`OK`, `LK`, `MD`, `PL`, `RN`)
* `review_status`: ENUM('pending', 'submitted', 'critical', 'doubt') DEFAULT 'pending'
* `reason_code`: VARCHAR(50) NULL
* `remark`: VARCHAR(500) NULL
* `pdf_path`: VARCHAR(500) NULL
* `download_status`: ENUM('pending', 'downloaded', 'failed') DEFAULT 'pending'
* `parse_status`: ENUM('pending', 'parsed', 'failed', 'skipped') DEFAULT 'pending'
* `processing_date`: TIMESTAMP NULL
* **Unique Index:** `UNIQUE(user_id, ca_number, billing_month, billing_year)`
* **Performance Indexes:**
  * `INDEX(user_id, billing_month, billing_year, mru_id)`
  * `INDEX(user_id, review_status)`
  * `INDEX(user_id, ca_number)`

---

## 4. Multi-Tenant Security & Authentication Architecture

### 1. The `BelongsToUser` Global Scope
Every Eloquent query executed on tenant models automatically appends:
$$\text{WHERE } \texttt{user\_id} = \text{Auth::id()}$$

```php
// Automatically applied by Trait:
static::addGlobalScope('belongs_to_user', function (Builder $builder) {
    if (Auth::check() && !static::isAdminContext()) {
        $builder->where('user_id', Auth::id());
    }
});
```

### 2. Sanctum API Token Scoping
* Standard Bearer Token: `Authorization: Bearer <token>`
* When an external request hits `/api/v1/*`, `auth:sanctum` middleware authenticates the `User` model.
* The `user_id` is immediately bound to the request lifecycle.
* Cross-tenant data tampering is mathematically impossible at the database query layer.

### 3. Admin Governance & Scoped Masquerading
* Users with `role: admin` bypass `BelongsToUser` on designated admin endpoints (`/api/v1/admin/*`).
* An Admin can optionally query a specific tenant's data by passing the `X-Tenant-User-ID` header or `?user_id=X` query parameter.
* Non-admin users attempting to pass `user_id` or `X-Tenant-User-ID` are rejected with `403 Forbidden`.

---

## 5. Performance Engineering (Fast & Efficient)

### 1. Indexed Query Strategy for `GET /api/v1/bills`
The primary endpoint queries millions of potential records. To maintain sub-100ms response times:
* The composite index `(user_id, billing_month, billing_year, mru_id)` covers the base query.
* Slicing and pagination (`LIMIT` / `OFFSET`) are applied after priority sort order calculation.
* Selected columns are restricted to avoid hydrating unneeded binary paths.

### 2. High-Efficiency Batch Synchronization (`POST /api/v1/bills/batch-sync`)
For offline readers returning from the field:
* Changes are committed inside an atomic `DB::transaction(...)`.
* Bulk updates utilize `upsert` or single-transaction update loops.
* Auto-cascade forward calculations run asynchronously or in single-pass indexed sweeps.

### 3. Zero-RAM Streaming Exports
* ZIP exports use `$zip->addFile($realDiskPath, $zipInternalPath)` instead of `addFromString($buffer)`.
* This streams files directly from the local disk into the archive, consuming `< 2 MB` of PHP RAM regardless of the number of PDFs bundled.

---

## 6. AI Agent Integration & Tool Calling (OpenAPI 3.0)

To support LLM tool calling (OpenAI Functions, Claude Computer Use / Tool Use, Gemini Function Calling, or MCP):
* **Deterministic Contract:** Responses return consistent JSON envelopes:
  ```json
  {
    "success": true,
    "data": [ ... ],
    "meta": { "total": 244, "page": 1 }
  }
  ```
* **Predictable Error Schemas:** Validation failures return standard 422 JSON:
  ```json
  {
    "success": false,
    "error_code": "VALIDATION_FAILED",
    "message": "The given data was invalid.",
    "errors": {
      "ca_number": ["The selected CA number is invalid or not registered."]
    }
  }
  ```
* **No Stateful Session Dependency:** Completely stateless Bearer token authentication ensures an AI agent can execute one-off tool calls without managing web cookies or CSRF tokens.
