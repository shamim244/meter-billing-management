{{-- Dashboard Stats, Header & Search Controls Hub --}}

<!-- 1. Top Header & Action Bar -->
@include('dashboard.partials.header.top-bar')

<!-- 2. Offline & Failed Sync Drawer -->
@include('dashboard.partials.header.offline-banner')

<!-- 3. Flash Alerts & Subscription Banner -->
@include('dashboard.partials.header.alerts-banner')

<!-- 4. Workspace Selection & Billing Period Bar -->
@include('dashboard.partials.header.workspace-bar')

<!-- 5. Top KPI Cards Row -->
@include('dashboard.partials.header.kpi-cards')

<!-- 6. Filter Pills, Search Bar & Sorting Controls -->
@include('dashboard.partials.header.filter-controls')

<!-- 7. Loading Indicator & Empty State -->
@include('dashboard.partials.header.empty-state')
