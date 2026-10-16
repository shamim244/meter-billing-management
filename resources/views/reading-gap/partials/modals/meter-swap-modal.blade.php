<!-- Meter Swap / Replacement Modal -->
<div x-show="showSwapModal" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="showSwapModal = false"
     x-cloak 
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
    
    <div @click.outside="showSwapModal = false"
         class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden p-6 space-y-4">
        
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 text-base">🔄</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Record Meter Replacement</h3>
            </div>
            <button type="button" @click="showSwapModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none cursor-pointer">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
            <!-- CA & Current Info -->
            <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/60 dark:border-slate-700/60">
                <div class="font-bold text-slate-800 dark:text-slate-200" x-text="swapForm.consumer_name"></div>
                <div class="font-mono text-slate-400 text-[11px]" x-text="'CA: ' + swapForm.ca_number + ' | Current Meter: ' + swapForm.old_meter_no"></div>
            </div>

            <!-- New Meter Number -->
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">New Meter Serial Number *</label>
                <input type="text" x-model="swapForm.new_meter_no" placeholder="e.g. GP1029384"
                       class="w-full text-xs font-mono py-1.5 px-3 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 shadow-xs">
            </div>

            <!-- Initial Reading of New Meter -->
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Initial Reading on New Meter (kWh) *</label>
                <input type="number" step="any" min="0" x-model="swapForm.initial_reading" placeholder="Usually 0 or 1"
                       class="w-full text-xs font-mono py-1.5 px-3 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 shadow-xs">
            </div>

            <!-- Old Meter Final Reading -->
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Old Meter Final Reading (if visible)</label>
                <input type="number" step="any" min="0" x-model="swapForm.old_meter_final_reading" placeholder="Reading at moment of removal"
                       class="w-full text-xs font-mono py-1.5 px-3 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 shadow-xs">
            </div>

            <!-- Notes -->
            <div>
                <label class="block font-bold text-slate-700 dark:text-slate-300 mb-1">Replacement Notes / JE Order No</label>
                <input type="text" x-model="swapForm.notes" placeholder="e.g. Burned meter replaced per Order #104"
                       class="w-full text-xs py-1.5 px-3 rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-200 shadow-xs">
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="showSwapModal = false" 
                    class="px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                Cancel
            </button>
            <button type="button" @click="submitMeterSwap()" :disabled="savingSwap || !swapForm.new_meter_no || swapForm.initial_reading === ''"
                    class="px-4 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                <span x-show="!savingSwap">Confirm Meter Swap</span>
                <span x-show="savingSwap">Saving...</span>
            </button>
        </div>
    </div>
</div>
