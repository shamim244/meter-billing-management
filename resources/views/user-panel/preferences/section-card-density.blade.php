<!-- 3. Card View Density & Spacing -->
<div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
    <label class="text-sm font-bold text-slate-900 dark:text-white block">Card View Density & Spacing</label>
    <p class="text-xs text-slate-500 dark:text-slate-400">Control padding and layout compactness during card review.</p>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
        <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['card_density'] ?? 'compact') === 'compact' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="card_density" value="compact" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['card_density'] ?? 'compact') === 'compact' ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold flex items-center gap-1.5">
                    <span>⚡ Compact / Standard (Recommended)</span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Reduced gaps and optimal spacing designed to fit on standard laptop screens without scrolling.</div>
            </div>
        </label>

        <label class="p-4 rounded-2xl border cursor-pointer flex items-center gap-3 transition {{ ($preferences['card_density'] ?? 'compact') === 'comfortable' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-900 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="card_density" value="comfortable" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['card_density'] ?? 'compact') === 'comfortable' ? 'checked' : '' }}>
            <div>
                <div class="text-xs font-bold flex items-center gap-1.5">
                    <span>📐 Comfortable / Spacious</span>
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Generous padding with relaxed visual spacing.</div>
            </div>
        </label>
    </div>
</div>

<!-- 4. Amount Text Size in Card View -->
<div class="space-y-2 border-b border-slate-100 dark:border-slate-800 pb-6">
    <label class="text-sm font-bold text-slate-900 dark:text-white block">Card Amount Font Size</label>
    <p class="text-xs text-slate-500 dark:text-slate-400">Scale the total billing amount font size in the middle banner.</p>
    
    <div class="flex items-center gap-3 pt-2">
        <label class="px-5 py-3 rounded-2xl border cursor-pointer flex items-center gap-2 text-xs font-bold transition {{ ($preferences['amount_size'] ?? 'standard') === 'standard' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="amount_size" value="standard" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['amount_size'] ?? 'standard') === 'standard' ? 'checked' : '' }}>
            <span>Standard Clean (Balanced)</span>
        </label>

        <label class="px-5 py-3 rounded-2xl border cursor-pointer flex items-center gap-2 text-xs font-bold transition {{ ($preferences['amount_size'] ?? 'standard') === 'large' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="amount_size" value="large" class="text-brand-600 focus:ring-brand-500" {{ ($preferences['amount_size'] ?? 'standard') === 'large' ? 'checked' : '' }}>
            <span>Large / Extra Prominent</span>
        </label>
    </div>
</div>
