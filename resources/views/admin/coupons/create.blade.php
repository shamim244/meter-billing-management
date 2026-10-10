<x-admin-layout>
    <x-slot name="header">
        Create New Coupon Campaign
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6" x-data="couponsFormApp(window.couponFormConfig)">
        <div class="flex items-center justify-between">
            <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>←</span> Back to All Coupons
            </a>
        </div>

        <form method="POST" action="{{ route('admin.coupons.store') }}" class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            @csrf

            @include('admin.coupons.partials.form.errors')
            @include('admin.coupons.partials.form.type-selector')

            <div class="border-t border-slate-800 pt-6 space-y-6">
                @include('admin.coupons.partials.form.code-input')
                @include('admin.coupons.partials.form.subscription-rules')
                @include('admin.coupons.partials.form.slabs-builder')
                @include('admin.coupons.partials.form.limits-schedule')
                @include('admin.coupons.partials.form.status-toggle')
            </div>

            <div class="border-t border-slate-800 pt-5 flex justify-end gap-3">
                <a href="{{ route('admin.coupons.index') }}" class="px-5 py-2 rounded-xl text-xs font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 rounded-xl text-xs font-black bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-lg shadow-indigo-600/30 transition">
                    Launch Coupon Campaign
                </button>
            </div>
        </form>
    </div>

    <!-- Decoupled Script & Cache Busting -->
    <script>
        window.couponFormConfig = {
            type: 'subscription_discount',
            discountKind: 'percentage',
            slabs: [
                { min_amount: 100, max_amount: 1000, bonus_percent: 5 },
                { min_amount: 1001, max_amount: 5000, bonus_percent: 10 },
                { min_amount: 5001, max_amount: '', bonus_percent: 15 }
            ]
        };
    </script>
    <script src="{{ asset('js/admin/coupons/coupons-form-app.js') }}?v={{ file_exists(public_path('js/admin/coupons/coupons-form-app.js')) ? filemtime(public_path('js/admin/coupons/coupons-form-app.js')) : time() }}"></script>
</x-admin-layout>
