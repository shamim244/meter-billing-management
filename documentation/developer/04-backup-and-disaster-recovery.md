# 04 — Disaster Recovery & Backup System

The **Backup and Disaster Recovery System** provides atomic database snapshots and persistent file storage archiving to guard against server failure, accidental deletion, or hosting outages.

---

## 💾 Core Backup Architecture

```
                                  Backup Trigger
                          (Admin UI or Scheduled Cron)
                                       │
                 ┌─────────────────────┴─────────────────────┐
                 ▼                                           ▼
       DatabaseDumpService                          StorageBackupService
                 │                                           │
       Check mysqldump CLI?                         Enumerate storage/app/public/
         ├─ YES: Native mysqldump                     ├─ Exclude temporary caches
         └─ NO:  Streaming PDO Chunk Dumper           └─ Compress into storage.zip
                 │                                           │
                 ▼                                           ▼
       Atomic database.sql.gz                     Persistent storage.zip
                 └─────────────────────┬─────────────────────┘
                                       │
                                       ▼
                       Disaster Recovery Archive (.zip)
                          storage/app/backups/
```

---

## ⚡ The Streaming PDO Dumper (`DatabaseDumpService`)

Traditional Laravel backup packages rely on the `mysqldump` CLI binary. On **Type 1 (Shared Hosting)** or locked-down cloud VPS instances, `mysqldump` is frequently disabled or unavailable.

The `DatabaseDumpService` provides a **Zero-Binary Streaming PDO Dumper**:

1. **Constant Memory ($O(1)$)**: Writes table rows directly into a gzip stream (`gzopen('...', 'w9')`) in chunks of 1,000 rows. It never buffers the full SQL file in PHP memory.
2. **Atomic Integrity**: Prepends `SET FOREIGN_KEY_CHECKS=0;` and appends `SET FOREIGN_KEY_CHECKS=1;` to avoid constraint failures during re-import.
3. **Driver Agnostic**: Detects whether the active database connection is **MySQL** or **SQLite** and emits the correct DDL dialects and pragma directives.

---

## 📂 Storage Archiving (`StorageBackupService`)

The storage backup engine archives:
* Consumer bill PDFs (`storage/app/public/users/{id}/pdfs/`).
* Uploaded reading attachments, meter photos, and inspection images.
* System avatars and public assets.

It explicitly excludes transient logs, framework view caches, and session files to keep archive sizes minimal.

---

## 🖥️ Admin Disaster Recovery Cockpit (`/admin/backups`)

Accessible to administrators under **Admin > System Config & Logs > Disaster Recovery & Backups**:

* **Create Backup Now**: Triggers immediate synchronous generation of an atomic backup.
* **Download Backup**: Streams the `.zip` archive directly to your local computer for offsite redundancy.
* **Inspect Manifest**: Displays backup creation timestamp, database table counts, and archive SHA-256 hash.
* **Retention Cleanup**: Automatically prunes archives older than the configured retention policy (default: 30 days).

---

## 🔄 Disaster Recovery Restoration Runbook

### Step 1: Restore Database
```bash
# Decompress database snapshot
gunzip < database.sql.gz > restore.sql

# Import into MySQL database
mysql -u [user] -p [database_name] < restore.sql
```

### Step 2: Restore Media Storage
```bash
# Extract storage archive into Laravel public storage
unzip -o storage.zip -d /var/www/meter-billing/storage/app/public/

# Recreate storage symlink
php artisan storage:link --force
```

### Step 3: Clear Framework Caches
```bash
php artisan optimize:clear
```
