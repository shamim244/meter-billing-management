# Smart Average Tuning Guardrails, Individual Overrides & Exclusions Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement enterprise-grade guardrails for Smart Average Tuning comprising minimum floor clamps, maximum ceiling clamps, automated over-posted meter exclusions ($M < P$), individual per-consumer average overrides, and manual exclusion toggles.

**Architecture:** 
1. **Database Schema & Models**: Add `custom_avg_units`, `is_avg_excluded`, and `avg_exclude_reason` to `consumer_accounts` and `bill_records`.
2. **Service Calculation Layer**: Upgrade `SmartAverageCalculationService` to accept and enforce minimum floors, maximum ceilings, over-posted detection, and individual custom unit overrides.
3. **Controller & Batch Auto-Projection Engine**: Update `DashboardController` (`getData`, `saveTuning`, `bulkProjectReadings`, and new `updateConsumerAverageTuning`) to enforce guardrails and bypass excluded/over-posted accounts during bulk projection.
4. **Interactive Dashboard & Modal UI**: Upgrade `tuning-modal` with Min/Max inputs and exclusion checkboxes, and add individual override/exclude controls to Box 3 on consumer cards and tables with real-time visual badges (`⚠️ Over-Posted`, `🎯 Custom`, `🚫 Excluded`).

**Tech Stack:** Laravel 12 / PHP 8.4, Eloquent ORM, Alpine.js, Tailwind CSS, PHPUnit.

**Spec:** Field requirements defined in user request: Minimum floor clamp, Maximum ceiling clamp, Over-posted automated exclusion ($M < P$), and Individual consumer override/manual exclusion toggle.

## Global Constraints
- **REAL DATA PROTECTION**: User ID 9 (`shamim244d@gmail.com`) and production consumer records are strictly untouchable. All automated tests must use isolated factories or fake CAs (e.g. `9999...`).
- **View Modularity Standard (< 120 lines)**: No Blade file exceeding 120 lines. Keep views separated into single-responsibility partials under `resources/views/dashboard/partials/modals/tuning/` and card boxes.
- **Zero Inline Script Bloat (< 25 lines)**: All frontend logic in `public/js/dashboard/dashboard-app.js`.
- **Set-Based Writes**: Bulk operations in `bulkProjectReadings` must use batch queries and bulk updates.
- **Formatting**: Run `vendor/bin/pint --dirty --format agent` on all modified PHP files.

## Review Focus
1. Over-posted meter ($M < P$) must be excluded from bulk auto-projection by default so its posted reading is not erroneously inflated.
2. Minimum floor clamp must prevent tuned averages from falling below minimum billing load standards (e.g. 30 kWh).
3. Maximum ceiling clamp must cap runaway averages to prevent excessive bill shocks (e.g. 180 kWh).
4. Individual CA overrides must take precedence over global percentage tuning.
5. Manually excluded consumers must remain untouched during `bulkAutoProjectAll()`.

---

### Task 1: Database Migration & Model Schema Attributes

**Files:**
- Create: `database/migrations/2026_10_16_000001_add_smart_avg_guardrails_and_exclusions.php`
- Modify: `app/Models/ConsumerAccount.php:15-55`
- Modify: `app/Models/BillRecord.php:20-65`
- Test: `tests/Feature/SmartAvgGuardrailsMigrationTest.php`

**Interfaces:**
- `ConsumerAccount`: `$custom_avg_units` (int|null), `$is_avg_excluded` (bool), `$avg_exclude_reason` (string|null).
- `BillRecord`: `$custom_avg_units` (int|null), `$is_avg_excluded` (bool), `$avg_exclude_reason` (string|null).

- [ ] **Step 1: Write the failing test for schema attributes**

```php
namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmartAvgGuardrailsMigrationTest extends TestCase
{
    public function test_consumer_accounts_and_bill_records_have_guardrail_and_exclusion_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('consumer_accounts', 'custom_avg_units'));
        $this->assertTrue(Schema::hasColumn('consumer_accounts', 'is_avg_excluded'));
        $this->assertTrue(Schema::hasColumn('consumer_accounts', 'avg_exclude_reason'));

        $this->assertTrue(Schema::hasColumn('bill_records', 'custom_avg_units'));
        $this->assertTrue(Schema::hasColumn('bill_records', 'is_avg_excluded'));
        $this->assertTrue(Schema::hasColumn('bill_records', 'avg_exclude_reason'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/SmartAvgGuardrailsMigrationTest.php`
Expected: FAIL with column not found assertion.

- [ ] **Step 3: Create migration and update model `$fillable` and `$casts`**

Create migration `database/migrations/2026_10_16_000001_add_smart_avg_guardrails_and_exclusions.php` adding columns with null/default values.
Update `app/Models/ConsumerAccount.php` and `app/Models/BillRecord.php` to include columns in `$fillable` and `$casts`.
Run `php artisan migrate --force`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/SmartAvgGuardrailsMigrationTest.php`
Expected: PASS

- [ ] **Step 5: Commit changes**

```bash
git add database/migrations/ app/Models/ tests/Feature/SmartAvgGuardrailsMigrationTest.php
git commit -m "feat(database): add smart average guardrails and exclusion columns"
```

---

### Task 2: Service Layer Guardrails & Exclusion Calculation Engine

**Files:**
- Modify: `app/Services/SmartAverageCalculationService.php:380-505`
- Test: `tests/Unit/SmartAverageGuardrailsCalculationTest.php`

**Interfaces:**
- Method: `calculateSmartAverage(...)` updated with options array or parameters:
  - `min_units_floor`: int (default 0)
  - `max_units_ceiling`: int (default 0)
  - `custom_avg_units`: ?int
  - `is_avg_excluded`: bool
  - `is_over_posted`: bool
  - `auto_exclude_overposted`: bool (default true)
- Return shape updated to include:
  - `is_excluded`: bool
  - `exclude_reason`: ?string
  - `is_custom_override`: bool
  - `is_clamped_min`: bool
  - `is_clamped_max`: bool

- [ ] **Step 1: Write failing unit test for guardrails, clamps, and exclusions**

```php
namespace Tests\Unit;

use App\Services\SmartAverageCalculationService;
use Tests\TestCase;

class SmartAverageGuardrailsCalculationTest extends TestCase
{
    public function test_custom_avg_override_takes_precedence_over_history_and_tuning(): void
    {
        $service = app(SmartAverageCalculationService::class);
        $result = $service->calculateSmartAverage(
            caNumber: '99990001',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 20,
            tuningSteps: [20],
            meterReadingHistories: collect(),
            guardrails: ['custom_avg_units' => 65]
        );

        $this->assertEquals(65, $result['avg_units']);
        $this->assertTrue($result['is_custom_override']);
    }

    public function test_minimum_floor_and_maximum_ceiling_clamp(): void
    {
        $service = app(SmartAverageCalculationService::class);
        // Test Min Clamp: base 15 clamped to min 30
        $minClamped = $service->calculateSmartAverage(
            caNumber: '99990002',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 0,
            tuningSteps: [],
            meterReadingHistories: collect(),
            guardrails: ['min_units_floor' => 30, 'custom_avg_units' => 15]
        );
        $this->assertEquals(30, $minClamped['avg_units']);
        $this->assertTrue($minClamped['is_clamped_min']);

        // Test Max Clamp: base 220 clamped to max 180
        $maxClamped = $service->calculateSmartAverage(
            caNumber: '99990003',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 0,
            tuningSteps: [],
            meterReadingHistories: collect(),
            guardrails: ['max_units_ceiling' => 180, 'custom_avg_units' => 220]
        );
        $this->assertEquals(180, $maxClamped['avg_units']);
        $this->assertTrue($maxClamped['is_clamped_max']);
    }

    public function test_overposted_and_manual_exclusion(): void
    {
        $service = app(SmartAverageCalculationService::class);
        $excluded = $service->calculateSmartAverage(
            caNumber: '99990004',
            month: 10,
            year: 2026,
            basis: 'OK',
            currentBill: null,
            consumer: null,
            history: collect(),
            tuningPercent: 10,
            tuningSteps: [],
            meterReadingHistories: collect(),
            guardrails: ['is_over_posted' => true, 'auto_exclude_overposted' => true]
        );

        $this->assertTrue($excluded['is_excluded']);
        $this->assertStringContainsString('Over-Posted', $excluded['exclude_reason']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Unit/SmartAverageGuardrailsCalculationTest.php`
Expected: FAIL

- [ ] **Step 3: Implement guardrail evaluation and clamps in `SmartAverageCalculationService.php`**

Implement `$guardrails` parameter in `calculateSmartAverage`:
1. If `$guardrails['custom_avg_units']` is set, set `$avgUnits = (int) $guardrails['custom_avg_units']`, `$isCustomOverride = true`.
2. Evaluate `$minFloor = (int) ($guardrails['min_units_floor'] ?? 0)`. If `$avgUnits < $minFloor && $minFloor > 0`, clamp `$avgUnits = $minFloor`, `$isClampedMin = true`.
3. Evaluate `$maxCeiling = (int) ($guardrails['max_units_ceiling'] ?? 0)`. If `$avgUnits > $maxCeiling && $maxCeiling > 0`, clamp `$avgUnits = $maxCeiling`, `$isClampedMax = true`.
4. If `$guardrails['is_over_posted'] && ($guardrails['auto_exclude_overposted'] ?? true)` or `$guardrails['is_avg_excluded']`, mark `$isExcluded = true`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Unit/SmartAverageGuardrailsCalculationTest.php`
Expected: PASS

- [ ] **Step 5: Commit changes**

```bash
git add app/Services/SmartAverageCalculationService.php tests/Unit/SmartAverageGuardrailsCalculationTest.php
git commit -m "feat(services): implement smart average guardrails, min-max clamps, and exclusion logic"
```

---

### Task 3: Controller API Endpoints & Bulk Projection Protection

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php:595-715, 1120-1240, 1565-1620`
- Modify: `routes/web.php`
- Test: `tests/Feature/DashboardSmartAvgGuardrailsTest.php`

**Interfaces:**
- `DashboardController::saveTuning()`: stores `min_units_floor`, `max_units_ceiling`, `auto_exclude_overposted`, `exclude_critical_doubt` in session.
- `DashboardController::bulkProjectReadings()`: skips accounts where `is_avg_excluded === true` or (`is_over_posted === true` and `auto_exclude_overposted === true`).
- `DashboardController::updateConsumerAverageTuning(Request $request, string $ca)`: endpoint to update individual CA override (`custom_avg_units`, `is_avg_excluded`, `avg_exclude_reason`).

- [ ] **Step 1: Write feature tests for controller guardrails and exclusion endpoints**

```php
namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSmartAvgGuardrailsTest extends TestCase
{
    public function test_save_tuning_persists_min_max_and_exclusion_settings_in_session(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/dashboard/tuning', [
            'percent' => 10,
            'steps' => [10],
            'base_units' => 50,
            'min_units_floor' => 30,
            'max_units_ceiling' => 150,
            'auto_exclude_overposted' => true,
        ]);

        $response->assertOk();
        $this->assertEquals(30, session('dashboard_tuning_min_floor'));
        $this->assertEquals(150, session('dashboard_tuning_max_ceiling'));
        $this->assertTrue(session('dashboard_tuning_auto_exclude_overposted'));
    }

    public function test_individual_ca_average_tuning_endpoint_updates_consumer_profile(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990099',
            'consumer_name' => 'Test Isolated Consumer',
        ]);

        $response = $this->actingAs($user)->postJson("/consumers/{$consumer->ca_number}/average-tuning", [
            'custom_avg_units' => 75,
            'is_avg_excluded' => true,
            'avg_exclude_reason' => 'Defective slow meter under dispute',
        ]);

        $response->assertOk();
        $consumer->refresh();
        $this->assertEquals(75, $consumer->custom_avg_units);
        $this->assertTrue($consumer->is_avg_excluded);
        $this->assertEquals('Defective slow meter under dispute', $consumer->avg_exclude_reason);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardSmartAvgGuardrailsTest.php`
Expected: FAIL

- [ ] **Step 3: Update `DashboardController` and `routes/web.php`**

1. Register `Route::post('/consumers/{ca}/average-tuning', [DashboardController::class, 'updateConsumerAverageTuning'])->name('consumers.average-tuning');`
2. Update `DashboardController::saveTuning()` to validate and store `min_units_floor`, `max_units_ceiling`, `auto_exclude_overposted`, `exclude_critical_doubt`.
3. In `DashboardController::getData()`:
   - Identify over-posted consumers by comparing `last_physical_reading` with `previous_reading`/`current_reading`.
   - Pass guardrails into `calculateSmartAverage`.
   - Attach `is_avg_excluded`, `avg_exclude_reason`, `is_over_posted`, `custom_avg_units` to each bill payload.
4. In `DashboardController::bulkProjectReadings()`:
   - Filter out accounts where `is_avg_excluded === true` or (`is_over_posted === true` && `auto_exclude_overposted === true`).
   - Increment and report `skipped_excluded_count` in response message.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardSmartAvgGuardrailsTest.php`
Expected: PASS

- [ ] **Step 5: Commit changes**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/DashboardSmartAvgGuardrailsTest.php
git commit -m "feat(api): add individual average tuning endpoint and guardrail session persistence"
```

---

### Task 4: UI Upgrades for Tuning Modal & Card/Table Box 3

**Files:**
- Modify: `resources/views/dashboard/partials/modals/tuning/step-controls.blade.php:1-55`
- Modify: `resources/views/dashboard/partials/modals/tuning/simulation-preview.blade.php:1-47`
- Modify: `resources/views/dashboard/partials/card/boxes/box3-avg.blade.php:1-15`
- Modify: `resources/views/dashboard/partials/table/cell-readings.blade.php:60-75`
- Modify: `public/js/dashboard/dashboard-app.js:1280-1365`
- Test: `tests/Feature/DashboardModularArchitectureTest.php`

**Interfaces:**
- Tuning Modal:
  - Guardrail inputs: `tuningMinFloor`, `tuningMaxCeiling`.
  - Exclusion toggles: `tuningAutoExcludeOverposted`, `tuningExcludeCriticalDoubt`.
  - Live preview stats: Count of active vs excluded accounts in current view.
- Box 3 (Card & Table):
  - Visual Badges:
    - `⚠️ Over-Posted (Excluded)`
    - `🚫 Excluded (Manual)`
    - `🎯 Custom (X kWh)`
    - `⚡ Normal (X kWh)`
  - Quick Override popover / action modal trigger to set individual average or toggle exclusion.

- [ ] **Step 1: Write/Update architectural test ensuring line counts adhere to < 120 lines**

Verify `resources/views/dashboard/partials/modals/tuning/step-controls.blade.php` and `resources/views/dashboard/partials/card/boxes/box3-avg.blade.php` remain under 120 lines.

- [ ] **Step 2: Update `step-controls.blade.php` and `simulation-preview.blade.php`**

Add Safe Range & Guardrails section (`min_floor`, `max_ceiling`) and Auto-Exclusion toggles.
Ensure clean dark-mode OLED Tailwind styling.

- [ ] **Step 3: Update `box3-avg.blade.php` and `cell-readings.blade.php`**

Display conditional badges for `is_avg_excluded`, `is_over_posted`, and `custom_avg_units`.
Add quick trigger for individual CA override modal/popover.

- [ ] **Step 4: Update `dashboard-app.js` with client state & API calls**

Add:
- `tuningMinFloor: 30`, `tuningMaxCeiling: 180`, `tuningAutoExcludeOverposted: true`, `tuningExcludeCriticalDoubt: true`.
- `updateIndividualAverageTuning(ca, customUnits, isExcluded, reason)`.
- Live exclusion counts computed over currently loaded `bills` dataset.

- [ ] **Step 5: Run tests and Pint formatter**

Run `vendor/bin/pint --dirty --format agent`
Run `vendor/bin/phpunit.bat tests/Feature/DashboardModularArchitectureTest.php tests/Feature/DashboardSmartAvgGuardrailsTest.php`
Expected: PASS

- [ ] **Step 6: Commit changes**

```bash
git add resources/views/dashboard/ public/js/dashboard/ tests/
git commit -m "feat(ui): implement smart average guardrails UI, individual overrides, and exclusion badges"
```

---

### Task 5: End-to-End Regression Testing, Verification & Git Remote Release

**Files:**
- Test: Full Feature Suite (`tests/Feature/DashboardSmartAvgGuardrailsTest.php`, `tests/Unit/SmartAverageGuardrailsCalculationTest.php`, `tests/Feature/ConsumerDedicatedPageTest.php`)

- [ ] **Step 1: Execute all affected test suites**

Run: `vendor/bin/phpunit.bat` on all relevant feature tests.
Verify 100% pass, zero errors.

- [ ] **Step 2: Verify Strict Real Data Protection**

Check that no live accounts or User ID 9 (`shamim244d@gmail.com`) were touched.

- [ ] **Step 3: Run Pint Code Formatter**

Run: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 4: Git Push & Release Tag**

Push changes to `origin/main`.
Create and push release tag (e.g. `v2.2.0-stable`).
