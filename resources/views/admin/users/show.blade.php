<x-admin-layout>
    <x-slot name="header">
        User 360° Dossier — {{ $user->name }}
    </x-slot>

    <div class="space-y-6" x-data="userDossierApp(window.userDossierConfig)">
        @include('admin.users.partials.toolbar')
        @include('admin.users.partials.hero-identity-card')
        @include('admin.users.partials.kpi-summary-cards')

        <!-- Two Column Main Body -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                @include('admin.users.partials.mru-workspaces-table')
                @include('admin.users.partials.billing-activity-card')
            </div>
            <div class="space-y-6">
                @include('admin.users.partials.recent-wallet-activity')
                @include('admin.users.partials.plan-transitions-card')
            </div>
        </div>

        <!-- Modals -->
        @include('admin.users.partials.modals.grant-plan-modal')
        @include('admin.users.partials.modals.override-quotas-modal')
        @include('admin.users.partials.modals.direct-notification-modal')
        @include('admin.users.partials.modals.cleanup-storage-modal')
    </div>

    <script>
        window.userDossierConfig = {
            mruIds: {{ json_encode($mrus->pluck('id')->toArray()) }},
            defaultPlanId: '{{ $availablePlans->first()?->id ?? '' }}',
            defaultMruId: '{{ $mrus->first()?->id ?? '' }}',
            currentMonth: '{{ now()->month }}',
            currentYear: '{{ now()->year }}'
        };
    </script>
    <script src="{{ asset('js/admin/user-dossier-app.js') }}?v={{ file_exists(public_path('js/admin/user-dossier-app.js')) ? filemtime(public_path('js/admin/user-dossier-app.js')) : time() }}"></script>
</x-admin-layout>
