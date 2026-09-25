# 01 — System Architecture

The **NBPDCL Meter Billing & Management System** is an enterprise-grade utility SaaS built to ingest, extract, calculate, audit, and dispatch electricity billing data for consumer accounts across North Bihar Power Distribution Company Limited (NBPDCL).

---

## 🏛️ High-Level Architecture Diagram

```
                               ┌─────────────────────────────────┐
                               │   Flutter Mobile App (Field)    │
                               │   - Batch Readings Upload       │
                               │   - Dynamic Server Discovery    │
                               └──────────────┬──────────────────┘
                                              │ HTTP / JSON
                                              ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                             HTTP & API Edge Layer                                │
│  - Adaptive Compression Middleware (Brotli / Zstd / Gzip negotiation)            │
│  - API Rate Limiting & Analytics (Per-key / IP throttling)                       │
│  - First-Run Installation Guard (CheckApplicationInstalled)                     │
└─────────────────────────────────────┬────────────────────────────────────────────┘
                                      │
                                      ▼
┌──────────────────────────────────────────────────────────────────────────────────┐
│                           Core Application Services                              │
│  ┌─────────────────────────┐  ┌─────────────────────────┐  ┌──────────────────┐  │
│  │  BillingCalculation     │  │  ServerMigrationService │  │ InstallerService │  │
│  │  Jasper Unicode Extractor│ │  Atomic Bundler & Audit │  │ 4-Step Web Setup │  │
│  └─────────────────────────┘  └─────────────────────────┘  └──────────────────┘  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐  ┌──────────────────┐  │
│  │  DatabaseDumpService    │  │  StorageBackupService   │  │ NotificationHub  │  │
│  │  Streaming PDO Dump     │  │  Media ZIP Archiver     │  │ Multi-Provider   │  │
│  └─────────────────────────┘  └─────────────────────────┘  └──────────────────┘  │
└─────────────────────────────────────┬────────────────────────────────────────────┘
                                      │
              ┌───────────────────────┴───────────────────────┐
              ▼                                               ▼
┌───────────────────────────┐                   ┌───────────────────────────┐
│     MySQL 8.0 Database    │                   │   Persistent File Storage │
│  - Double-Entry Wallets   │                   │   - Consumer Bill PDFs    │
│  - 13 Core Migrated Tables│                   │   - Attachment Snapshots  │
│  - Foreign Key Checked    │                   │   - Migration Bundles     │
└───────────────────────────┘                   └───────────────────────────┘
```

---

## 📂 Directory Topology

```
app/
├── Console/Commands/        # Artisan CLI utilities (app:install, app:migration-pack, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/           # Admin Back-Office (Users, Wallets, Backups, Migration Cockpit)
│   │   ├── Api/             # Mobile API (v1 endpoints, dynamic config, batch sync)
│   │   └── Install/         # 4-Step Web Setup Wizard Controller
│   └── Middleware/          # Adaptive compression, rate-limiting, and install locks
├── Models/                  # Eloquent entities (User, ConsumerAccount, BillRecord, Wallet)
└── Services/
    ├── Backup/              # DatabaseDumpService, StorageBackupService
    ├── Installation/        # InstallerService (DB connection tester, env writer)
    ├── Migration/           # ServerMigrationService (bundle pack, unpack, audit)
    └── Notifications/       # Multi-provider email registry & template dispatchers
```

---

## ⚡ Framework Conventions & Runtime Lifecycle

1. **PHP 8.4 Standards**:
   - Constructor property promotion everywhere (`public function __construct(protected DatabaseDumpService $dumpService) {}`).
   - Strict return type declarations and method parameter typing.
   - Match expressions instead of legacy switch statements.

2. **Database Resilience**:
   - Production uses **MySQL 8.0+** with `utf8mb4_unicode_ci`.
   - Local unit and feature tests execute using **SQLite In-Memory (`:memory:`)** with zero mutation to real MySQL records.

3. **Storage Abstraction**:
   - Bill PDFs and media assets reside in `storage/app/public/` linked via `public/storage`.
   - Migration archives reside in `storage/app/migrations/`.
   - Application lock state is controlled via `storage/installed.lock`.

4. **Octane & State Hygiene**:
   - Between requests, runtime in-memory caches are explicitly flushed via `SystemSetting::clearRuntimeCache()`.
