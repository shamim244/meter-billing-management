<!-- ========================================== -->
<!-- TAB 3: RATE LIMITING & THROTTLING TIERS   -->
<!-- ========================================== -->
<div x-show="activeTab === 'ratelimits'" class="space-y-6" x-cloak>
    @include('admin.api-hub.partials.ratelimits.master-switch')
    @include('admin.api-hub.partials.ratelimits.tiers-grid')
    @include('admin.api-hub.partials.ratelimits.save-bar')
</div>
