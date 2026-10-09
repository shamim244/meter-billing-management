<!-- Section 3: Smart Average & Spike Filter Configuration -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <input type="hidden" name="has_spike_settings" value="1">
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

    <!-- Exclusion Rules Toggles -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
        <label class="flex items-start gap-3 p-4 rounded-xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
            <input type="checkbox" name="filter_non_ok_bases" value="1" class="mt-0.5 rounded border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-slate-800" {{ $settings['filter_non_ok_bases'] ? 'checked' : '' }}>
            <div>
                <span class="text-xs font-bold text-white block">Exclude Non-OK Bases (MD, LK, PL, DL, EST)</span>
                <span class="text-[11px] text-slate-400 mt-0.5 block leading-normal">
                    Prevents flat assessed MD or provisional LK months from contaminating normal average consumption.
                </span>
            </div>
        </label>

        <label class="flex items-start gap-3 p-4 rounded-xl bg-slate-900/40 border border-slate-800 hover:border-slate-700 cursor-pointer transition">
            <input type="checkbox" name="filter_zero_unit_months" value="1" class="mt-0.5 rounded border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-slate-800" {{ $settings['filter_zero_unit_months'] ? 'checked' : '' }}>
            <div>
                <span class="text-xs font-bold text-white block">Exclude 0-Unit / Frozen Meter Months</span>
                <span class="text-[11px] text-slate-400 mt-0.5 block leading-normal">
                    Trims months with zero consumption (e.g. locked premises, frozen meters) from pulling down historical averages.
                </span>
            </div>
        </label>
    </div>

    <!-- Global Spike Multiplier & Min Unit Buffer -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
        <!-- Global Spike Multiplier -->
        <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Global Spike Multiplier
                </label>
                <p class="text-[11px] text-slate-500">
                    Months exceeding <code>Multiplier × Median</code> and buffer delta will be trimmed from average.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                    <button type="button" @click="adjustGlobalMultiplier(-0.1)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">−</button>
                    <input type="number" step="0.1" min="1.0" max="10.0" name="global_spike_multiplier" x-model="globalSpikeMultiplier" class="w-20 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                    <button type="button" @click="adjustGlobalMultiplier(0.1)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">+</button>
                </div>
                <span class="text-xs font-bold text-slate-400">× Median</span>
            </div>

            <!-- Presets -->
            <div class="flex items-center gap-2 pt-1">
                <span class="text-[10px] uppercase font-bold text-slate-500">Presets:</span>
                @foreach([1.5, 2.0, 2.5, 3.0] as $preset)
                    <button type="button" @click="setGlobalMultiplier({{ $preset }})" :class="parseFloat(globalSpikeMultiplier) === {{ $preset }} ? 'bg-indigo-600 text-white font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'" class="px-2.5 py-1 text-[11px] rounded-lg border border-slate-700 transition">
                        {{ number_format($preset, 1) }}x
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Minimum Unit Buffer (Delta Floor) -->
        <div class="p-5 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">
                    Minimum Spike Unit Buffer Floor
                </label>
                <p class="text-[11px] text-slate-500">
                    Absolute kWh delta floor required to flag a spike. Prevents small-number false positives (e.g. 5 to 12 kWh).
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                    <button type="button" @click="adjustBuffer(-5)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">−</button>
                    <input type="number" step="1" min="0" max="500" name="min_spike_unit_buffer" x-model="minSpikeBuffer" class="w-20 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                    <button type="button" @click="adjustBuffer(5)" class="px-3 py-2 text-slate-400 hover:text-white hover:bg-slate-800 text-sm font-bold transition">+</button>
                </div>
                <span class="text-xs font-bold text-slate-400">kWh Buffer Floor</span>
            </div>

            <div class="text-[11px] text-slate-400 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800/80">
                💡 Delta must satisfy: <code>(Units − Median) &ge; Buffer</code>. If delta is below buffer, reading is safely retained.
            </div>
        </div>
    </div>

    <!-- Tariff Category Specific Overrides -->
    <div class="pt-4 border-t border-slate-800/80 space-y-4">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
            <span>🏷️</span>
            <span>Tariff Category Overrides & Protections</span>
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Agriculture IAS1 -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                <div class="flex items-center justify-between">
                    <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">IAS1 (Agriculture)</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="agriculture_spike_bypass" value="1" class="sr-only peer" {{ $settings['agriculture_spike_bypass'] ? 'checked' : '' }}>
                        <div class="w-8 h-4 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
                <span class="text-xs font-bold text-white block">Seasonal Pump Surge Bypass</span>
                <p class="text-[10px] text-slate-400 leading-relaxed">
                    When ON, agricultural irrigation surges are bypassed from trimming so seasonal pumping is preserved.
                </p>
                <div class="pt-2 border-t border-slate-800/60">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Fallback Multiplier (if bypass OFF)</label>
                    <div class="flex items-center bg-slate-950 border border-slate-800 rounded-lg overflow-hidden w-28">
                        <button type="button" @click="adjustAgriMultiplier(-0.5)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">−</button>
                        <input type="number" step="0.5" min="1.0" max="20.0" name="agriculture_spike_multiplier" x-model="agriMultiplier" class="w-12 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                        <button type="button" @click="adjustAgriMultiplier(0.5)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">+</button>
                    </div>
                </div>
            </div>

            <!-- Commercial NDS1D -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 inline-block">NDS1D (Commercial)</span>
                <span class="text-xs font-bold text-white block">Commercial Spike Multiplier</span>
                <p class="text-[10px] text-slate-400 leading-relaxed">
                    Accommodates commercial equipment, refrigeration, and seasonal customer traffic swings.
                </p>
                <div class="pt-2 border-t border-slate-800/60">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Category Multiplier</label>
                    <div class="flex items-center bg-slate-950 border border-slate-800 rounded-lg overflow-hidden w-28">
                        <button type="button" @click="adjustCommMultiplier(-0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">−</button>
                        <input type="number" step="0.1" min="1.0" max="10.0" name="commercial_spike_multiplier" x-model="commMultiplier" class="w-12 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                        <button type="button" @click="adjustCommMultiplier(0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">+</button>
                    </div>
                </div>
            </div>

            <!-- Domestic DS1D, DS-II -->
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                <span class="px-2 py-0.5 text-[10px] font-black uppercase rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 inline-block">DS1D / DS-II / KJ (Domestic)</span>
                <span class="text-xs font-bold text-white block">Domestic Spike Multiplier</span>
                <p class="text-[10px] text-slate-400 leading-relaxed">
                    Standard domestic threshold protecting residential households from sudden meter glitches or uncalibrated jumps.
                </p>
                <div class="pt-2 border-t border-slate-800/60">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Category Multiplier</label>
                    <div class="flex items-center bg-slate-950 border border-slate-800 rounded-lg overflow-hidden w-28">
                        <button type="button" @click="adjustDomMultiplier(-0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">−</button>
                        <input type="number" step="0.1" min="1.0" max="10.0" name="domestic_spike_multiplier" x-model="domMultiplier" class="w-12 bg-transparent border-0 text-center text-xs font-bold text-white focus:ring-0">
                        <button type="button" @click="adjustDomMultiplier(0.1)" class="px-2 py-1 text-slate-400 hover:text-white hover:bg-slate-800 text-xs font-bold">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
