{{-- Coupons Table --}}
<div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            @include('admin.coupons.partials.table.header')
            <tbody class="divide-y divide-slate-800/70 font-medium">
                @forelse($coupons as $coupon)
                    <tr class="hover:bg-slate-900/40 transition">
                        @include('admin.coupons.partials.table.code-type-cells')
                        @include('admin.coupons.partials.table.discount-redemption-cells')
                        @include('admin.coupons.partials.table.actions-cells')
                    </tr>
                @empty
                    @include('admin.coupons.partials.table.empty-state')
                @endforelse
            </tbody>
        </table>
    </div>

    @include('admin.coupons.partials.table.pagination')
</div>
