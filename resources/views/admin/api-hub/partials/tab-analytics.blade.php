<!-- ========================================== -->
<!-- TAB 1: TRAFFIC ANALYTICS                   -->
<!-- ========================================== -->
<div x-show="activeTab === 'analytics'" class="space-y-6" x-cloak>
    @include('admin.api-hub.partials.analytics.timeline-chart')
    @include('admin.api-hub.partials.analytics.breakdown-cards')
    @include('admin.api-hub.partials.analytics.recent-logs-table')
</div>
