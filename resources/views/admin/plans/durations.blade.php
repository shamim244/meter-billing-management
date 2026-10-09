<x-admin-layout>
    <div x-data="planDurationsManager(window.planDurationsConfig.durations, window.planDurationsConfig.basePrice)" class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @include('admin.plans.partials.durations.header')
        @include('admin.plans.partials.durations.alerts')
        @include('admin.plans.partials.durations.summary-strip')
        @include('admin.plans.partials.durations.table')
        @include('admin.plans.partials.durations.modal-add')
        @include('admin.plans.partials.durations.modal-edit')
    </div>

    <!-- Server Configuration Bridge & App Script -->
    <script>
        window.planDurationsConfig = {
            durations: {{ json_encode($plan->durations) }},
            basePrice: {{ (float) $plan->base_price }}
        };
    </script>
    <script src="{{ asset('js/admin/plans/plan-durations-app.js') }}?v={{ file_exists(public_path('js/admin/plans/plan-durations-app.js')) ? filemtime(public_path('js/admin/plans/plan-durations-app.js')) : time() }}"></script>
</x-admin-layout>
