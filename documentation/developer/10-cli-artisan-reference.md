# 10 — Custom Artisan CLI Command Reference

This dictionary documents all custom Artisan console commands available in the application.

---

## 🔍 `app:preflight-check`

Audits host system readiness, PHP extensions, memory limits, writable storage permissions, and database connectivity.

### Signature:
```bash
php artisan app:preflight-check
```

### Options:
* `--json`: Output raw JSON for automated CI/CD monitoring scripts.

### Sample Output:
```
🔍 Running Full Server Environment Audit...

• PHP Version: 8.4.4 [✅ OK]
+-----------+-----------+------+
| Extension | Status    | Pass |
+-----------+-----------+------+
| pdo_mysql | INSTALLED | ✅   |
| bcmath    | INSTALLED | ✅   |
| mbstring  | INSTALLED | ✅   |
| zip       | INSTALLED | ✅   |
| redis     | INSTALLED | ✅   |
| brotli    | INSTALLED | ✅   |
| zstd      | INSTALLED | ✅   |
+-----------+-----------+------+
• Database (mysql): ✅ CONNECTED
🎉 Server is 100% READY for production hosting!
```

---

## 📦 `app:migration-pack`

Exports an atomic cross-cloud migration bundle containing `database.sql.gz`, `storage.zip`, and cryptographic `manifest.json`.

### Signature:
```bash
php artisan app:migration-pack [options]
```

### Options:
* `--skip-storage`: Exports database only (skips PDFs and media for fast database transfers).
* `--output=`: Target directory to save `.zip` bundle (defaults to `storage/app/migrations/`).

### Example:
```bash
php artisan app:migration-pack --skip-storage
```

---

## 📥 `app:migration-unpack`

Restores a migration bundle into the current server and executes an itemized post-flight verification audit.

### Signature:
```bash
php artisan app:migration-unpack {path} [options]
```

### Arguments:
* `path`: Relative or absolute path to the `.zip` migration package.

### Options:
* `--skip-storage`: Restore database only.
* `--force`: Bypass overwrite confirmations.

### Example:
```bash
php artisan app:migration-unpack storage/app/migrations/migration_2026-09-25_190415.zip
```

---

## ⚡ `app:install`

Universal server installation command supporting interactive terminal prompts or automated headless execution for Docker and VPS provisioners.

### Signature:
```bash
php artisan app:install [options]
```

### Options:
* `--headless`: Non-interactive execution without confirmation prompts.
* `--db-driver=`: Database driver (`mysql` or `sqlite`).
* `--db-host=`: Database hostname (default: `127.0.0.1`).
* `--db-port=`: Database port (default: `3306`).
* `--db-name=`: Database name (default: `meter_billing`).
* `--db-user=`: Database user (default: `root`).
* `--db-pass=`: Database password.
* `--app-url=`: Application root URL (default: `http://localhost`).
* `--admin-name=`: Super Admin user name (default: `Super Admin`).
* `--admin-email=`: Super Admin email.
* `--admin-pass=`: Super Admin password.
* `--package=`: Path to `.zip` migration bundle to restore instead of clean install.
* `--force`: Force installation even if `storage/installed.lock` exists.

### Example:
```bash
php artisan app:install --headless --db-name=meter_billing --admin-email=admin@example.com --admin-pass=Secret123
```
