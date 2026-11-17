# 🚀 Application Deployment & CI/CD Architecture

This document serves as the permanent source of truth for the deployment setup, server architecture, and CI/CD pipelines across environments. **All development agents and contributors must follow these conventions.**

---

## 🏛️ Philosophy: The "WordPress Perspective" of Zero-Friction Updates

Just like a modern WordPress system where updating themes or plugins never breaks your media library or database:
1. **Protected Uploads**: The `storage/` directory and `public/storage/` symlink (equivalent to `wp-content/uploads/`) are **strictly excluded from CI/CD overwrites**. User uploads, bill PDFs, and attachments are never deleted or replaced.
2. **Protected Credentials**: The `.env` file (equivalent to `wp-config.php`) is **never overwritten** by deployment pipelines. Server-specific database passwords, Redis configurations, and keys remain untouched.
3. **Safe, Incremental Schema Updates**: Only `php artisan migrate --force` is executed. Migrations are non-destructive and only apply new schema additions. `migrate:fresh` or destructive table drops are strictly forbidden.
4. **Cache Invalidation**: On every deployment, framework caches are cleared (`php artisan optimize:clear`) to immediately load new code changes without stale cache conflicts.

---

## 🎯 Dual Deployment Targets

### Target 1: Dedicated VPS (Ubuntu + Docker Compose)
* **Local Staging VM / Production VPS**: Ubuntu Server (Docker v29.8+, Docker Compose v5.5+).
* **Architecture**:
  * `meter_billing_web`: Nginx reverse proxy on port 80 routing to FastCGI.
  * `meter_billing_app`: PHP 8.4-FPM container with extensions: `pdo_mysql`, `redis`, `sockets`, `brotli`, `zstd`, `gd`, `intl`, `zip`.
  * `meter_billing_db`: MySQL 8.0 with persistent volume `mysql_data`.
  * `meter_billing_redis`: Redis 7 Alpine for high-speed cache, sessions, and queues.
  * `meter_billing_queue`: Background worker running `queue:work`.
  * `meter_billing_scheduler`: Task runner running `schedule:work`.
* **CI/CD Workflow**: [`.github/workflows/deploy-vps.yml`](.github/workflows/deploy-vps.yml)
  * Triggers via SSH on push or manual dispatch.
  * Commands: `git pull`, `composer install --no-dev`, `php artisan migrate --force`, `php artisan optimize:clear`, `docker compose restart app queue scheduler`.

---

### Target 2: Shared Hosting (Hostinger / cPanel)
* **Environment**: Traditional shared hosting environment (no Docker required).
* **CI/CD Workflow**: [`.github/workflows/deploy-shared.yml`](.github/workflows/deploy-shared.yml)
* **Automation Flow**:
  1. **Build on GitHub Runners**: Installs PHP 8.4 Composer packages (`--no-dev`) and compiles Vite assets via Bun (`bun run build`).
  2. **Lean Rsync Sync**: Transfers application code + `vendor` + `public/build` via SSH/Rsync.
  3. **Strict Exclusions**: Excludes `.env`, `.git`, `tests/`, `/storage/`, and `/public/storage`.
  4. **Post-Deployment Hooks**: Sets `chmod -R 775 bootstrap/cache`, verifies storage symlink, runs safe migrations, and clears caches (`php artisan optimize:clear`).
* **Auto-Trigger Rules**:
  * Triggers automatically on push to `main`.
  * Ignores documentation (`**.md`), tests (`tests/**`), and prompt edits.
  * Auto-cancels outdated runs if multiple commits are pushed in quick succession (`cancel-in-progress: true`).
  * Supports `[skip ci]` in commit messages for non-deployment commits.

---

## 🤝 Guide for Development Agents
* When writing new features, **do not touch or delete `.github/workflows/`**.
* Whenever adding new composer packages or npm dependencies, make sure they are compatible with **PHP 8.4** and **Bun/Vite**.
* Always adhere to the project's **Real Data Protection** rules: never run destructive migration rollbacks on live environments.
