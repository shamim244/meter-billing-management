{{-- Monthly Reading Matrix Tab --}}
<div x-show="activeTab === 'matrix'" class="space-y-4">
    @include('bills.partials.history.matrix-table', ['meterMatrix' => $meterMatrix])
</div>
