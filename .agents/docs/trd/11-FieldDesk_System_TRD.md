# Technical Requirements & Architecture Document (TRD) — Module 11
**Project Name:** NBPDCL SaaS Electricity Billing & Field Automation Platform  
**Module:** FieldDesk (Consumer Actions, Grievance Tracking & Field Follow-Up Service)  
**Document Version:** 1.0.0 (Production Blueprint)  
**Status:** Approved for Implementation  
**Serial Number:** TRD-11  
**Location:** `.agents/docs/trd/11-FieldDesk_System_TRD.md`  
**Associated PRD:** `.agents/docs/prd/11-FieldDesk_System_PRD.md`  
**Route:** `/field-desk`  

---

## 1. System Architecture & Component Mapping

```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                       USER INTERFACES                                       │
│  ┌──────────────────────────────────────────────┐  ┌─────────────────────────────────────┐  │
│  │ Tier 1: Dedicated Hub (/field-desk)          │  │ Tier 2: Main Dashboard Bridge       │  │
│  │ (Full agenda, counters, filters, timeline)   │  │ (Card badge + Quick Pop-up Modal)   │  │
│  └──────────────────────┬───────────────────────┘  └──────────────────┬──────────────────┘  │
└─────────────────────────┼─────────────────────────────────────────────┼─────────────────────┘
                          │ HTTP Requests (Session / CSRF)              │ Fetch JSON Requests
                          ▼                                             ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                      CONTROLLER LAYER                                       │
│  ┌───────────────────────────────────────────────────────────────────────────────────────┐  │
│  │ FieldDeskController:                                                                  │  │
│  │ • index(): Renders Dedicated FieldDesk view                                           │  │
│  │ • getData(Request): Filtered paginated JSON feed for Alpine.js                        │  │
│  │ • store(Request): Creates new FieldDesk action + logs timeline                        │  │
│  │ • update(Request, id): Edits existing action item                                     │  │
│  │ • quickReschedule(Request, id): Pushes date forward (+2d, +5d, custom)                │  │
│  │ • complete(Request, id): Marks action resolved/paid                                   │  │
│  │ • logActivity(Request, id): Logs interaction (Call, WhatsApp, note)                   │  │
│  │ • forConsumer(Request, ca): Lightweight endpoint for Main Dashboard Quick Pop-up      │  │
│  │ • getCategories(): Returns active dynamic categories for dropdowns                    │  │
│  └──────────────────────────────────────────┬────────────────────────────────────────────┘  │
└─────────────────────────────────────────────┼───────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                       SERVICE LAYER                                         │
│  ┌───────────────────────────────────────────────────────────────────────────────────────┐  │
│  │ FieldDeskService:                                                                     │  │
│  │ • getFilteredAgenda(): Applies 'today', 'overdue', 'upcoming', MRU & category filters  │  │
│  │ • getSummaryCounts(): Fast COUNT(*) aggregates for Due Today, Overdue, Upcoming       │  │
│  │ • generateWhatsAppLink(): Formats sanitized mobile & urlencoded message template      │  │
│  │ • quickReschedule(): Updates date, increments counter & logs timeline touch           │  │
│  │ • completeAction(): Records collected amount & resolution note                        │  │
│  └──────────────────────────────────────────┬────────────────────────────────────────────┘  │
└─────────────────────────────────────────────┼───────────────────────────────────────────────┘
                                              │
                                              ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                                  DATA ACCESS & PERSISTENCE                                  │
│  ┌───────────────────────────┐  ┌─────────────────────────────┐  ┌───────────────────────┐  │
│  │ FieldDeskAction (Model)   │  │ FieldDeskCategory (Model)   │  │ FieldDeskActivity(Mod)│  │
│  └─────────────┬─────────────┘  └──────────────┬──────────────┘  └───────────┬───────────┘  │
│                │                               │                             │              │
│                ▼                               ▼                             ▼              │
│  ┌───────────────────────────────────────────────────────────────────────────────────────┐  │
│  │ MySQL Database Tables: field_desk_actions | field_desk_categories | field_desk_activities│
│  │ Foreign Keys / Relations: users(id) | consumer_accounts(id) | mrus(id)                │  │
│  └───────────────────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Database Migrations Specifications

### 2.1 Table: `field_desk_categories`
```php
Schema::create('field_desk_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('code', 50)->unique();
    $table->string('icon', 50)->default('📋');
    $table->string('color', 30)->default('#10b981');
    $table->string('description', 255)->nullable();
    $table->boolean('is_system')->default(true);
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();

    $table->index('is_active');
    $table->index('sort_order');
});
```

### 2.2 Table: `field_desk_actions`
```php
Schema::create('field_desk_actions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('ca_number', 50);
    $table->foreignId('consumer_account_id')->nullable()->constrained('consumer_accounts')->nullOnDelete();
    $table->foreignId('category_id')->constrained('field_desk_categories')->restrictOnDelete();
    $table->enum('priority', ['urgent', 'high', 'normal', 'low'])->default('normal');
    $table->date('target_date');
    $table->date('original_target_date');
    $table->decimal('target_amount', 12, 2)->nullable();
    $table->decimal('collected_amount', 12, 2)->default(0.00);
    $table->string('payment_mode', 50)->nullable();
    $table->text('private_note')->nullable();
    $table->enum('status', ['open', 'completed', 'rescheduled', 'cancelled'])->default('open');
    $table->unsignedInteger('reschedule_count')->default(0);
    $table->unsignedSmallInteger('billing_month')->nullable();
    $table->unsignedSmallInteger('billing_year')->nullable();
    $table->foreignId('mru_id')->nullable()->constrained('mrus')->nullOnDelete();
    $table->timestamp('resolved_at')->nullable();
    $table->string('resolution_note', 255)->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status', 'target_date'], 'idx_user_status_date');
    $table->index(['user_id', 'ca_number'], 'idx_user_ca');
    $table->index(['category_id', 'status'], 'idx_cat_status');
    $table->index('mru_id');
});
```

### 2.3 Table: `field_desk_activities`
```php
Schema::create('field_desk_activities', function (Blueprint $table) {
    $table->id();
    $table->foreignId('action_id')->constrained('field_desk_actions')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('action_type', 50);
    $table->text('note')->nullable();
    $table->date('old_date')->nullable();
    $table->date('new_date')->nullable();
    $table->decimal('amount_recorded', 12, 2)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamp('created_at')->useCurrent();

    $table->index(['action_id', 'created_at'], 'idx_action_timeline');
    $table->index('action_type');
});
```

---

## 3. Eloquent Models

- **`FieldDeskCategory`**: `app/Models/FieldDeskCategory.php`
- **`FieldDeskAction`**: `app/Models/FieldDeskAction.php`
- **`FieldDeskActivity`**: `app/Models/FieldDeskActivity.php`

---

## 4. Web Routes & API Endpoints

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Dedicated Workspace
    Route::get('/field-desk', [FieldDeskController::class, 'index'])->name('field-desk.index');
    
    // Asynchronous JSON API for FieldDesk
    Route::prefix('api/field-desk')->name('api.field-desk.')->group(function () {
        Route::get('/data', [FieldDeskController::class, 'getData'])->name('data');
        Route::post('/actions', [FieldDeskController::class, 'store'])->name('store');
        Route::get('/actions/{id}', [FieldDeskController::class, 'show'])->name('show');
        Route::put('/actions/{id}', [FieldDeskController::class, 'update'])->name('update');
        Route::delete('/actions/{id}', [FieldDeskController::class, 'destroy'])->name('destroy');
        Route::post('/actions/{id}/reschedule', [FieldDeskController::class, 'quickReschedule'])->name('reschedule');
        Route::post('/actions/{id}/complete', [FieldDeskController::class, 'complete'])->name('complete');
        Route::post('/actions/{id}/activity', [FieldDeskController::class, 'logActivity'])->name('activity');
        Route::get('/consumer/{ca}', [FieldDeskController::class, 'forConsumer'])->name('consumer');
        Route::post('/consumer/{ca}/contact', [FieldDeskController::class, 'updateConsumerContact'])->name('consumer.contact');
        Route::get('/categories', [FieldDeskController::class, 'getCategories'])->name('categories');
    });
});
```

---

## 5. Geolocation Schema & Technical Specification

### 5.1 Geolocation Migration: `2026_10_09_000001_add_coordinates_to_consumer_accounts_and_field_desk_actions_table.php`
```php
Schema::table('consumer_accounts', function (Blueprint $table) {
    $table->decimal('latitude', 10, 8)->nullable()->after('mobile');
    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
    $table->float('location_accuracy')->nullable()->after('longitude');
    $table->timestamp('location_updated_at')->nullable()->after('location_accuracy');
});

Schema::table('field_desk_actions', function (Blueprint $table) {
    $table->decimal('latitude', 10, 8)->nullable()->after('private_note');
    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
    $table->float('location_accuracy')->nullable()->after('longitude');
});
```

### 5.2 Accessor Methods
* `ConsumerAccount::getMapLinkAttribute()`: `https://www.google.com/maps?q={latitude},{longitude}`
* `FieldDeskAction::getMapLinkAttribute()`: Resolves action coordinates or falls back to consumer account coordinates.

---

## 6. Phase 1 Verification & Status

* **Status:** Verified & Complete in production codebase.
* **Test Suite:** `tests/Feature/FieldDeskSystemTest.php` (20 tests passed, 108 assertions).
* **Full Suite Run:** 32 tests passed (including `UserDashboardTest.php`).
* **Pint Formatted:** 100% compliant.

---
*End of TRD-11 Specification. Branded as FieldDesk.*

