<x-app-layout>
    <x-slot name="header">
        @include('consumers.partials.header')
    </x-slot>

    <div class="py-6" x-data="consumerDetailWorkspace()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @include('consumers.partials.kpi-cards')
            @include('consumers.partials.tabs-nav')

            <!-- Tab Panels -->
            @include('consumers.partials.overview-tab')
            @include('consumers.partials.gap-tab')
            @include('consumers.partials.fielddesk-tab')
            @include('consumers.partials.bills-tab')
            @include('consumers.partials.matrix-tab')
        </div>

        <!-- Modals -->
        @include('consumers.partials.edit-profile-modal')
        @include('reading-gap.partials.modals.inspection-modal')
        @include('reading-gap.partials.modals.meter-swap-modal')
    </div>

    @push('scripts')
        <script>
            window.consumerConfig = {
                caNumber: "{{ $account->ca_number }}",
                consumerName: "{{ $account->consumer_name ?? '' }}",
                meterNo: "{{ $account->meter_no ?? '' }}",
                defaultTab: "{{ $defaultTab }}",
                updateUrl: "{{ route('consumers.update', $account->ca_number) }}",
                quickPullUrl: "{{ url('api/v1/bills/quick-pull') }}",
                inspectionsUrl: "{{ url('api/reading-gap/inspections') }}",
                recordInspectionUrl: "{{ route('api.reading-gap.record-inspection') }}",
                recordSwapUrl: "{{ route('api.reading-gap.record-swap') }}",
                clearAllInspectionsUrl: "{{ url('api/reading-gap/inspections/consumer') }}",
                csrfToken: "{{ csrf_token() }}",
                profileData: {
                    consumer_name: "{{ $account->consumer_name ?? '' }}",
                    father_name: "{{ $account->father_name ?? '' }}",
                    mobile: "{{ $account->mobile ?? '' }}",
                    address: "{{ $account->address ?? '' }}",
                    meter_no: "{{ $account->meter_no ?? '' }}",
                    tariff_category: "{{ $account->tariff_category ?? 'DS1D' }}",
                    billing_basis: "{{ $account->billing_basis ?? 'OK' }}",
                    custom_safe_ceiling: "{{ $account->custom_safe_ceiling ?? '' }}",
                    gap_recovery_mode: "{{ $account->gap_recovery_mode ?? 'auto' }}"
                }
            };
        </script>
        <script src="{{ asset('js/consumers/consumer-detail-app.js') }}?v={{ file_exists(public_path('js/consumers/consumer-detail-app.js')) ? filemtime(public_path('js/consumers/consumer-detail-app.js')) : time() }}"></script>
    @endpush
</x-app-layout>
