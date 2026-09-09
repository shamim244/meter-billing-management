<laravel-boost-guidelines>
=== persistent project rules ===

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

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Test every code change by adding or updating a test.
- Run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
