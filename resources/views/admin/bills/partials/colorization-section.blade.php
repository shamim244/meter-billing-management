<!-- Section 4: Dynamic Visual Colorization & Threshold Ranges -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <input type="hidden" name="has_color_settings" value="1">
    @include('admin.bills.partials.colorization.header-master-toggle')

    <!-- Two-Column Grid: Bill Amount Thresholds & Average Unit Thresholds -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @include('admin.bills.partials.colorization.amount-thresholds')
        @include('admin.bills.partials.colorization.unit-thresholds')
    </div>
</div>
