<x-admin-layout>
    <div class="space-y-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="adminPlanFormApp(window.adminPlanConfig)">
        @include('admin.plans.partials.form.create-header')
        @include('admin.plans.partials.form.alerts')

        <form method="POST" action="{{ route('admin.plans.store') }}" class="space-y-6">
            @csrf

            @include('admin.plans.partials.form.basic-info')
            @include('admin.plans.partials.form.rates')
            @include('admin.plans.partials.form.durations-table')
            @include('admin.plans.partials.form.submit-bar')
        </form>
    </div>

    <!-- Script Configuration Bridge & Decoupled Script -->
    <script>
        window.adminPlanConfig = {
            basePrice: 499,
            durations: [
                { unit: 'month', value: 1, discount: 0, price: 499, name: '', extraMru: '', extraConsumer: '', is_active: true },
                { unit: 'month', value: 2, discount: 5, price: 948, name: '', extraMru: '', extraConsumer: '', is_active: true },
                { unit: 'month', value: 3, discount: 10, price: 1347, name: '', extraMru: '', extraConsumer: '', is_active: true },
                { unit: 'month', value: 6, discount: 15, price: 2545, name: '', extraMru: '', extraConsumer: '', is_active: true },
                { unit: 'month', value: 12, discount: 20, price: 4790, name: '', extraMru: '', extraConsumer: '', is_active: true }
            ]
        };
    </script>
    <script src="{{ asset('js/admin/plans/plan-form-app.js') }}?v={{ file_exists(public_path('js/admin/plans/plan-form-app.js')) ? filemtime(public_path('js/admin/plans/plan-form-app.js')) : time() }}"></script>
</x-admin-layout>
