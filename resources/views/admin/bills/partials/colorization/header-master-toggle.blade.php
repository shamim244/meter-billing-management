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
