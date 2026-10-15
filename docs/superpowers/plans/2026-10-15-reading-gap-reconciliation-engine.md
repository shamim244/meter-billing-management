# Dynamic Reading Gap & Event-Driven Reconciliation Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [x]`) syntax for tracking.

**Goal:** Build a robust, event-driven Reading Gap and Reconciliation Engine that manages divergences between physical meter readings and official posted bills, providing 1-click hold modes (+0/+1 unit) for over-posted bills and smooth monthly amortization (capped at safe tariff thresholds: 200–250 kWh) for unbilled accumulated gaps across variable inspection intervals.

**Architecture:** Event-driven reconciliation architecture anchoring on ad-hoc physical inspection events while utilizing the consumer's verified Smart Average ($A_{\text{avg}}$) as the continuous burn rate anchor for unvisited monthly billing cycles. The engine calculates recovery slices, enforces tariff-based safe ceilings, and logs historical inspection audit trails.

**Tech Stack:** Laravel 12 (PHP 8.4), MySQL, Eloquent ORM, Alpine.js, Tailwind CSS, PHPUnit.

**Spec:** `.agents/docs/prd/13-Dynamic_Reading_Gap_and_Reconciliation_Engine_PRD.md`

## Global Constraints

- **REAL DATA PROTECTION**: User ID 9 (`shamim244d@gmail.com`) and all live consumer accounts/bills are strictly protected. Never mutate or test with real consumer data. Use factories and fake CAs (`9999...`).
- **View Modularity Standard**: Blade templates must stay strictly under 120–150 lines. Keep components decomposed into single-responsibility partials under `resources/views/<domain>/partials/`.
- **Zero Inline Script Bloat**: All JavaScript must live in `public/js/` browser-cacheable files decoupled from Blade syntax.
- **Migration Idempotency**: All migrations must be idempotent using `Schema::hasTable()`, `Schema::hasColumn()`, and safe down methods.
- **Pint Formatting**: Run `vendor/bin/pint --dirty --format agent` on modified PHP files before finalizing tasks.

## Review Focus

- Physical reading less than previous posted reading (over-posted / negative gap) must never allow official bill to submit $< \text{Previous}$, enforcing $+0$ or $+1$ unit hold mode.
- Physical reading greater than posted reading by thousands of units (accumulated reading debt) must strictly clamp monthly bill to the safe ceiling ($C_{\text{safe}}$: 200–250 kWh/mo) to prevent spike audits.
- Variable inspection intervals (15 days vs 6 months vs 1 year) must dynamically calculate elapsed days and remaining drift without breaking trajectory math.
- Dial rollover / meter replacement cases (e.g. meter reset to 00010) must be detected to prevent erroneous negative gaps.
- Safe ceiling presets must respect tariff categories (DS-1D domestic vs NDS-1D commercial vs IAS-1 agriculture).

---

### Task 1: Database Foundation & Inspection Audit Model

**Files:**
- Create: `database/migrations/2026_10_15_000002_add_reading_gap_columns_to_consumer_accounts_and_bills.php`
- Create: `database/migrations/2026_10_15_000003_create_meter_inspections_table.php`
- Create: `app/Models/MeterInspection.php`
- Modify: `app/Models/ConsumerAccount.php:15-48`
- Modify: `app/Models/BillRecord.php:14-55`
- Test: `tests/Feature/MeterInspectionDatabaseTest.php`

**Interfaces:**
- Produces: `MeterInspection` model with relationships `consumerAccount()`, `user()`, and scopes `latestForCa()`.
- Produces: `ConsumerAccount` attributes `last_physical_reading`, `last_inspected_at`, `gap_recovery_mode`, `custom_safe_ceiling`.
- Produces: `BillRecord` attributes `physical_reading`, `reading_gap`, `gap_slice_applied`, `gap_action_applied`.

- [x] **Step 1: Write the failing test for schema and models**

```php
// tests/Feature/MeterInspectionDatabaseTest.php
namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\BillRecord;
use App\Models\MeterInspection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterInspectionDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_inspections_table_and_relationships_work(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990001111',
            'consumer_name' => 'Test Consumer',
            'last_physical_reading' => '7000',
            'last_inspected_at' => now(),
            'gap_recovery_mode' => 'auto',
            'custom_safe_ceiling' => 250,
        ]);

        $inspection = MeterInspection::create([
            'user_id' => $user->id,
            'consumer_account_id' => $consumer->id,
            'ca_number' => '99990001111',
            'physical_reading' => '7000',
            'posted_reading_at_inspection' => '5000',
            'gap_at_inspection' => 2000,
            'notes' => 'Site inspection',
            'inspected_at' => now(),
        ]);

        $this->assertDatabaseHas('meter_inspections', ['ca_number' => '99990001111', 'gap_at_inspection' => 2000]);
        $this->assertEquals($consumer->id, $inspection->consumerAccount->id);
    }
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/MeterInspectionDatabaseTest.php`  
Expected: FAIL with missing table/column errors.

- [x] **Step 3: Implement migrations and model updates**

1. Create migration `2026_10_15_000002_add_reading_gap_columns_to_consumer_accounts_and_bills.php`:
   - In `consumer_accounts`: add `last_physical_reading` (string nullable), `last_inspected_at` (timestamp nullable), `inspected_by_user_id` (foreign key nullable), `gap_recovery_mode` (string default 'auto'), `custom_safe_ceiling` (integer nullable).
   - In `bill_records`: add `physical_reading` (string nullable), `reading_gap` (integer nullable), `gap_slice_applied` (integer nullable), `gap_action_applied` (string nullable).
2. Create migration `2026_10_15_000003_create_meter_inspections_table.php`:
   - Table `meter_inspections` with `id`, `user_id`, `consumer_account_id`, `ca_number`, `physical_reading`, `posted_reading_at_inspection`, `gap_at_inspection`, `photo_path`, `notes`, `inspected_at`, `timestamps`.
3. Create `app/Models/MeterInspection.php` with fillable and casts.
4. Update fillable and casts in `ConsumerAccount.php` and `BillRecord.php`.

- [x] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/MeterInspectionDatabaseTest.php`  
Expected: PASS.

- [x] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/ app/Models/ tests/Feature/MeterInspectionDatabaseTest.php
git commit -m "feat(database): add reading gap columns and meter inspections table"
```

---

### Task 2: Core Domain Service (`ReadingGapReconciliationService`)

**Files:**
- Create: `app/Services/Reading/ReadingGapReconciliationService.php`
- Test: `tests/Unit/ReadingGapReconciliationServiceTest.php`

**Interfaces:**
- Consumes: `ConsumerAccount`, `BillRecord`, `MeterInspection`, `SystemSetting`.
- Produces: `recordPhysicalInspection(ConsumerAccount $consumer, string|float $reading, ?Carbon $date, ?string $notes, ?User $user): MeterInspection`.
- Produces: `calculateRecommendation(BillRecord $bill, ConsumerAccount $consumer, ?int $smartAvg = null): array`.
- Produces: `getGapSummary(ConsumerAccount $consumer, ?BillRecord $latestBill = null): array`.

- [x] **Step 1: Write the failing unit tests for reconciliation logic**

```php
// tests/Unit/ReadingGapReconciliationServiceTest.php
namespace Tests\Unit;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\User;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingGapReconciliationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReadingGapReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReadingGapReconciliationService::class);
    }

    public function test_over_posted_bill_recommends_hold_mode_and_catchup_timeline(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990002222',
            'last_physical_reading' => '1200',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990002222',
            'billing_month' => 4,
            'billing_year' => 2026,
            'current_reading' => '1300',
            'previous_reading' => '1300',
        ]);

        $recommendation = $this->service->calculateRecommendation($bill, $consumer, smartAvg: 50);

        $this->assertEquals('over_posted', $recommendation['gap_status']);
        $this->assertEquals(-100, $recommendation['net_gap']);
        $this->assertEquals('1300', $recommendation['recommended_working_reading']);
        $this->assertEquals(2, $recommendation['estimated_catchup_cycles']);
    }

    public function test_under_posted_bill_applies_safe_monthly_ceiling_slice(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990003333',
            'last_physical_reading' => '7000',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);

        $bill = BillRecord::create([
            'user_id' => $user->id,
            'ca_number' => '99990003333',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '5000',
        ]);

        // Monthly average is 100 kWh, safe ceiling is 220 kWh -> gap slice should be 120 kWh -> total units 220 kWh -> reading 5220
        $recommendation = $this->service->calculateRecommendation($bill, $consumer, smartAvg: 100);

        $this->assertEquals('under_posted', $recommendation['gap_status']);
        $this->assertEquals(2000, $recommendation['net_gap']);
        $this->assertEquals(220, $recommendation['billed_units']);
        $this->assertEquals('5220', $recommendation['recommended_working_reading']);
        $this->assertEquals(120, $recommendation['gap_slice_applied']);
    }
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Unit/ReadingGapReconciliationServiceTest.php`  
Expected: FAIL with Class `ReadingGapReconciliationService` not found.

- [x] **Step 3: Implement `ReadingGapReconciliationService`**

Implement `app/Services/Reading/ReadingGapReconciliationService.php`:
- Resolve safe ceiling by tariff category:
  - `DS1D`, `DS-II` -> `SystemSetting::get('gap_safe_ceiling_domestic', 220)`
  - `NDS1D` -> `SystemSetting::get('gap_safe_ceiling_commercial', 400)`
  - `IAS1` -> `SystemSetting::get('gap_safe_ceiling_agriculture', 800)`
- Evaluate $\Delta = M - P$.
- For $\Delta < 0$: Recommended working reading $= P$ (or $P+1$), $\text{catchup\_cycles} = \lceil |\Delta| / A_{\text{avg}} \rceil$.
- For $\Delta > 0$: $\text{Gap Slice} = \max(0, C_{\text{safe}} - A_{\text{avg}})$, $\text{Billed Units} = \min(A_{\text{avg}} + \text{Gap Slice}, P + \Delta - P)$, Recommended working reading $= P + \text{Billed Units}$.
- For $\Delta = 0$: In-Sync normal billing ($P + A_{\text{avg}}$).

- [x] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Unit/ReadingGapReconciliationServiceTest.php`  
Expected: PASS.

- [x] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Reading/ tests/Unit/ReadingGapReconciliationServiceTest.php
git commit -m "feat(service): implement ReadingGapReconciliationService"
```

---

### Task 3: Admin Engine Settings Configuration

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminBillController.php:30-150`
- Modify: `resources/views/admin/bills/partials/engine-settings/reading-gap-card.blade.php` (Create single-responsibility partial < 100 lines)
- Modify: `resources/views/admin/bills/engine-settings.blade.php` (Include new partial)
- Test: `tests/Feature/ReadingGapSettingsTest.php`

**Interfaces:**
- Produces: System settings `gap_safe_ceiling_domestic`, `gap_safe_ceiling_commercial`, `gap_safe_ceiling_agriculture`, `gap_hold_default_mode`, `inspection_stale_warning_days`.

- [x] **Step 1: Write the failing feature test for admin settings**

```php
// tests/Feature/ReadingGapSettingsTest.php
namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReadingGapSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_reading_gap_engine_settings(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.bills.engine-settings.update'), [
            'gap_safe_ceiling_domestic' => 240,
            'gap_safe_ceiling_commercial' => 450,
            'gap_safe_ceiling_agriculture' => 900,
            'gap_hold_default_mode' => 'one',
            'inspection_stale_warning_days' => 180,
        ]);

        $response->assertRedirect();
        $this->assertEquals(240, (int) SystemSetting::get('gap_safe_ceiling_domestic'));
        $this->assertEquals('one', SystemSetting::get('gap_hold_default_mode'));
    }
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/ReadingGapSettingsTest.php`  
Expected: FAIL.

- [x] **Step 3: Implement AdminBillController settings validation and Blade partial**

1. Update `AdminBillController@updateEngineSettings` to validate and persist the new gap settings.
2. Create `resources/views/admin/bills/partials/engine-settings/reading-gap-card.blade.php` (< 100 lines) with numeric steppers for domestic, commercial, and agriculture ceilings, plus stale warning days.
3. `@include('admin.bills.partials.engine-settings.reading-gap-card')` in `engine-settings.blade.php`.

- [x] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/ReadingGapSettingsTest.php`  
Expected: PASS.

- [x] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/ resources/views/admin/bills/ tests/Feature/ReadingGapSettingsTest.php
git commit -m "feat(admin): add reading gap safe ceiling engine settings"
```

---

### Task 4: Dashboard API Endpoints & Data Payload Bridge

**Files:**
- Modify: `app/Http/Controllers/DashboardController.php:730-860`
- Modify: `routes/web.php` (add `/api/reading-gap/record-inspection` and `/api/reading-gap/update-mode`)
- Test: `tests/Feature/DashboardReadingGapApiTest.php`

**Interfaces:**
- Produces: Enhanced `/dashboard/data` JSON payload containing `gap_status`, `net_gap`, `last_physical_reading`, `last_inspected_days_ago`, `gap_slice_applied`, `catchup_cycles`.
- Produces: `POST /api/reading-gap/record-inspection` endpoint returning updated gap calculation.

- [x] **Step 1: Write the failing API test**

```php
// tests/Feature/DashboardReadingGapApiTest.php
namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadingGapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_data_includes_reading_gap_payload(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_GAP_1', 'name' => 'Gap MRU']);
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990004444',
            'last_physical_reading' => '6000',
            'last_inspected_at' => now()->subDays(45),
        ]);
        BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990004444',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '5000',
            'units_consumed' => 80,
        ]);

        $response = $this->actingAs($user)->getJson('/dashboard/data?month=4&year=2026');
        $response->assertOk();
        $item = $response->json('data.0');
        $this->assertArrayHasKey('reading_gap_info', $item);
        $this->assertEquals('under_posted', $item['reading_gap_info']['gap_status']);
    }

    public function test_recording_physical_inspection_updates_consumer_and_logs_audit(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990005555',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990005555',
            'physical_reading' => '7200',
            'notes' => 'Wall meter checked in field',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('success'));
        $this->assertDatabaseHas('consumer_accounts', [
            'ca_number' => '99990005555',
            'last_physical_reading' => '7200',
        ]);
        $this->assertDatabaseHas('meter_inspections', [
            'ca_number' => '99990005555',
            'physical_reading' => '7200',
        ]);
    }
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardReadingGapApiTest.php`  
Expected: FAIL.

- [x] **Step 3: Implement DashboardController payload enrichment and inspection route**

1. In `DashboardController@getData`: loop through items and attach `reading_gap_info` via `ReadingGapReconciliationService`.
2. Add route and controller method `DashboardController@recordInspection` to accept `ca_number`, `physical_reading`, and optional `notes`.
3. Add route `DashboardController@updateGapMode` to update `gap_recovery_mode` or `custom_safe_ceiling`.

- [x] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardReadingGapApiTest.php`  
Expected: PASS.

- [x] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/DashboardReadingGapApiTest.php
git commit -m "feat(api): enrich dashboard payload with reading gap and add inspection endpoint"
```

---

### Task 5: UI Integration & Decoupled Frontend Controls

**Files:**
- Create: `resources/views/dashboard/partials/card/reading-gap-badge.blade.php` (< 60 lines)
- Create: `resources/views/dashboard/partials/modals/reading-gap/inspection-modal.blade.php` (< 90 lines)
- Modify: `resources/views/dashboard/partials/card/reading-box-grid.blade.php:1-60`
- Modify: `resources/views/dashboard/partials/table/table-row.blade.php:1-80`
- Modify: `public/js/dashboard/dashboard-app.js:200-400`
- Test: `tests/Feature/DashboardReadingGapViewTest.php`

**Interfaces:**
- Produces: 1-click `[ +0 Hold ]` & `[ +1 Unit ]` buttons on over-posted cards.
- Produces: Safe recovery badge & remaining progress bar on under-posted cards.
- Produces: Inspection staleness pill (🟢 Fresh, 🟡 60d+, 🔴 Overdue).
- Produces: Decoupled JS functions in `dashboard-app.js`: `openInspectionModal(bill)`, `savePhysicalInspection()`, `setGapHoldMode(bill, mode)`.

- [x] **Step 1: Write the failing view integration test**

```php
// tests/Feature/DashboardReadingGapViewTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadingGapViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_reading_gap_components_cleanly(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('inspection-modal');
    }
}
```

- [x] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardReadingGapViewTest.php`  
Expected: FAIL.

- [x] **Step 3: Implement Blade partials (< 120 lines each) and decoupled JavaScript methods**

1. Create `resources/views/dashboard/partials/card/reading-gap-badge.blade.php`: renders 🟢 In-Sync, 🟡 Hold Mode, 🔴 Recovery progress.
2. Create `resources/views/dashboard/partials/modals/reading-gap/inspection-modal.blade.php`: quick input for physical meter reading + notes with 1-click submit.
3. In `public/js/dashboard/dashboard-app.js`: add methods `savePhysicalInspection()`, `applyGapHold(bill, mode)`.

- [x] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardReadingGapViewTest.php`  
Expected: PASS.

- [x] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/dashboard/ public/js/dashboard/ tests/Feature/DashboardReadingGapViewTest.php
git commit -m "feat(ui): add reading gap badges, 1-click hold controls, and inspection modal"
```

---

### Task 6: End-to-End Regression & Full Verification

**Files:**
- Test: `tests/Feature/FullReadingGapReconciliationFlowTest.php`

**Interfaces:**
- Exercises: Inspection recording -> Gap detection -> Over-posted hold mode -> Under-posted amortization -> New cycle bill sync -> Full recovery.

- [x] **Step 1: Write the full lifecycle integration test**

```php
// tests/Feature/FullReadingGapReconciliationFlowTest.php
namespace Tests\Feature;

use App\Models\BillRecord;
use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullReadingGapReconciliationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_gap_amortization_lifecycle(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'LIFECYCLE_MRU']);
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009999',
            'tariff_category' => 'DS1D',
        ]);

        // 1. Agent visits site and records 7,000 kWh physical reading
        $service = app(ReadingGapReconciliationService::class);
        $service->recordPhysicalInspection($consumer, 7000, now(), 'Field trip visit', $user);

        // 2. Initial bill is at 5,000 kWh (gap is +2,000 kWh)
        $billMonth1 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009999',
            'billing_month' => 4,
            'billing_year' => 2026,
            'previous_reading' => '5000',
        ]);

        $recMonth1 = $service->calculateRecommendation($billMonth1, $consumer->fresh(), smartAvg: 100);
        $this->assertEquals('5220', $recMonth1['recommended_working_reading']);
        $this->assertEquals(120, $recMonth1['gap_slice_applied']);

        // 3. Month 2 arrives (no visit): Bill posted at 5,220 kWh
        $billMonth2 = BillRecord::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009999',
            'billing_month' => 5,
            'billing_year' => 2026,
            'previous_reading' => '5220',
        ]);

        $recMonth2 = $service->calculateRecommendation($billMonth2, $consumer->fresh(), smartAvg: 100);
        $this->assertEquals('5440', $recMonth2['recommended_working_reading']);
        $this->assertEquals(120, $recMonth2['gap_slice_applied']);
    }
}
```

- [x] **Step 2: Run the full test suite**

Run: `vendor/bin/phpunit.bat tests/Feature/FullReadingGapReconciliationFlowTest.php`  
Expected: PASS.

- [x] **Step 3: Run full project test suite**

Run: `vendor/bin/phpunit.bat`  
Expected: All tests pass with 0 regressions.

- [x] **Step 4: Final Pint Code Format & Commit**

```bash
vendor/bin/pint --format agent
git add .
git commit -m "feat(reconciliation): complete Dynamic Reading Gap & Event-Driven Engine"
```
