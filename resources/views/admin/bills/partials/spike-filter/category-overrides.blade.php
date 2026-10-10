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
