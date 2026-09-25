# 03 — Cross-Cloud Migration Engine

The **Cross-Cloud Migration Engine** enables seamless, zero-vendor-lock-in migration between any hosting provider (DigitalOcean, AWS, Hetzner, Hostinger, Google Cloud, Linode, or on-premise hardware) across **all 3 deployment types** with zero data loss.

---

## 📦 Anatomy of a Universal Migration Package

Every export generated via the Admin Panel or CLI creates a standard `.zip` file stored in `storage/app/migrations/` (e.g. `migration_2026-09-25_190415.zip`).

```
migration_bundle.zip
├── manifest.json         # Cryptographic checksums, table row counts, financial ledger sum
├── database.sql.gz       # Gzip-compressed atomic snapshot of the MySQL database
└── storage.zip           # Compressed archive of public storage (bill PDFs, attachments)
```

---

## 🔍 The Verification Manifest (`manifest.json`) Schema

The manifest serves as the single source of truth for post-flight integrity auditing:

```json
{
  "app_name": "NBPDCL Meter Billing",
  "php_version": "8.4.4",
  "laravel_version": "13.8.0",
  "commit_sha": "6ce724a",
  "created_at": "2026-09-25T13:30:00+00:00",
  "export_type": "universal_cross_cloud",
  "checksums": {
    "database_sql_gz": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
    "storage_zip": "a8f5f167f44f4964e6c998dee827110c"
  },
  "sizes": {
    "database_bytes": 1048576,
    "storage_bytes": 52428800
  },
  "table_counts": {
    "users": 15,
    "consumer_accounts": 1250,
    "bill_records": 3400,
    "bill_statuses": 3400,
    "meter_reading_histories": 6800,
    "mrus": 24,
    "wallets": 15,
    "transactions": 48,
    "system_settings": 35,
    "api_keys": 6,
    "issue_reports": 2,
    "roles": 3,
    "permissions": 18
  },
  "financial_checksum": {
    "total_wallet_balance": 14500.50
  }
}
```

### The Financial Ledger Checksum
In addition to counting database rows, the manifest calculates the floating-point sum of all `wallets.balance` records. During the post-flight audit on the destination server, the auditor re-calculates the sum:
$$\left| \sum \text{Balance}_{\text{live}} - \sum \text{Balance}_{\text{manifest}} \right| < 0.01$$
If a single transaction or penny is missing, the restore issues a critical audit warning.

---

## 🛡️ Bidirectional Cross-Type Portability

Because the package uses standard `.sql.gz` and standard `.zip`, it is **completely agnostic** to the underlying operating system or container environment:

* A bundle exported from **Type 1 (Shared Hosting)** can be restored on **Type 2 (Docker)** or **Type 3 (Native VPS)**.
* A bundle exported from **Type 3 (Bare-metal Ubuntu)** can be restored directly via the Web UI on **Type 1 (Hostinger cPanel)**.

---

## 🖥️ Web Admin Cockpit (`/admin/server-migration`)

Accessible by system administrators under **Admin > System Config & Logs > Cloud Migration & Portability**:

1. **Preflight Readiness Card**: Displays live PHP version, PDO drivers, memory limits, and writable folder health.
2. **Export Migration Package**: 1-click button to compile the atomic `.zip` package. Includes an optional checkbox: *"Skip storage (Database-only for rapid transfer)"*.
3. **Import & Rehydrate Server**: Uploads a bundle, unzips the database, restores storage assets, runs foreign key checks, and displays an itemized verification table.
4. **Existing Bundles Table**: Lists all `.zip` files stored on the local server with **Download** and **Delete** actions.

---

## ⌨️ CLI Command Reference

### Exporting a Migration Package (`app:migration-pack`)
```bash
# Standard export with database and media storage:
php artisan app:migration-pack

# Database-only export (skips storage for lightweight sync):
php artisan app:migration-pack --skip-storage
```

**Output**:
```
📦 Compressing universal migration package...
   Package created: storage/app/migrations/migration_2026-09-25_190415.zip (52.4 MB)
   SHA-256 Checksum: 8f4e2b...3a1c
   Manifest: 13 core tables, total wallet balance audited.

📋 Next Step — Copy to Destination Server:
   scp storage/app/migrations/migration_2026-09-25_190415.zip user@dest-ip:/var/www/meter-billing/storage/app/migrations/
```

### Restoring and Auditing a Package (`app:migration-unpack`)
```bash
php artisan app:migration-unpack storage/app/migrations/migration_2026-09-25_190415.zip
```

**Output**:
```
🔍 Inspecting Migration Package...
   Valid: YES
   Created: 2026-09-25 19:04:15 UTC
   Source Commit: 6ce724a

⚡ Restoring database snapshot (database.sql.gz)...
⚡ Rehydrating storage files (storage.zip)...
🔍 Executing Post-Flight Integrity Audit across 13 Core Tables:

+-------------------------+----------+--------+--------+
| Table                   | Expected | Actual | Status |
+-------------------------+----------+--------+--------+
| users                   | 15       | 15     | MATCH  |
| consumer_accounts       | 1250     | 1250   | MATCH  |
| bill_records            | 3400     | 3400   | MATCH  |
| wallets                 | 15       | 15     | MATCH  |
+-------------------------+----------+--------+--------+
• Total Wallet Balance: Rs. 14,500.50 (MATCHED 100%)

🎉 Migration Restored with 100% Data Fidelity! Zero Discrepancies.
```

---

## ⚠️ Important Note on `APP_KEY`
Laravel encrypts passwords, session tokens, and sensitive database columns using `APP_KEY`. **Always copy your `APP_KEY` from the source server's `.env` to the destination server's `.env`**. If `APP_KEY` changes, existing user passwords and encrypted tokens cannot be decrypted.
