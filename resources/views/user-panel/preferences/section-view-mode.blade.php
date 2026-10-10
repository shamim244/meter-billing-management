<!-- 1. Default Review Mode -->
<div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
    <label class="text-sm font-bold text-slate-900 dark:text-white block">Default Dashboard Review Mode</label>
    <p class="text-xs text-slate-500 dark:text-slate-400">Choose the view layout loaded by default when you open the working dashboard.</p>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
        <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['default_view'] ?? 'card') === 'card' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="default_view" value="card" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['default_view'] ?? 'card') === 'card' ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold flex items-center gap-1.5">
                    <span>🗃️ Card View (Recommended)</span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Focuses on one consumer card at a time with hands-on-keyboard shortcuts.</div>
            </div>
        </label>

        <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['default_view'] ?? 'card') === 'table' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="default_view" value="table" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['default_view'] ?? 'card') === 'table' ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold flex items-center gap-1.5">
                    <span>📋 Table View</span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Spreadsheet-style dense listing for reviewing multiple consumers simultaneously.</div>
            </div>
        </label>
    </div>
</div>

<!-- 2. Default Page Size -->
<div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
    <label class="text-sm font-bold text-slate-900 dark:text-white block">Default Page Size</label>
    <p class="text-xs text-slate-500 dark:text-slate-400">Number of consumer records loaded per batch.</p>
    
    <div class="flex items-center gap-3 pt-2">
        @foreach([25, 50, 100] as $size)
            <label class="px-5 py-3 rounded-2xl border cursor-pointer flex items-center gap-2 text-xs font-bold transition {{ ($preferences['default_page_size'] ?? 50) == $size ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
                <input type="radio" name="default_page_size" value="{{ $size }}" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['default_page_size'] ?? 50) == $size ? 'checked' : '' }}>
                <span>{{ $size }} bills</span>
            </label>
        @endforeach
    </div>
</div>
