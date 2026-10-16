{{-- Interactive Reconciliation Strategy & Recovery Pacing Workbench --}}
<div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
        <div class="flex items-center gap-2">
            <span class="text-xl">⚙️</span>
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Forensic Recovery Strategy & Pacing Workbench</h3>
                <p class="text-[11px] text-slate-500">Fine-tune automated reconciliation pacing rules for this specific consumer</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span x-show="pacingSuccess" x-cloak class="text-xs font-bold text-emerald-500 animate-pulse">✓ Saved Successfully</span>
        </div>
    </div>

    <form @submit.prevent="savePacingStrategy()" class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <!-- Recovery Mode -->
        <div class="space-y-1">
            <label class="font-bold text-slate-700 dark:text-slate-300">Reconciliation Recovery Mode</label>
            <select x-model="pacingForm.gap_recovery_mode" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-mono focus:ring-brand-500 focus:border-brand-500">
                <option value="auto">Auto Standard (Dynamic Pacing)</option>
                <option value="hold_exact">+0 Hold Exact (Freeze until meter matches)</option>
                <option value="hold_advance">+1 Unit Advance (Prevents billing drop alert)</option>
                <option value="amortize_safe">Safe Amortize (Distribute evenly below ceiling)</option>
            </select>
            <p class="text-[10px] text-slate-400">Controls how discrepancy units are sliced across future billing cycles.</p>
        </div>

        <!-- Safe Monthly Ceiling Override -->
        <div class="space-y-1">
            <label class="font-bold text-slate-700 dark:text-slate-300">Safe Monthly Ceiling Override (kWh)</label>
            <input type="number" 
                   x-model="pacingForm.custom_safe_ceiling" 
                   min="0" 
                   max="5000" 
                   placeholder="Default by tariff: {{ $gapSummary['safe_ceiling'] ?? 220 }} kWh" 
                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-mono focus:ring-brand-500 focus:border-brand-500">
            <p class="text-[10px] text-slate-400">Monthly cap to prevent high-bill complaints or unexpected bill shock.</p>
        </div>

        <!-- Submit Button -->
        <div class="space-y-1 flex flex-col justify-end">
            <button type="submit" 
                    :disabled="savingPacing" 
                    class="w-full px-4 py-2 rounded-xl text-xs font-bold bg-brand-600 hover:bg-brand-500 text-white flex items-center justify-center gap-1.5 transition active:scale-95 disabled:opacity-50 cursor-pointer shadow-xs">
                <span x-show="savingPacing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>Update Pacing Strategy</span>
            </button>
            <p class="text-[10px] text-center text-slate-400">Takes immediate effect on next billing cycle projection.</p>
        </div>
    </form>
</div>
