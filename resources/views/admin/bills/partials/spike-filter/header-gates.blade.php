<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-800/80 pb-4 gap-2">
    <div>
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>📊</span>
            <span>Smart Average & Spike Filter Configuration</span>
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">
            Dynamic dual-layer quality gate for historical consumption records, automated spike trimming, and category-level agricultural bypass protection.
        </p>
    </div>
    <div class="flex items-center gap-2">
        <button type="button" @click="resetSpikeDefaults()" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/60 transition" title="Reset multipliers and buffer to factory defaults in form">
            🔄 Reset Form Presets
        </button>
        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg {{ $settings['calculation_filter_enabled'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700' }}">
            {{ $settings['calculation_filter_enabled'] ? '🛡️ Filter: Active' : 'Pass-Through (Off)' }}
        </span>
    </div>
</div>

<!-- Dual Quality Gate Toggles (Layer 1 & Layer 2) -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- Layer 1: Ingestion-Time Quality Gate -->
    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-3">
        <div class="flex items-center justify-between">
            <span class="font-bold text-xs uppercase tracking-wider text-indigo-400 flex items-center gap-1.5">
                <span>📥</span>
                <span>Layer 1: Ingestion-Time Gate</span>
            </span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="extraction_filter_enabled" value="1" class="sr-only peer" {{ $settings['extraction_filter_enabled'] ? 'checked' : '' }}>
                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>
        <h3 class="text-sm font-bold text-white">Extraction Filter Gate</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">
            Applied during PDF bill parsing and historical table ingest. Eliminates ghost rows (0 units, null readings), marks non-OK bases as inactive for average consumption, and flags extreme spikes with <code>is_spike = true</code> metadata.
        </p>
    </div>

    <!-- Layer 2: Calculation-Time Smart Average Gate -->
    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-3">
        <div class="flex items-center justify-between">
            <span class="font-bold text-xs uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                <span>⚡</span>
                <span>Layer 2: Calculation-Time Gate</span>
            </span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="calculation_filter_enabled" value="1" class="sr-only peer" {{ $settings['calculation_filter_enabled'] ? 'checked' : '' }}>
                <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-600"></div>
            </label>
        </div>
        <h3 class="text-sm font-bold text-white">Smart Average Calculation Gate</h3>
        <p class="text-[11px] text-slate-400 leading-relaxed">
            Applied dynamically during live operator review, bulk projection, and cycle creation. Evaluates clean median from OK months, excludes non-OK bases and frozen cycles, and trims outlier spikes based on consumer tariff multipliers.
        </p>
    </div>
</div>
