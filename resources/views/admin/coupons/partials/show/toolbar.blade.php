<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <a href="{{ route('admin.coupons.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
        <span>←</span> Back to All Coupons
    </a>

    <div class="flex items-center gap-2">
        <!-- Toggle Status Form -->
        <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" class="inline">
            @csrf
            @method('PATCH')
            <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $coupon->is_active ? 'bg-amber-950/70 hover:bg-amber-900 text-amber-300 border border-amber-500/30' : 'bg-emerald-950/70 hover:bg-emerald-900 text-emerald-300 border border-emerald-500/30' }}">
                {{ $coupon->is_active ? '🚫 Deactivate' : '✓ Activate' }}
            </button>
        </form>

        <!-- Edit Button -->
        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition">
            <span>✏️</span> Edit Campaign
        </a>

        <!-- Delete Button -->
        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" onclick="return confirm('Delete coupon campaign {{ $coupon->code }}?');" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-500/40 transition">
                <span>🗑️</span> Delete
            </button>
        </form>
    </div>
</div>
