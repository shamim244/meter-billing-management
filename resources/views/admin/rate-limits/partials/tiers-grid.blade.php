{{-- Tiered Limits Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @include('admin.rate-limits.partials.tiers.operational-tiers')
    @include('admin.rate-limits.partials.tiers.security-guidance')
</div>
