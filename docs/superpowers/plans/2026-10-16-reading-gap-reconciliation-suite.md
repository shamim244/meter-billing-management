# Enterprise Reading Gap & Reconciliation Suite Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build an enterprise-grade Reading Gap & Reconciliation Suite with strict security, a dedicated User Hub (`/reading-gap`), Admin Management & Analytics (`/admin/reading-gap`), and deep Card integration for NBPDCL billing agents.

**Architecture:** 
- **Core & Security**: Dedicated Form Requests (`RecordInspectionRequest`, `UpdateGapModeRequest`) with multi-tenant ownership enforcement, rate-limiting (`throttle:60,1`), dial rollover detection, and meter replacement events.
- **Dedicated User Hub (`/reading-gap`)**: Operator workspace displaying 4 KPI summary cards (Total Gap kWh, Over-Posted, Under-Posted, Stale >90d), filterable agenda table, 1-click inspection/hold actions, and route CSV export.
- **Admin Management Hub (`/admin/reading-gap`)**: Executive dashboard aggregating unbilled kWh and estimated revenue at risk (₹), MRU distribution, and system-wide inspection audit log.
- **Card & Modal Bridge**: Seamless linking between billing dashboard cards and the dedicated gap hub with visual recovery progress indicators.

**Tech Stack:** Laravel 12, PHP 8.4, Tailwind CSS v3, Alpine.js (decoupled `<script>` standard), SQLite / MySQL.

**Spec:** [PRD-13: Dynamic Reading Gap & Event-Driven Reconciliation Engine](file:///c:/Users/bccbo/Desktop/NBPDCL/tool/bill-downlod/php/laravel/.agents/docs/prd/13-Dynamic_Reading_Gap_and_Reconciliation_Engine_PRD.md)

---

## Global Constraints

- **REAL DATA PROTECTION**: User ID 9 (`shamim244d@gmail.com`) and all live consumer accounts/bills are strictly protected. Never mutate, truncate, delete, or test with live records. Use isolated factories and fake CAs (`9999...`).
- **View Modularity Standard (< 120 Lines Master Rule)**: No Blade view may exceed 120–150 lines. Decompose all pages into single-responsibility partials under `resources/views/<domain>/partials/`.
- **Zero Inline Script Bloat**: No raw JS blocks (> 25 lines) in Blade. Decouple into browser-cacheable files (`public/js/<domain>/<domain>-app.js`).
- **Security & Multi-Tenancy**: Regular agents can ONLY access their own consumers and MRUs. Authorization checks must enforce `$user->id === $consumer->user_id || $user->hasRole('admin')`.
- **TDD & Pint**: Write tests before implementing each step. Format modified PHP files using `vendor/bin/pint --dirty --format agent`.

---

## Review Focus

1. **Unauthorized Tenant Access**: An agent attempting to record an inspection or update gap mode for another agent's consumer receives a `403 Forbidden` response.
2. **Extreme / Negative Reading Inputs**: Input validation strictly rejects negative readings, non-numeric strings, or impossible dial jumps without confirmation.
3. **Dial Rollover (e.g. 99,950 $\rightarrow$ 00,080)**: Prevents an apparent -99,870 kWh false negative gap when a 5-digit meter rolls over 100,000.
4. **Meter Swap / Replacement Event**: Supports resetting the baseline when the department replaces a damaged/burned meter.
5. **N+1 Query Prevention**: Bulk aggregation of MRU gap statistics using keyed queries and set-based Eloquent statements.

---

### Task 1: Security Hardening & Dedicated Form Requests

**Files:**
- Create: `app/Http/Requests/RecordInspectionRequest.php`
- Create: `app/Http/Requests/UpdateGapModeRequest.php`
- Modify: `app/Http/Controllers/DashboardController.php:1810-1890`
- Modify: `routes/web.php:125-135`
- Test: `tests/Feature/ReadingGapSecurityTest.php`

**Interfaces:**
- Consumes: `ConsumerAccount`, `Auth::user()`.
- Produces: Authorized and sanitized input payloads with tenant isolation validation.

- [ ] **Step 1: Write the failing security feature test**

```php
// tests/Feature/ReadingGapSecurityTest.php
namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingGapSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_record_inspection_on_another_users_consumer(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $owner->id,
            'ca_number' => '99990007771',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($attacker)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990007771',
            'physical_reading' => '5500',
        ]);

        $response->assertStatus(403);
    }

    public function test_physical_reading_must_be_non_negative_numeric(): void
    {
        $user = User::factory()->create();
        $consumer = ConsumerAccount::create([
            'user_id' => $user->id,
            'ca_number' => '99990007772',
            'last_physical_reading' => '5000',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reading-gap/record-inspection', [
            'ca_number' => '99990007772',
            'physical_reading' => -100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['physical_reading']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/ReadingGapSecurityTest.php`  
Expected: FAIL (returns 404 or succeeds instead of 403 authorization denial).

- [ ] **Step 3: Implement `RecordInspectionRequest` and `UpdateGapModeRequest`**

1. Create `app/Http/Requests/RecordInspectionRequest.php`:
   - Checks `$consumer = ConsumerAccount::where('ca_number', $this->input('ca_number'))->first()`
   - `authorize()`: returns `$consumer && ($consumer->user_id === $this->user()->id || $this->user()->hasRole('admin'))`
   - Rules: `ca_number` => 'required|string', `physical_reading` => 'required|numeric|min:0|max:9999999', `notes` => 'nullable|string|max:255', `inspection_date` => 'nullable|date'.
2. Create `app/Http/Requests/UpdateGapModeRequest.php`:
   - Same `authorize()` logic.
   - Rules: `ca_number` => 'required|string', `gap_recovery_mode` => 'nullable|in:hold_exact,hold_advance,amortize_safe', `custom_safe_ceiling` => 'nullable|integer|min:50|max:5000'.
3. Inject both Form Requests into `DashboardController@recordInspection` and `DashboardController@updateGapMode`.
4. Add `throttle:60,1` middleware to `routes/web.php` for `api/reading-gap/*`.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/ReadingGapSecurityTest.php`  
Expected: PASS.

- [ ] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/ app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/ReadingGapSecurityTest.php
git commit -m "security(reading-gap): add tenant authorization form requests and rate limiting"
```

---

### Task 2: Service Layer Expansion (Analytics, Rollover & Meter Replacement)

**Files:**
- Modify: `app/Services/Reading/ReadingGapReconciliationService.php`
- Modify: `app/Models/MeterInspection.php`
- Test: `tests/Unit/ReadingGapAnalyticsServiceTest.php`

**Interfaces:**
- Consumes: `ConsumerAccount`, `BillRecord`, `MeterInspection`.
- Produces:
  - `detectMeterRollover(float|int $physical, float|int $posted, int $dialCapacity = 100000): array`
  - `recordMeterReplacement(ConsumerAccount $consumer, string|float $initialReading, ?Carbon $date, ?string $oldMeterFinalReading, ?string $newMeterNo, ?string $notes, ?User $user): MeterInspection`
  - `getMruGapAnalytics(?int $userId = null, ?int $mruId = null): array` (returns `total_unbilled_kwh`, `estimated_financial_gap_rs`, `over_posted_count`, `under_posted_count`, `stale_inspections_count`, `in_sync_count`).

- [ ] **Step 1: Write failing unit test for rollover and MRU gap analytics**

```php
// tests/Unit/ReadingGapAnalyticsServiceTest.php
namespace Tests\Unit;

use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use App\Services\Reading\ReadingGapReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingGapAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReadingGapReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReadingGapReconciliationService::class);
    }

    public function test_detects_dial_rollover_when_reading_wraps(): void
    {
        // Posted is 99950, Physical on wall is 50 -> net advancement is +100 kWh, NOT -99900!
        $rollover = $this->service->detectMeterRollover(50, 99950, 100000);
        $this->assertTrue($rollover['is_rollover']);
        $this->assertEquals(100, $rollover['adjusted_gap']);
    }

    public function test_calculates_aggregated_mru_gap_analytics(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_ANALYTICS_1', 'name' => 'Analytics MRU']);

        // Consumer 1: Under-posted (+500 gap)
        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990008881',
            'last_physical_reading' => '5500',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);
        // Consumer 2: Over-posted (-100 gap)
        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990008882',
            'last_physical_reading' => '1200',
            'last_inspected_at' => now(),
            'tariff_category' => 'DS1D',
        ]);

        $analytics = $this->service->getMruGapAnalytics($user->id, $mru->id);

        $this->assertArrayHasKey('total_unbilled_kwh', $analytics);
        $this->assertArrayHasKey('estimated_financial_gap_rs', $analytics);
        $this->assertArrayHasKey('over_posted_count', $analytics);
        $this->assertArrayHasKey('under_posted_count', $analytics);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Unit/ReadingGapAnalyticsServiceTest.php`  
Expected: FAIL.

- [ ] **Step 3: Implement rollover detection, meter replacement, and MRU analytics in `ReadingGapReconciliationService`**

1. Implement `detectMeterRollover($physical, $posted, $dialCapacity = 100000)`:
   - If `$posted > ($dialCapacity * 0.9)` and `$physical < ($dialCapacity * 0.1)`, calculate `adjustedGap = ($dialCapacity - $posted) + $physical`.
2. Implement `recordMeterReplacement($consumer, $initialReading, $date, $oldMeterFinalReading, $newMeterNo, $notes, $user)`:
   - Stores inspection with `notes` containing replacement metadata, updates `consumer_accounts.meter_no` and resets `last_physical_reading`.
3. Implement `getMruGapAnalytics($userId, $mruId)`:
   - Queries consumers in scope with their latest bill previous reading.
   - Computes total unbilled energy gap, over-posted units, average tariff multiplier (~₹6.5/kWh for DS-1D, ₹8.5 for NDS-1D), and status counts.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Unit/ReadingGapAnalyticsServiceTest.php`  
Expected: PASS.

- [ ] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Reading/ tests/Unit/ReadingGapAnalyticsServiceTest.php
git commit -m "feat(service): add dial rollover detection, meter replacement, and MRU gap analytics"
```

---

### Task 3: Dedicated User Reading Gap Hub (`/reading-gap`)

**Files:**
- Create: `app/Http/Controllers/ReadingGapController.php`
- Create: `resources/views/reading-gap/index.blade.php` (< 60 lines)
- Create: `resources/views/reading-gap/partials/kpi-cards.blade.php` (< 80 lines)
- Create: `resources/views/reading-gap/partials/filter-bar.blade.php` (< 60 lines)
- Create: `resources/views/reading-gap/partials/gap-table.blade.php` (< 110 lines)
- Create: `resources/views/reading-gap/partials/modals/meter-swap-modal.blade.php` (< 90 lines)
- Create: `public/js/reading-gap/reading-gap-app.js`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/navigation/partials/nav/operations-menu.blade.php`
- Modify: `resources/views/layouts/navigation/partials/mobile/operations-accordion.blade.php`
- Test: `tests/Feature/UserReadingGapWorkspaceTest.php`

**Interfaces:**
- Produces:
  - `GET /reading-gap`: Full responsive workspace with Tailwind dark mode and zero inline scripts.
  - `GET /reading-gap/data`: Paginated, filterable JSON feed with gap calculations and MRU analytics.
  - `GET /reading-gap/export-csv`: CSV download of route inspection sheet.
  - `POST /api/reading-gap/record-swap`: Endpoint to record a meter replacement event.

- [ ] **Step 1: Write the failing feature test for dedicated user hub**

```php
// tests/Feature/UserReadingGapWorkspaceTest.php
namespace Tests\Feature;

use App\Models\ConsumerAccount;
use App\Models\Mru;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReadingGapWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_dedicated_reading_gap_hub(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reading-gap.index'));
        $response->assertOk();
        $response->assertSee('Reading Gap & Reconciliation Hub');
    }

    public function test_user_can_fetch_filtered_gap_data(): void
    {
        $user = User::factory()->create();
        $mru = Mru::create(['user_id' => $user->id, 'code' => 'MRU_HUB_1', 'name' => 'Hub MRU']);
        ConsumerAccount::create([
            'user_id' => $user->id,
            'mru_id' => $mru->id,
            'ca_number' => '99990009991',
            'last_physical_reading' => '6000',
            'last_inspected_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/reading-gap/data?tab=all');
        $response->assertOk();
        $this->assertArrayHasKey('kpis', $response->json());
        $this->assertArrayHasKey('consumers', $response->json());
    }

    public function test_user_can_export_gap_audit_csv(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('reading-gap.export-csv'));
        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/UserReadingGapWorkspaceTest.php`  
Expected: FAIL.

- [ ] **Step 3: Implement `ReadingGapController`, Blade Partials (< 120 lines), and JS app**

1. Create `ReadingGapController`:
   - `index()`: renders `reading-gap.index` with MRUs list and current user config.
   - `getData(Request $request)`: returns KPIs and paginated consumers filtered by tab (`all`, `over_posted`, `under_posted`, `stale`, `uninspected`) and search.
   - `exportCsv(Request $request)`: streams clean CSV with UTF-8 BOM containing CA, Name, Tariff, Physical, Posted, Gap, Status, Last Visit Date.
   - `recordMeterSwap(Request $request)`: handles meter replacement event.
2. Build Blade partials strictly under 120 lines:
   - `resources/views/reading-gap/index.blade.php`: layout wrapper and config bridge.
   - `kpi-cards.blade.php`: 4 summary cards (Total Gap kWh, ₹ At Risk, Over-Posted Count, Overdue >90d).
   - `filter-bar.blade.php`: tab buttons, MRU dropdown, search input.
   - `gap-table.blade.php`: responsive table with progress bars and action buttons.
   - `meter-swap-modal.blade.php`: popup to record new meter number and initial reading.
3. Decouple logic in `public/js/reading-gap/reading-gap-app.js`.
4. Register routes in `routes/web.php` and add navigation link to Operations menu.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/UserReadingGapWorkspaceTest.php`  
Expected: PASS.

- [ ] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/ReadingGapController.php resources/views/reading-gap/ public/js/reading-gap/ routes/web.php resources/views/layouts/navigation/ tests/Feature/UserReadingGapWorkspaceTest.php
git commit -m "feat(hub): implement dedicated User Reading Gap and Field Inspection workspace"
```

---

### Task 4: Admin Management Hub & System-Wide Analytics (`/admin/reading-gap`)

**Files:**
- Create: `app/Http/Controllers/Admin/AdminReadingGapController.php`
- Create: `resources/views/admin/reading-gap/index.blade.php` (< 60 lines)
- Create: `resources/views/admin/reading-gap/partials/kpi-banner.blade.php` (< 80 lines)
- Create: `resources/views/admin/reading-gap/partials/mru-gap-table.blade.php` (< 90 lines)
- Create: `resources/views/admin/reading-gap/partials/inspections-log-table.blade.php` (< 110 lines)
- Create: `public/js/admin/admin-reading-gap-app.js`
- Modify: `routes/web.php:250-280`
- Modify: `resources/views/layouts/admin/partials/nav/operations.blade.php`
- Test: `tests/Feature/AdminReadingGapHubTest.php`

**Interfaces:**
- Produces:
  - `GET /admin/reading-gap`: System-wide executive dashboard for administrators.
  - `GET /admin/reading-gap/data`: System aggregates, MRU risk ranking, and audit log.
  - `GET /admin/reading-gap/export-audit`: Complete inspection audit trail download.

- [ ] **Step 1: Write the failing feature test for admin hub**

```php
// tests/Feature/AdminReadingGapHubTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminReadingGapHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_system_wide_reading_gap_hub(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reading-gap.index'));
        $response->assertOk();
        $response->assertSee('System-Wide Reading Gap & Unbilled Energy Intelligence');
    }

    public function test_regular_agent_cannot_access_admin_reading_gap_hub(): void
    {
        $agent = User::factory()->create();
        $response = $this->actingAs($agent)->get(route('admin.reading-gap.index'));
        $response->assertStatus(403);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/AdminReadingGapHubTest.php`  
Expected: FAIL.

- [ ] **Step 3: Implement `AdminReadingGapController`, Blade partials, and routes**

1. Create `AdminReadingGapController`:
   - Enforces `role:admin` middleware.
   - `index()`: renders `admin.reading-gap.index`.
   - `getData()`: aggregates cross-tenant stats across all MRUs and recent inspection audit logs.
   - `exportAudit()`: streams all meter inspections with agent names and timestamps.
2. Build Blade partials (< 120 lines):
   - `kpi-banner.blade.php`: System-wide Total Unbilled Energy (MWh), Revenue at Risk (₹ Lakhs), and Over-Posted Float.
   - `mru-gap-table.blade.php`: MRUs ranked by unbilled gap severity.
   - `inspections-log-table.blade.php`: chronological feed of inspections across all agents.
3. Decouple logic into `public/js/admin/admin-reading-gap-app.js`.
4. Add link to Admin Sidebar under Operations pillar (`resources/views/layouts/admin/partials/nav/operations.blade.php`).

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/AdminReadingGapHubTest.php`  
Expected: PASS.

- [ ] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/AdminReadingGapController.php resources/views/admin/reading-gap/ public/js/admin/ routes/web.php resources/views/layouts/admin/ tests/Feature/AdminReadingGapHubTest.php
git commit -m "feat(admin): implement System-Wide Reading Gap & Unbilled Energy Management Hub"
```

---

### Task 5: Billing Card Integration Polish & Deep Linking

**Files:**
- Modify: `resources/views/dashboard/partials/card/reading-gap-badge.blade.php`
- Modify: `resources/views/dashboard/partials/card/boxes/box1-working.blade.php`
- Modify: `public/js/dashboard/dashboard-app.js`
- Test: `tests/Feature/DashboardReadingGapIntegrationTest.php`

**Interfaces:**
- Produces:
  - Deep-link button `[↗ Hub]` from card badge directly to `/reading-gap?ca={ca_number}`.
  - Mini recovery progress bar inside Box 1 when under-posted gap is being amortized:
    `[████████░░░░] 60% (Remaining: 400 kWh)`.
  - Auto-fill synchronization when user clicks safe slice.

- [ ] **Step 1: Write integration test for card deep-link and progress bar**

```php
// tests/Feature/DashboardReadingGapIntegrationTest.php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardReadingGapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_hub_deep_link_and_progress_attributes(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('reading-gap?ca=');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardReadingGapIntegrationTest.php`  
Expected: FAIL.

- [ ] **Step 3: Update `reading-gap-badge.blade.php` and `box1-working.blade.php`**

1. In `reading-gap-badge.blade.php`: add a discreet link `[↗ Hub]` pointing to `/reading-gap?ca={ca_number}`.
2. In `box1-working.blade.php`: add an ultra-compact visual progress bar under the working reading input when `bill.reading_gap_info?.gap_status === 'under_posted'`.
3. In `dashboard-app.js`: update `applySafeRecovery` to recalculate progress live without reloading page.

- [ ] **Step 4: Run test to verify it passes**

Run: `vendor/bin/phpunit.bat tests/Feature/DashboardReadingGapIntegrationTest.php`  
Expected: PASS.

- [ ] **Step 5: Run Pint and Commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/dashboard/ public/js/dashboard/ tests/Feature/DashboardReadingGapIntegrationTest.php
git commit -m "feat(ui): add gap hub deep-linking and visual recovery progress bar on billing cards"
```

---

### Task 6: Full Verification, Security Penetration Checks & Regression Suite

**Files:**
- Test: All tests under `tests/Feature/` and `tests/Unit/`
- Command: `vendor/bin/pint --dirty --format agent`

- [ ] **Step 1: Run complete Reading Gap test suite**

Run: `vendor/bin/phpunit.bat --filter=ReadingGap`  
Expected: 100% PASS with zero failures.

- [ ] **Step 2: Run Security Penetration Test**

Run: `vendor/bin/phpunit.bat tests/Feature/ReadingGapSecurityTest.php`  
Expected: PASS (Tenant isolation, 403 checks, rate limits verified).

- [ ] **Step 3: Verify View Modularity Standard (< 120 lines)**

Run PowerShell check:
```powershell
Get-ChildItem -Path resources/views/reading-gap, resources/views/admin/reading-gap -Recurse -Filter *.blade.php | ForEach-Object { "$($_.Name): $((Get-Content $_.FullName).Count) lines" }
```
Expected: All files under 120 lines.

- [ ] **Step 4: Run Pint Code Formatter**

```bash
vendor/bin/pint --dirty --format agent
```

---

## Plan Self-Review Checklist

1. **Spec Coverage**:
   - Core & Database: Covered in Tasks 1 & 2.
   - Admin Management & See: Covered in Task 4 (`/admin/reading-gap`).
   - Dedicated Page for User: Covered in Task 3 (`/reading-gap`).
   - Card Popup & Link: Covered in Task 5.
   - Security (Most Important): Covered in Task 1 (`RecordInspectionRequest`, `UpdateGapModeRequest`, tenant checks, rate-limiting).
2. **Step Scan**: Every step has unambiguous actions and verification commands.
3. **Type Consistency**: Form Requests and Service method names match throughout all tasks.
4. **Modularity & Decoupling**: All new Blade templates broken into partials under 120 lines; all JS extracted to `public/js/`.
