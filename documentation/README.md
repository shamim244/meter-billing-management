# NBPDCL Meter Billing & Management System
## Unified Developer & AI Agent Architecture Documentation

> **Zero-Vendor-Lock-in SaaS Architecture**  
> Built on **Laravel 13**, **PHP 8.4**, and **Tailwind CSS**, supporting **Type 1 (Shared Hosting)**, **Type 2 (Docker)**, and **Type 3 (Native VPS)** deployments with 100% data fidelity.

---

## 🧭 Documentation Portal Structure

This documentation suite is split into two specialized sections:

```
┌────────────────────────────────────────┐   ┌────────────────────────────────────────┐
│   👨‍💻 Human Developer & Operator Side    │   │         🤖 AI Agent / LLM Side         │
│  Guides, architecture, setup runbooks  │   │  Machine-readable schemas & invariants │
└───────────────────┬────────────────────┘   └───────────────────┬────────────────────┘
                    │                                            │
                    ▼                                            ▼
         [Read Developer Docs](#-developer-side)       [Read AI Agent Docs](#-ai-agent-side)
```

---

## 👨‍💻 Developer Side (Guides & Runbooks)

1. [⚡ System Architecture](developer/01-system-architecture.md)  
   Foundational context, Laravel 13 framework features, directory topology, design patterns, and runtime lifecycle.

2. [🛠️ Installation System](developer/02-installation-system.md)  
   Complete guide to the 4-step Web Setup Wizard (`/install`), automated first-run redirection, headless Docker entrypoints, and `php artisan app:install`.

3. [🚀 Cross-Cloud Migration Engine](developer/03-cross-cloud-migration.md)  
   Zero-vendor-lock-in migration engine. Details bundle format, SHA-256 manifest schema, post-flight 13-table audit, and wallet financial ledger integrity checks.

4. [💾 Disaster Recovery & Backups](developer/04-backup-and-disaster-recovery.md)  
   Atomic MySQL dumps (`DatabaseDumpService`), chunked streaming PDO dumper, storage zip backups, and restoration runbooks.

5. [🗜️ Adaptive Hybrid Compression](developer/05-adaptive-compression.md)  
   Next-generation response compression supporting Brotli, Zstandard, and Gzip with dynamic quality negotiation and API performance benchmarks.

6. [⚡ NBPDCL Billing & Extraction Engine](developer/06-nbpdcl-billing-engine.md)  
   Jasper Unicode PDF extraction, OCR fallback strategies, working reading calculations, and cascade protection mechanisms.

7. [📱 Mobile App Resilience](developer/07-mobile-app-resilience.md)  
   Flutter mobile app dynamic endpoint discovery (`GET /api/v1/app/config`), failover URL routing, minimum app versioning, and QR code pairing.

8. [🔔 Notification & Email Hub](developer/08-notifications-and-email.md)  
   Multi-provider email failover engine (Brevo, Resend, Hostinger IMAP/SMTP), customizable notification templates, and the failed critical queue.

9. [💳 Wallets, Plans & Payments](developer/09-wallets-and-plans.md)  
   Bavix double-entry wallet ledger, wallet balance freezes, subscription plans, duration overrides, and the payment gateway simulator.

10. [⌨️ CLI Artisan Reference](developer/10-cli-artisan-reference.md)  
    Comprehensive dictionary of all custom console commands (`app:preflight-check`, `app:migration-pack`, `app:migration-unpack`, `app:install`, etc.).

---

## 🤖 AI Agent Side (Context & Machine-Readable Contracts)

1. [📄 llms.txt](ai-agent/llms.txt)  
   Standard machine-readable prompt index designed for Cursor, Claude, Antigravity, and OpenAI agents to digest project context in a single shot.

2. [🛡️ AI Agent Guidelines & Real Data Protection](ai-agent/ai-agent-guidelines.md)  
   Permanent project rules, strict zero-touch protection for User ID 9 (`shamim244d@gmail.com`) and production billing entries, and testing isolation rules.

3. [🗄️ Database Schema & Invariants](ai-agent/database-schema.md)  
   Exact MySQL schema definitions, table relationships, foreign key constraints, and critical model invariants.

4. [🔌 REST API Contracts](ai-agent/api-contracts.md)  
   Complete specifications for `/api/v1/*` endpoints, authentication headers, rate limits, request payloads, and standardized error formats.

---

## ⚡ Technology Stack Summary

| Layer | Component | Specification |
| :--- | :--- | :--- |
| **Backend** | Framework | Laravel 13.x |
| **Language** | PHP Engine | PHP 8.4+ with constructor promotion & explicit return types |
| **Database** | Relational Database | MySQL 8.0+ (Production) / SQLite In-Memory (PHPUnit Testing) |
| **Cache & Queue** | In-Memory Accelerator | Redis 7.x (Optional fallback to file driver on shared hosts) |
| **Web Server** | HTTP Server | Nginx with PHP8.4-FPM socket / Apache on Shared Hosts |
| **Compression** | Algorithmic Engines | Brotli (level 4-6), Zstandard (level 3), Gzip (level 6) |
| **Frontend** | Styling & UI | Tailwind CSS v3 / Blade Components / Alpine.js |
| **Testing** | Automated Quality | PHPUnit 12+ (431 automated tests, 100% pass rate) |
