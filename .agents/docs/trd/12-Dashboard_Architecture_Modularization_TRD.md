# TRD-12: Technical Architecture & Implementation Blueprint — Dashboard Modularization

**Project:** NBPDCL SaaS Meter Billing & Field Automation Platform  
**Module:** Dashboard Architecture Optimization (Module 12)  
**Version:** 1.0.0 (Enterprise Refactor Blueprint)  
**Status:** Approved for Implementation  
**Serial Number:** TRD-12  
**Associated PRD:** `.agents/docs/prd/12-Dashboard_Architecture_Modularization_PRD.md`  

---

## 1. System Architecture Before & After

```
BEFORE (Monolithic):
dashboard.blade.php (5,406 lines / 405 KB)
├── Inline HTML Header & KPI Ribbon (~450 lines)
├── Inline Table View (~350 lines)
├── Inline Card View (~700 lines)
├── 10 Inline Modals (~950 lines)
└── Embedded <script> Tag (~2,800 lines of Alpine.js)
    [Zero Browser Caching • 20K-50K Tokens Burned per Prompt • Class Bloat]

AFTER (Modular Enterprise):
resources/views/dashboard.blade.php (~100 lines)
├── @include('dashboard.partials.stats-header')
├── @include('dashboard.partials.table-view')
├── @include('dashboard.partials.card-view')
├── @include('dashboard.partials.modals.*') (10 isolated partial files)
├── <link rel="stylesheet" href="/css/dashboard/dashboard.css?v=..."> (Cached)
└── <script src="/js/dashboard/dashboard-app.js?v=..."> (Cached)
```

---

## 2. Server-to-Client Config Contract (`window.dashboardConfig`)

To cleanly decouple the JavaScript logic from Blade PHP directives, `dashboard.blade.php` will initialize a single immutable configuration object before loading `dashboard-app.js`:

```html
<script>
    window.dashboardConfig = {
        csrfToken: '{{ csrf_token() }}',
        selectedMruId: '{{ $selectedMruId }}',
        selectedMonth: {{ $selectedMonth }},
        selectedYear: {{ $selectedYear }},
        mruPeriodsMap: @json($mruPeriodsMap ?? []),
        activeTags: @json($activeTags ?? []),
        defaultTag: '{{ $defaultTag ?? "OK" }}',
        statusCounts: @json($statusCounts ?? []),
        totalPeriodBills: {{ $totalPeriodBills ?? 0 }},
        totalPeriodUnits: {{ $totalPeriodUnits ?? 0 }},
        totalPeriodAmount: {{ $totalPeriodAmount ?? 0 }},
        totalConsumers: {{ $totalConsumers ?? 0 }},
        fieldDeskCategories: @json($fieldDeskCategories ?? []),
        initialCa: @json($initialCa ?? '')
    };
</script>
```

`dashboard-app.js` will read from `window.dashboardConfig` without relying on server-side Blade template evaluation inside `.js` files.

---

## 3. Categorized Semantic CSS Specification

Create `public/css/dashboard/` with the following modular files:

| File | Purpose | Key Semantic Classes |
| :--- | :--- | :--- |
| `layout.css` | Containers, sticky controls, swipe viewport | `.dashboard-container`, `.sticky-filter-ribbon`, `.swipe-viewport` |
| `cards.css` | Card dimensions, border highlights, elevation | `.bill-card`, `.bill-card-header`, `.bill-card-footer`, `.card-drag-zone` |
| `reading-boxes.css` | Prev, Working, and Official PDF reading containers | `.box-previous`, `.box-working`, `.box-official` |
| `badges.css` | Status, billing basis, GPS map, and FieldDesk pills | `.badge-status`, `.badge-basis`, `.badge-gps-map`, `.badge-desk` |
| `modals.css` | Modal backdrops, glassmorphic panels, header/footers | `.modal-backdrop-blur`, `.modal-panel-elevated`, `.modal-action-btn` |
| `tables.css` | Tabular headers, row striping, active selection | `.bill-table-wrapper`, `.bill-row-item`, `.bill-col-mono` |
| `dashboard.css` | Master stylesheet importing all partials | `@import './layout.css';` etc. |

---

## 4. Phased Execution Roadmap

### Phase 1: CSS Categorization & Extraction
1. Create `public/css/dashboard/` and build `layout.css`, `cards.css`, `reading-boxes.css`, `badges.css`, `modals.css`, `tables.css`, and `dashboard.css`.
2. Link the versioned stylesheet in `dashboard.blade.php`.

### Phase 2: Modals Decomposition
1. Create `resources/views/dashboard/partials/modals/`.
2. Extract all 10 modals into self-contained partials.
3. Test modal visibility toggles.

### Phase 3: Core Views Decomposition
1. Create `resources/views/dashboard/partials/stats-header.blade.php`.
2. Create `resources/views/dashboard/partials/table-view.blade.php`.
3. Create `resources/views/dashboard/partials/card-view.blade.php`.
4. Re-link inside `dashboard.blade.php`.

### Phase 4: JavaScript Decoupling & Browser Caching
1. Create `public/js/dashboard/dashboard-app.js`.
2. Move `function dashboardApp()` from `dashboard.blade.php` to `dashboard-app.js`.
3. Inject `window.dashboardConfig` in `dashboard.blade.php`.
4. Add version query parameter for instant cache busting (`?v={{ filemtime(...) }}`).

### Phase 5: Verification & Safety Protocol
1. Run `vendor/bin/phpunit tests/Feature/FieldDeskSystemTest.php tests/Feature/UserDashboardTest.php`.
2. Verify table view, card view, keyboard navigation, and modals function identically.
3. Run `vendor/bin/pint --format agent`.
