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
