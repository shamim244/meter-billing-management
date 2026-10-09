{{-- Submit Actions --}}
<div class="flex items-center justify-end gap-3 pt-2">
    <a href="{{ route('admin.payments.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition">
        Cancel
    </a>
    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 flex items-center gap-2">
        <span>💾</span> Save All Payment Settings
    </button>
</div>
