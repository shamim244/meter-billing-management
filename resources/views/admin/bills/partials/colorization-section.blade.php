<!-- Section 4: Dynamic Visual Colorization & Threshold Ranges -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <input type="hidden" name="has_color_settings" value="1">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-slate-800/80 pb-4 gap-3">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>🎨</span>
                <span>Dynamic Visual Colorization & Threshold Ranges</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Real-time heatmap color gradients, usage zone spectrums, and visual alert thresholds across the Dashboard.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="resetColorDefaults()" class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white border border-slate-700/60 transition" title="Reset color and usage thresholds to factory defaults in form">
                🔄 Reset Form Presets
            </button>
            <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg transition" :class="colorizationEnabled ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700'" x-text="colorizationEnabled ? '🎨 Colorization: ON' : 'Colorization: OFF'">
            </span>
        </div>
    </div>

    <!-- Master Toggle & Quick Presets -->
    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="space-y-1">
                <span class="font-bold text-xs uppercase tracking-wider text-indigo-400 flex items-center gap-1.5">
                    <span>🌈</span>
                    <span>Master Colorization Engine</span>
                </span>
                <h3 class="text-sm font-bold text-white">Enable Dynamic Heatmap & Zone Spectrum</h3>
                <p class="text-[11px] text-slate-400 leading-relaxed max-w-xl">
                    When enabled, bill amounts and average consumption units dynamically colorize across the card grid and table list views into Calm Green (Safe), Lime/Amber (Normal), Warm Orange (Elevated), and Bold Red (High-Alert).
                </p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input type="checkbox" name="dynamic_colorization_enabled" value="1" x-model="colorizationEnabled" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
            </label>
        </div>

        <!-- Quick Presets Bar -->
        <div class="pt-3 border-t border-slate-800/60 flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1">Quick Presets:</span>
            <button type="button" @click="applyPreset('rural')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-700/60 transition active:scale-95 flex items-center gap-1.5">
                <span>🌾</span>
                <span>Rural Low-Cap</span>
                <span class="text-[10px] text-slate-500 font-mono">(₹300 / ₹1k / ₹2k • 35/80/150 kWh)</span>
            </button>
            <button type="button" @click="applyPreset('balanced')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-950/40 hover:bg-indigo-900/50 text-indigo-300 border border-indigo-700/40 transition active:scale-95 flex items-center gap-1.5">
                <span>⚖️</span>
                <span>Balanced Default</span>
                <span class="text-[10px] text-indigo-400/80 font-mono">(₹500 / ₹1.5k / ₹2.5k • 50/120/200 kWh)</span>
            </button>
            <button type="button" @click="applyPreset('urban')" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-950 hover:bg-slate-800 text-slate-300 border border-slate-700/60 transition active:scale-95 flex items-center gap-1.5">
                <span>🏙️</span>
                <span>Urban High-Cap</span>
                <span class="text-[10px] text-slate-500 font-mono">(₹800 / ₹2.5k / ₹5k • 80/200/350 kWh)</span>
            </button>
        </div>
    </div>

    <!-- Two-Column Grid: Bill Amount Thresholds & Average Unit Thresholds -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Column 1: Bill Amount Thresholds (₹) -->
        <div class="p-5 sm:p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-5">
            <div class="border-b border-slate-800/80 pb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>💰</span>
                    <span>Bill Amount Thresholds (₹)</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Defines numerical boundaries for green safe zones, amber warning ranges, and bold red high-alert triggers.
                </p>
            </div>

            <!-- Amount Safe Ceiling -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        <span>Safe Zone Ceiling</span>
                    </label>
                    <span class="text-[11px] font-mono font-bold text-emerald-400" x-text="'&le; ₹' + (Number(amountSafeCeiling) || 0).toLocaleString()"></span>
                </div>
                <input type="number" step="10" min="0" name="color_amount_safe_ceiling" x-model="amountSafeCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
                <span class="text-[10px] text-slate-500 block">Default: ₹500. Negative/Credit and amounts up to this ceiling display in Vibrant Green (Safe).</span>
            </div>

            <!-- Amount Warning Ceiling -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                        <span>Warning Zone Ceiling</span>
                    </label>
                    <span class="text-[11px] font-mono font-bold text-amber-400" x-text="'&le; ₹' + (Number(amountWarningCeiling) || 0).toLocaleString()"></span>
                </div>
                <input type="number" step="10" min="0" name="color_amount_warning_ceiling" x-model="amountWarningCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-amber-500 focus:border-amber-500">
                <span class="text-[10px] text-slate-500 block">Default: ₹1,500. Amounts between Safe and Warning ceilings display in Lime / Amber (Normal).</span>
            </div>

            <!-- Amount Danger Floor -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
                        <span>High-Alert Danger Floor</span>
                    </label>
                    <span class="text-[11px] font-mono font-bold text-rose-400" x-text="'&ge; ₹' + (Number(amountDangerFloor) || 0).toLocaleString()"></span>
                </div>
                <input type="number" step="10" min="0" name="color_amount_danger_floor" x-model="amountDangerFloor" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-rose-500 focus:border-rose-500">
                <span class="text-[10px] text-slate-500 block">Default: ₹2,500. Warning to Danger floor displays in Warm Orange. Floor+ displays in Bold Red with an Alert badge.</span>
            </div>
        </div>

        <!-- Column 2: Average Units Thresholds (kWh) -->
        <div class="p-5 sm:p-6 rounded-2xl bg-slate-900/50 border border-slate-800 space-y-5">
            <div class="border-b border-slate-800/80 pb-3">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>⚡</span>
                    <span>Average Usage Thresholds (kWh)</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    Configures dynamic colorization and card border spectrum for Box 3 Average Usage and Table View units.
                </p>
            </div>

            <!-- Units Safe Ceiling -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                        <span>Low-Usage Safe Ceiling</span>
                    </label>
                    <span class="text-[11px] font-mono font-bold text-emerald-400" x-text="'&le; ' + (parseInt(unitsSafeCeiling) || 0) + ' kWh'"></span>
                </div>
                <input type="number" step="1" min="0" name="color_units_safe_ceiling" x-model="unitsSafeCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-emerald-500 focus:border-emerald-500">
                <span class="text-[10px] text-slate-500 block">Default: 50 kWh. Readings and borders display in Deep Green (Safe Zone).</span>
            </div>

            <!-- Units Warning Ceiling -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
                        <span>Moderate Zone Ceiling</span>
                    </label>
                    <span class="text-[11px] font-mono font-bold text-amber-400" x-text="'&le; ' + (parseInt(unitsWarningCeiling) || 0) + ' kWh'"></span>
                </div>
                <input type="number" step="1" min="0" name="color_units_warning_ceiling" x-model="unitsWarningCeiling" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-amber-500 focus:border-amber-500">
                <span class="text-[10px] text-slate-500 block">Default: 120 kWh. Moderate monthly consumption displays in Lime / Amber.</span>
            </div>

            <!-- Units Danger Floor -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
                        <span>High-Usage Danger Floor</span>
                    </label>
                    <span class="text-[11px] font-mono font-bold text-rose-400" x-text="'&ge; ' + (parseInt(unitsDangerFloor) || 0) + ' kWh'"></span>
                </div>
                <input type="number" step="1" min="0" name="color_units_danger_floor" x-model="unitsDangerFloor" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white font-mono focus:ring-rose-500 focus:border-rose-500">
                <span class="text-[10px] text-slate-500 block">Default: 200 kWh. High heavy-usage readings and borders display in Bold Red (Alert Zone).</span>
            </div>
        </div>
    </div>
</div>
