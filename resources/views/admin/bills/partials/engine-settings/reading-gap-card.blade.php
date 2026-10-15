<!-- Section 5: Reading Gap & Safe Ceiling Reconciliation Engine -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <input type="hidden" name="has_gap_settings" value="1">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800/80 pb-5">
        <div class="space-y-1">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>⚖️</span>
                <span>Reading Gap & Safe Reconciliation Engine</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Active Invariant</span>
            </h2>
            <p class="text-xs text-slate-400">
                Smoothly absorbs under-posted reading gaps across monthly billing cycles while preventing ₹15,000+ tariff spikes and audit flags.
            </p>
        </div>
    </div>

    <!-- Safe Monthly Unit Ceilings by Tariff -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Domestic Ceiling -->
        <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                    <span>Domestic (DS-1D / DS-II)</span>
                </label>
                <span class="text-[11px] font-mono font-bold text-emerald-400" x-text="(Number(gapDomesticCeiling) || 220) + ' kWh/mo'"></span>
            </div>
            <input type="number" name="gap_safe_ceiling_domestic" x-model="gapDomesticCeiling" min="50" max="1000" step="10" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
            <span class="text-[10px] text-slate-500 block">Default: 220 kWh/mo (prevents massive rural domestic bill shocks).</span>
        </div>

        <!-- Commercial Ceiling -->
        <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                    <span>Commercial (NDS-1D)</span>
                </label>
                <span class="text-[11px] font-mono font-bold text-amber-400" x-text="(Number(gapCommercialCeiling) || 400) + ' kWh/mo'"></span>
            </div>
            <input type="number" name="gap_safe_ceiling_commercial" x-model="gapCommercialCeiling" min="50" max="2000" step="10" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:ring-amber-500 focus:border-amber-500">
            <span class="text-[10px] text-slate-500 block">Default: 400 kWh/mo.</span>
        </div>

        <!-- Agriculture Ceiling -->
        <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>
                    <span>Agriculture (IAS-1)</span>
                </label>
                <span class="text-[11px] font-mono font-bold text-blue-400" x-text="(Number(gapAgriCeiling) || 800) + ' kWh/mo'"></span>
            </div>
            <input type="number" name="gap_safe_ceiling_agriculture" x-model="gapAgriCeiling" min="100" max="5000" step="50" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:ring-blue-500 focus:border-blue-500">
            <span class="text-[10px] text-slate-500 block">Default: 800 kWh/mo.</span>
        </div>
    </div>

    <!-- Hold Mode & Inspection Staleness -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 border-t border-slate-800/80">
        <!-- Default Over-Posted Hold Mode -->
        <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-2">
            <label class="text-xs font-bold text-slate-300">Default Over-Posted Hold Action</label>
            <p class="text-[11px] text-slate-400">Determines default recommended bill units when physical meter reading is lower than posted reading.</p>
            <select name="gap_hold_default_mode" x-model="gapHoldMode" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
                <option value="zero">+0 Exact Hold (Freeze bill at previous reading)</option>
                <option value="one">+1 Unit Advance (Advance 1 unit to satisfy strict forward validation)</option>
            </select>
        </div>

        <!-- Inspection Staleness Warning Days -->
        <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-2">
            <div class="flex items-center justify-between">
                <label class="text-xs font-bold text-slate-300">Field Inspection Staleness Alert</label>
                <span class="text-[11px] font-mono font-bold text-slate-300" x-text="(Number(staleWarningDays) || 90) + ' days'"></span>
            </div>
            <p class="text-[11px] text-slate-400">Marks meter reading as stale when no field inspection has occurred within this duration.</p>
            <input type="number" name="inspection_stale_warning_days" x-model="staleWarningDays" min="15" max="365" step="5" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
        </div>
    </div>
</div>
