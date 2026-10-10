<x-admin-layout>
    <x-slot name="header">
        Edit Coupon Campaign — {{ $coupon->code }}
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6" x-data="couponsFormApp(window.couponFormConfig)">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.coupons.show', $coupon) }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>←</span> Back to Coupon Details
            </a>
        </div>

        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            @csrf
            @method('PUT')

            @include('admin.coupons.partials.form.errors')
            @include('admin.coupons.partials.form.type-selector', ['coupon' => $coupon])

            <div class="border-t border-slate-800 pt-6 space-y-6">
                @include('admin.coupons.partials.form.code-input', ['coupon' => $coupon])
                @include('admin.coupons.partials.form.subscription-rules', ['coupon' => $coupon])
                @include('admin.coupons.partials.form.slabs-builder')
                @include('admin.coupons.partials.form.limits-schedule', ['coupon' => $coupon])
                @include('admin.coupons.partials.form.status-toggle', ['coupon' => $coupon])
            </div>

            <div class="border-t border-slate-800 pt-5 flex justify-end gap-3">
                <a href="{{ route('admin.coupons.show', $coupon) }}" class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 rounded-xl text-xs font-black bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script>
        window.couponFormConfig = {
            type: '{{ $coupon->type }}',
            discountKind: '{{ $coupon->discount_kind ?? 'percentage' }}',
            slabs: {{ \Illuminate\Support\Js::from($coupon->slabs->map(fn($s) => ['min_amount' => (float)$s->min_amount, 'max_amount' => $s->max_amount ? (float)$s->max_amount : '', 'bonus_percent' => (float)$s->bonus_percent])->values()->all()) }}
        };
    </script>
    <script src="{{ asset('js/admin/coupons/coupons-form-app.js') }}?v={{ file_exists(public_path('js/admin/coupons/coupons-form-app.js')) ? filemtime(public_path('js/admin/coupons/coupons-form-app.js')) : time() }}"></script>
</x-admin-layout>
