<!-- ========================================== -->
<!-- TAB 2: FEATURE TOGGLES & ENDPOINT SWITCHES -->
<!-- ========================================== -->
<div x-show="activeTab === 'features'" class="space-y-6" x-cloak>
    @include('admin.api-hub.partials.features.kill-switch')
    @include('admin.api-hub.partials.features.capabilities-grid')
    @include('admin.api-hub.partials.features.save-bar')
</div>
