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
