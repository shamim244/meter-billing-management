# 02 — Universal Installation System

The **Universal Installation System** ensures that the application can be installed and bootstrapped on any infrastructure in under **2 minutes** without requiring terminal commands or manual configuration file editing.

---

## 🛠️ The 3 Deployment Installation Types

| Type | Target Infrastructure | User Interaction | Method |
| :--- | :--- | :--- | :--- |
| **Type 1: Shared Hosting** | cPanel, Plesk, Hostinger (No SSH) | Point & Click Browser | Web Setup Wizard (`/install`) |
| **Type 2: Docker Container** | Cloud VPS, Kubernetes, Docker Compose | 100% Zero-Touch | Automated `docker/entrypoint.sh` |
| **Type 3: Native VPS** | Bare-metal Ubuntu 22.04 / 24.04 | Interactive or Headless | CLI (`php artisan app:install`) |

---

## 🧭 First-Run Detection & Auto-Redirection

Installation status is determined by the presence of a security lock file: `storage/installed.lock`.

```
                    Visitor hits http://your-domain.com/
                                     │
                                     ▼
                     CheckApplicationInstalled Middleware
                                     │
                 ┌───────────────────┴───────────────────┐
                 ▼                                       ▼
    storage/installed.lock EXISTS           storage/installed.lock MISSING
                 │                                       │
                 ▼                                       ▼
     Normal Request Processing            Force session to 'file' driver
   (/install blocked -> redirects)        Redirect immediately to /install
```

### Session Driver Safety Trap
On uninstalled servers where the database has not yet been migrated, standard Laravel session handlers set to `database` would crash with `Table 'sessions' not found`. The middleware dynamically forces `session.driver = 'file'` and `cache.default = 'file'` whenever uninstalled, guaranteeing that the setup wizard boots cleanly on any server.

---

## 🖥️ The 4-Step Web Setup Wizard (`/install`)

The wizard requires **zero npm or asset compilation** because it uses standalone CDN distributions of Tailwind CSS and Alpine.js.

### Step 1: Server Readiness & Compatibility Audit (`/install/step-1`)
* Validates PHP 8.4+ requirement.
* Checks 11 critical extensions (`pdo_mysql`, `bcmath`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `gd`, `redis`, `brotli`, `zstd`).
* Checks directory write permissions for `storage/` and `bootstrap/cache/`.
* The "Next" button is disabled until mandatory checks pass.

### Step 2: Database Configuration & Live AJAX Testing (`/install/step-2`)
* Form fields: Database Driver (`mysql` or `sqlite`), Host, Port, Database Name, Username, Password, and Application URL.
* **Live "Test Connection" Button**: Uses Fetch API against `POST /install/test-db` to attempt a direct PDO connection with a 5-second timeout.
* **Automatic Database Creation**: If MySQL returns error code `1049` (Unknown database), the installer automatically attempts `CREATE DATABASE IF NOT EXISTS \`database\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`.

### Step 3: Setup Mode Selection (`/install/step-3`)
The operator chooses between two options:

#### Option A: Clean Install (New Organization)
1. Prompts for initial Super Admin details: Name, Email, Password.
2. Generates an application encryption key (`APP_KEY`).
3. Executes `php artisan migrate --force`.
4. Seeds base roles (`admin`, `agent`, `worker`) and default system parameters.
5. Creates the primary Super Admin account with role `admin` and `enterprise` plan tier.

#### Option B: 1-Click Migration Restore
1. Prompts the operator to upload their `migration-xxxx.zip` package.
2. Restores the atomic MySQL dump with `SET FOREIGN_KEY_CHECKS=0`.
3. Rehydrates all bill attachments and media into `storage/app/public/`.
4. Executes the 13-table post-flight audit to confirm 100% data integrity.

### Step 4: Finish & Security Lockout (`/install/step-4`)
* Atomically generates `storage/installed.lock` containing installation metadata:
  ```json
  {
      "installed": true,
      "installed_at": "2026-09-25T21:40:00+05:30",
      "version": "1.0.0",
      "method": "clean_install",
      "admin_email": "admin@example.com"
  }
  ```
* Flushes framework caches (`php artisan optimize:clear`).
* Once locked, any subsequent attempt to visit `/install` is immediately redirected to `/login`.

---

## ⌨️ CLI Headless & Automated Installation (`app:install`)

For Docker containers, continuous integration, or native VPS terminal installations:

### Interactive Terminal Walkthrough:
```bash
php artisan app:install
```

### Headless (Non-Interactive) Clean Install:
```bash
php artisan app:install \
  --headless \
  --db-driver=mysql \
  --db-host=127.0.0.1 \
  --db-port=3306 \
  --db-name=meter_billing \
  --db-user=meter_user \
  --db-pass=SecretPassword123 \
  --app-url=https://billing.example.com \
  --admin-name="Primary Admin" \
  --admin-email="admin@example.com" \
  --admin-pass="StrongAdminPass999!"
```

### Headless Migration Bundle Restore:
```bash
php artisan app:install \
  --headless \
  --package=/var/www/meter-billing/storage/app/migrations/migration-2026-09-25.zip \
  --db-name=meter_billing \
  --db-user=meter_user \
  --db-pass=SecretPassword123
```
