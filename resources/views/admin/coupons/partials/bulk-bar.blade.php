{{-- Bulk Action Bar --}}
<div x-show="selectedCoupons.length > 0" x-cloak class="bg-indigo-950/90 border border-indigo-500/40 p-4 rounded-2xl shadow-xl flex items-center justify-between gap-3 animate-in fade-in duration-150">
    <div class="flex items-center gap-2 text-xs font-bold text-indigo-200">
        <span class="px-2 py-0.5 bg-indigo-600 text-white rounded-md font-mono" x-text="selectedCoupons.length"></span>
        <span>coupon(s) selected</span>
    </div>

    <form method="POST" action="{{ route('admin.coupons.bulk-deactivate') }}" class="flex items-center gap-2">
        @csrf
        <template x-for="id in selectedCoupons" :key="id">
            <input type="hidden" name="coupon_ids[]" :value="id">
        </template>

        <button type="submit" onclick="return confirm('Deactivate selected coupon campaigns?');" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold shadow transition">
            🚫 Deactivate Selected
        </button>
    </form>
</div>
