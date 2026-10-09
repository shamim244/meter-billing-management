<div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
            <span>⚙️</span> Platform Grace Period Policy
        </h3>
        <p class="text-xs text-slate-400 mt-0.5">Sets platform-wide default grace period. Individual plans can override this.</p>
    </div>
    <form method="POST" action="{{ route('admin.subscriptions.update_settings') }}" class="flex items-center gap-3">
        @csrf
        <div class="flex items-center gap-2">
            <label class="text-xs text-slate-300 font-medium">Default Days:</label>
            <input type="number" min="0" max="90" name="default_grace_period_days" value="{{ $defaultGraceDays }}" class="w-16 text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-2.5 font-mono text-center">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
            Save Default
        </button>
    </form>
</div>
