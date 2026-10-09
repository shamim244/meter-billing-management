# PRD-12: Main Billing Dashboard Architecture Modularization & CSS Optimization

**Product:** NBPDCL SaaS Electricity Billing & Field Automation Platform  
**Module:** Dashboard Architecture Optimization (Module 12)  
**Version:** 1.0.0 (Enterprise Refactor)  
**Status:** Approved for Implementation  
**Serial Number:** PRD-12  
**Target:** `resources/views/dashboard.blade.php`, `resources/views/dashboard/partials/`, `public/css/dashboard/`, `public/js/dashboard/`  

---

## 1. Problem Statement & Executive Summary

The main billing dashboard (`resources/views/dashboard.blade.php`) currently spans **5,406 lines** and **405 KB** in a single file. 

### Critical Friction Points
1. **Severe Token Burn & AI Latency**: Every development iteration burns tens of thousands of LLM tokens simply reading through unedited sections, causing response lag and context exhaustion.
2. **Tailwind Utility Class Bloat**: More than 60% of the HTML consists of repetitive 15–25 class strings on every tag, obscuring business logic.
3. **Monolithic Script Block**: 2,800+ lines of client-side Alpine.js logic are embedded inline inside `<script>` tags, preventing browser HTTP caching and forcing complete script re-parsing on every page load.
4. **Maintenance Fragility**: 10+ distinct modals, table view, card grid view, and filters all reside in one file, increasing the risk of accidental markup errors.

---

## 2. Goals & Success Criteria

1. **Dramatic Code Reduction**:
   - Master `dashboard.blade.php` reduced from **5,406 lines to under 120 lines**.
   - Subcomponents separated into isolated, readable partials of 60–350 lines each.
2. **CSS Extraction & Categorization**:
   - Repetitive Tailwind class chains extracted into a dedicated, categorized semantic stylesheet directory: `public/css/dashboard/`.
   - HTML markup token size reduced by **50% to 70%**.
3. **JavaScript Decoupling with Browser Caching**:
   - Client-side Alpine.js methods extracted to `public/js/dashboard/dashboard-app.js`.
   - Dynamic server parameters cleanly injected via `window.dashboardConfig`.
   - Browser permanently caches the JS file with version hashing (`?v={timestamp}`).
4. **Zero Downtime & Zero Regression**:
   - 100% feature preservation: all shortcuts, swipe gestures, offline sync queue (`nbpdcl_offline_queue_v1`), working reading calculation, heatmaps, and FieldDesk integration must work identically.
   - 100% pass rate across all automated test suites.
   - Zero risk to User ID 9 (`shamim244d@gmail.com`) or real database records.

---

## 3. Scope of Decomposition

```
resources/views/
├── dashboard.blade.php                         <-- Master Orchestrator (Under 120 lines)
└── dashboard/
    └── partials/
        ├── stats-header.blade.php              <-- Header, KPI Ribbon, MRU & Billing Month selectors, Filters
        ├── table-view.blade.php                <-- Tabular Bill List View
        ├── card-view.blade.php                 <-- Card Grid View (Box 1/2/3 readings, Mobile gestures)
        └── modals/
            ├── create-mru-modal.blade.php      <-- Modal 1: Create MRU Workspace
            ├── billing-cycle-modal.blade.php   <-- Modal 2: Billing Cycle & MRU Conflict
            ├── pdf-viewer-modal.blade.php      <-- Modal 3: Sequential In-App PDF Viewer
            ├── quick-pull-modal.blade.php      <-- Modal 4: Single CA Quick Pull
            ├── shortcuts-modal.blade.php       <-- Modal 5: Interactive Keyboard Shortcuts Cheatsheet & Rebind
            ├── tuning-modal.blade.php          <-- Modal 6: Smart Average Units Tuning
            ├── meter-history-modal.blade.php   <-- Modal 7: Historical Meter Readings Popup
            ├── mobile-modal.blade.php          <-- Modal 8: Consumer Mobile Edit Modal
            ├── bulk-mobile-modal.blade.php     <-- Modal 9: Bulk Mobile Numbers Import
            └── field-desk-modal.blade.php      <-- Modal 10: FieldDesk Tier 2 Quick Bridge

public/css/dashboard/
├── dashboard.css                               <-- Master Dashboard Stylesheet (Browser Cached)
├── layout.css                                  <-- Container grids & sticky action ribbons
├── cards.css                                   <-- .bill-card, .card-meta, .card-footer
├── reading-boxes.css                           <-- .reading-box-prev, .reading-box-work, .reading-box-pdf
├── badges.css                                  <-- .badge-status, .badge-basis, .badge-map, .badge-desk
├── modals.css                                  <-- .modal-overlay, .modal-dialog, .modal-header, .modal-footer
└── tables.css                                  <-- .bill-table, .bill-row-active, .bill-cell

public/js/dashboard/
└── dashboard-app.js                            <-- Full Alpine Application Object (Browser Cached)
```

---

## 4. Non-Functional Requirements & Invariants

1. **Hostinger Shared Hosting Compatibility**: Pure static CSS/JS files served directly via Apache/Nginx web server without requiring a background Node.js server.
2. **Mobile Bandwidth Efficiency**: Smaller HTML payload (< 70 KB instead of 405 KB) speeds up first contentful paint (FCP) for meter readers on 4G in Bihar.
3. **Data Safety**: Absolutely no modifications to database tables, models, or backend controllers.
