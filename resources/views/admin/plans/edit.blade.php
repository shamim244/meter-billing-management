<x-admin-layout>
    <div class="space-y-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="adminPlanFormApp(window.adminPlanConfig)">
        @include('admin.plans.partials.form.edit-header')
        @include('admin.plans.partials.form.alerts')

        <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('admin.plans.partials.form.basic-info')
            @include('admin.plans.partials.form.rates')
            @include('admin.plans.partials.form.durations-table')
            @include('admin.plans.partials.form.submit-bar')
        </form>
    </div>

    <!-- Script Configuration Bridge & Decoupled Script -->
    <script>
        window.adminPlanConfig = {
            basePrice: {{ (float) ($plan->base_price ?: 499) }},
            durations: {{ Js::from($plan->durations->map(fn($d) => [
                'id' => $d->id,
                'unit' => $d->duration_unit ?: 'month',
                'value' => (int) ($d->duration_value ?: $d->duration_months ?: 1),
                'name' => (string) ($d->name ?? ''),
                'discount' => (float) $d->discount_percent,
                'price' => (float) $d->final_price,
                'extraMru' => $d->extra_mru_rate !== null ? (float) $d->extra_mru_rate : '',
                'extraConsumer' => $d->extra_consumer_rate !== null ? (float) $d->extra_consumer_rate : '',
                'is_active' => (bool) $d->is_active,
            ])) }}
        };
    </script>
    <script src="{{ asset('js/admin/plans/plan-form-app.js') }}?v={{ file_exists(public_path('js/admin/plans/plan-form-app.js')) ? filemtime(public_path('js/admin/plans/plan-form-app.js')) : time() }}"></script>
</x-admin-layout>
