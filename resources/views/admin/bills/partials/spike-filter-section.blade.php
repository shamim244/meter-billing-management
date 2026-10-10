<!-- Section 3: Smart Average & Spike Filter Configuration -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <input type="hidden" name="has_spike_settings" value="1">
    @include('admin.bills.partials.spike-filter.header-gates')
    @include('admin.bills.partials.spike-filter.rules-buffers')
    @include('admin.bills.partials.spike-filter.category-overrides')
</div>
