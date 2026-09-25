# AI Agent Guidelines & Invariant Rules

> **MANDATORY POLICY FOR ALL AI AGENTS & AUTOMATED CODING ASSISTANTS**  
> (Antigravity, Cursor, Claude, GitHub Copilot, ChatGPT, DeepSeek, etc.)

---

## 🚨 HIGHEST-PRIORITY RULE: REAL DATA PROTECTION

Anything that the user has created, entered, selected, clicked, edited, updated, processed, or otherwise worked on is **strictly PROTECTED**.

This protection applies unconditionally to:
- **User ID 9 (`shamim244d@gmail.com`)**: Under no circumstances should this user account, its wallet, its password, or its MRU assignments be altered, impersonated for destructive tests, or deleted.
- **Consumer Numbers (CAs) & Records**: Real electricity consumer numbers and bills in `nbpdcl_billing`.
- **Bill Entries, Meter Readings, & Billing Statuses**: `Submitted`, `Critical`, `Doubt`, etc.
- **Form Inputs, Selections, & Remarks**.

### Strict Prohibitions:
* ❌ Do NOT edit real user records.
* ❌ Do NOT delete real user records.
* ❌ Do NOT reset or overwrite real user values.
* ❌ Do NOT run `migrate:fresh`, `migrate:reset`, or `db:wipe` on live databases.
* ❌ Do NOT use real user records for testing.

> **Core Principle**: The fact that a record exists in the database does NOT mean it is available for testing. If the user has worked on it, assume it is protected.

---

## 🟢 Testing Protocol (Mandatory Safe Isolation)

1. **In-Memory SQLite Only**:
   All PHPUnit feature and unit tests must run against SQLite In-Memory (`:memory:`) configured in `phpunit.xml`.
2. **Dedicated Test Accounts**:
   If a test creates a user, create an ephemeral user via `User::factory()->create()` or use isolated test IDs (e.g. User ID 8 / isolated test MRUs).
3. **No `.env` Mutation in Tests**:
   Tests must never overwrite `.env` on disk. Environment mutations must be localized to runtime `config([...])`.

---

## 🔵 Migration & Installation Integrity

1. **Zero Data Loss**: Any migration operation must execute a post-flight audit verifying exact row counts against `manifest.json`.
2. **Ledger Integrity**: Floating-point balance checks must match within $\pm 0.01$ of the source manifest.
3. **Preserve `APP_KEY`**: Never regenerate `APP_KEY` on an existing database, as it destroys the ability to decrypt existing secrets and remember tokens.

---

## 🎨 Code Style & Quality Standards

1. **PHP 8.4 Conventions**:
   - Use constructor property promotion (`public function __construct(protected ServerMigrationService $service) {}`).
   - Use explicit return type declarations on all methods (`: void`, `: bool`, `: array`, `: Response`).
   - Use curly braces for all control structures.
2. **Pint Formatting**:
   - Always run `vendor/bin/pint --dirty --format agent` before finalizing changes.
3. **Testing Enforcement**:
   - Every code change or new endpoint must be backed by a passing test in `tests/Feature/`.
