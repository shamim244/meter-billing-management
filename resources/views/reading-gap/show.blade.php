<x-app-layout>
    <x-slot name="header">
        @include('reading-gap.partials.show.header')
    </x-slot>

    <div class="py-6" x-data="consumerGapDetailWorkspace()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Forensic Diagnostics Cards --}}
            @include('reading-gap.partials.show.diagnostics-deck')

            {{-- Interactive Strategy & Pacing Workbench --}}
            @include('reading-gap.partials.show.pacing-workbench')

            {{-- Chronological Inspection Visits Ledger --}}
            @include('reading-gap.partials.show.visit-ledger')

            {{-- Comparative Reading Discrepancy Ledger --}}
            @include('reading-gap.partials.show.comparative-ledger')
        </div>

        {{-- Shared Modals --}}
        @include('reading-gap.partials.modals.inspection-modal')
        @include('reading-gap.partials.modals.meter-swap-modal')
    </div>

    @push('scripts')
        <script>
            window.consumerGapConfig = {
                caNumber: "{{ $account->ca_number }}",
                consumerName: "{{ $account->consumer_name ?? '' }}",
                meterNo: "{{ $account->meter_no ?? '' }}",
                postedReading: {{ (int) ($latestBill?->current_reading ?? $latestBill?->previous_reading ?? 0) }},
                gapRecoveryMode: "{{ $account->gap_recovery_mode ?? 'auto' }}",
                customSafeCeiling: "{{ $account->custom_safe_ceiling ?? '' }}",
                updateConsumerUrl: "{{ route('consumers.update', $account->ca_number) }}",
                inspectionsUrl: "{{ url('api/reading-gap/inspections') }}",
                recordInspectionUrl: "{{ route('api.reading-gap.record-inspection') }}",
                recordSwapUrl: "{{ route('api.reading-gap.record-swap') }}",
                clearAllInspectionsUrl: "{{ url('api/reading-gap/inspections/consumer') }}",
                csrfToken: "{{ csrf_token() }}"
            };
        </script>
        <script src="{{ asset('js/reading-gap/consumer-gap-detail-app.js') }}?v={{ file_exists(public_path('js/reading-gap/consumer-gap-detail-app.js')) ? filemtime(public_path('js/reading-gap/consumer-gap-detail-app.js')) : time() }}"></script>
    @endpush
</x-app-layout>
