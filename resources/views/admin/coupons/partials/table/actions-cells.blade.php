<!-- Status -->
<td class="py-3.5 px-4 text-center">
    <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" class="inline">
        @csrf
        @method('PATCH')
        <button type="submit" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase transition {{ $coupon->is_active ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30 hover:bg-emerald-900' : 'bg-slate-800 text-slate-400 border border-slate-700 hover:bg-slate-700' }}">
            {{ $coupon->is_active ? 'Active' : 'Inactive' }}
        </button>
    </form>
</td>

<!-- Actions -->
<td class="py-3.5 px-5 text-center">
    <div class="flex items-center justify-center gap-1.5 flex-wrap">
        <!-- Analytics / Show -->
        <a href="{{ route('admin.coupons.show', $coupon) }}" class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-indigo-300 border border-indigo-500/30 transition" title="View Analytics & Logs">
            👁️ Show
        </a>

        <!-- Edit -->
        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="px-2 py-1 rounded-lg text-xs font-bold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 transition" title="Edit Coupon">
            ✏️ Edit
        </a>

        <!-- Delete -->
        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline">
            @csrf
            @method('DELETE')
            <button type="submit" onclick="return confirm('Delete coupon {{ $coupon->code }}?');" class="px-2 py-1 rounded-lg text-xs font-bold bg-rose-950/60 hover:bg-rose-900 text-rose-300 border border-rose-500/30 transition" title="Delete Coupon">
                🗑️
            </button>
        </form>
    </div>
</td>
