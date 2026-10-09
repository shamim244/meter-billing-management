# 🚨 PERMANENT PROJECT RULE: REAL DATA PROTECTION

## 🔴 HIGHEST-PRIORITY RULE: PROTECT USER WORK
Anything that the user has created, entered, selected, clicked, edited, updated, processed, or otherwise worked on is strictly PROTECTED.

This protection applies unconditionally to:
- Consumer numbers (CAs) and consumer records
- Bill entries, meter readings, and billing statuses (Submitted, Critical, Doubt, etc.)
- User-related accounts, login credentials, and profile data (e.g., User ID 9 / shamim244d@gmail.com)
- Form inputs, selections, and notes/remarks
- Records created or modified by the user
- Records the user has actively worked with or inspected
- Any related data belonging to those records

### Strict Prohibitions on Protected Data:
NEVER modify protected data:
- ❌ Do NOT edit it
- ❌ Do NOT delete it
- ❌ Do NOT reset it
- ❌ Do NOT overwrite it
- ❌ Do NOT change its status
- ❌ Do NOT change its values
- ❌ Do NOT submit changes to it
- ❌ Do NOT use it as a test account
- ❌ Do NOT use it for destructive testing
- ❌ Do NOT intentionally trigger actions that could alter it

> **Core Principle**: The fact that a record exists in the database does NOT mean it is available for testing. If the user has worked on it, assume it is protected.

---

## 🟢 TESTING PROTOCOL (MANDATORY SAFE ISOLATION)
Thorough testing of the Laravel application is mandatory, but testing must NEVER compromise real working data.

1. **Use Non-Protected Consumers**:
   - There are many consumer numbers available in the application. Always use other consumers that are not part of the user's work.
   - Consumers from different villages/MRUs may be used for testing. Testing across separate villages is acceptable when necessary.
2. **Dedicated Test Records**:
   - For testing operations that modify data, select a consumer/record that is not protected, or create isolated test accounts/records (e.g., User ID 8 / isolated test MRU).
   - Prefer clearly identifiable test data whenever possible.
   - Never use user's working records simply because they are convenient.
3. **Status Changes & Data Mutation**:
   - If a test requires altering a consumer's status or reading, use a non-protected consumer.
   - Clean up only test data created by the agent itself.

---

## 🟡 WHEN IN DOUBT (ZERO-ASSUMPTION POLICY)
If you cannot determine whether a consumer, entry, or record is part of the user's work:
1. **Treat it as PROTECTED.**
2. **Do NOT guess.**
3. **Choose another record for testing.**
4. If there is no clearly safe alternative, **STOP and ask the user for permission** before modifying anything.
5. Never assume permission to modify real data.

---

## 🔵 MIGRATION INTEGRITY
Because this is a live migration from the old application to Laravel:
- Preserve existing real data without alteration.
- Compare old and new application data without modifying it whenever possible.
- If you find incorrect or inconsistent migrated data, report the discrepancy to the user instead of silently altering or "fixing" it.
- Never use migration data as disposable test data.
- Never truncate, wipe, reset, or run uncontrolled reseeding on databases containing real data.
- Never perform destructive migration or testing operations without explicit user approval.

---

## 🛑 PRE-ACTION SAFETY CHECKLIST
Before executing ANY destructive or data-changing action, you MUST verify:
1. What data will be affected?
2. Is that data protected or potentially part of the user's work?
3. Is there a safe alternative consumer/record for testing?

If the data could be part of the user's work and there is any uncertainty:
👉 **DO NOT EXECUTE THE ACTION. ASK THE USER FIRST.**

---

### SUMMARY PRINCIPLE
**TEST THE APPLICATION — NOT THE USER'S WORK.**
Data safety takes absolute priority over testing convenience.


---

# ⚡ PERMANENT PROJECT RULE: ARCHITECTURAL MODULARITY, SPEED & CPU EFFICIENCY

## 🎯 MANDATORY CORE OBJECTIVE: MAXIMAL EFFICIENCY, LOW SERVER COST, ZERO CLUTTER

To prevent technical debt, context window/token exhaust during AI pair programming, and unnecessary server calculation overhead, ALL new implementations, modifications, and refactors MUST strictly follow these universal laws:

### 1. View Modularity Standard (< 120 Lines Master Rule)
- ❌ **NEVER** write or commit a monolithic Blade template exceeding 120–150 lines.
- ✅ Break all complex pages into single-responsibility partials under `resources/views/<domain>/partials/` (and subdirectories like `modals/`).
- Master views must only contain layout tags, modular CSS links, `@include('...')` directives, and `<script>` configuration bridges.

### 2. Zero Inline Script Bloat (<script> Decoupling Rule)
- ❌ **NEVER** write large inline JavaScript blocks (> 25 lines) or inlined Alpine.js objects inside `.blade.php` files.
- ✅ Extract all JavaScript application logic into dedicated, browser-cacheable files under `public/js/<domain>/<domain>-app.js`.
- ✅ Bridge server-side data (routes, CSRF tokens, user settings) cleanly via a single `window.<domain>Config = { ... }` object in the Blade file.
- The external JS file must contain ZERO Blade syntax (`{{ ... }}`) or raw Blade directives.

### 3. Categorized, Reusable CSS Standard
- ❌ **NEVER** repeat 30+ Tailwind utility classes across dozens of elements in the same file.
- ✅ Abstract repeated patterns into semantic CSS files under `public/css/<domain>/` (e.g., cards, reading boxes, badges, modals, tables).
- Aggregate via `public/css/<domain>/<domain>.css` using `@import` statements.

### 4. Server Calculation Power & Cost Saving Standard
- **CPU Cycles are Costly:** Prefer static assets, browser caching, and database set-based queries over repetitive PHP CPU calculations.
- **Cache-Busting & Caching:** All CSS and JS must be cache-busted with filemtime query strings (`?v={{ filemtime(...) }}`) and served with immutable browser cache headers so workers' browsers download them once and never re-request them.
- **Set-Based Database Writes:** Never write sequential 1-by-1 database save loops in API controllers. Always use bulk `upsert()` and pre-fetched keyed collections.

### 5. Git Safety & Automated Verification
- Before major structural changes, verify tests pass and check Git status.
- Add or update automated tests for all architectural changes.
- Ensure Pint formatting (`vendor/bin/pint --format agent`) is run before finalizing.
