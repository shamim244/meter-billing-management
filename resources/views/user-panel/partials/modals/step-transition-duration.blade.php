{{-- Step 1 (Flow B: Upgrade / Downgrade / New): Choose Duration --}}
<div x-show="currentStep === 1" class="space-y-4">
    <div class="flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
        <span>Select your desired billing duration:</span>
        <span class="text-[11px] text-slate-400 font-normal">Discounts auto-applied</span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
        <template x-for="d in availableDurations" :key="d.id">
            <button type="button" 
                    @click="selectDuration(d)"
                    :class="selectedDuration && selectedDuration.id === d.id ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-2 ring-indigo-400/50' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 hover:border-indigo-300 dark:hover:border-indigo-700'"
                    class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between relative cursor-pointer">
                <div class="flex items-center justify-between gap-1">
                    <span class="text-xs font-bold" :class="selectedDuration && selectedDuration.id === d.id ? 'text-white' : 'text-slate-900 dark:text-white'" x-text="d.name || (d.duration_value || d.duration_months) + (d.duration_unit === 'day' ? ' Days' : ' Month(s)')"></span>
                    <template x-if="d.discount_percent > 0">
                        <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md" 
                              :class="selectedDuration && selectedDuration.id === d.id ? 'bg-amber-400 text-amber-950 font-black' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 font-bold'"
                              x-text="d.discount_percent + '% OFF'"></span>
                    </template>
                </div>
                <div class="mt-2.5 flex items-baseline justify-between">
                    <div class="text-base font-mono font-black" :class="selectedDuration && selectedDuration.id === d.id ? 'text-white' : 'text-slate-900 dark:text-white'">
                        ₹<span x-text="parseFloat(d.final_price).toLocaleString('en-IN')"></span>
                    </div>
                    <span x-show="selectedDuration && selectedDuration.id === d.id" class="text-[10px] font-bold uppercase tracking-wider text-white bg-white/20 px-1.5 py-0.5 rounded">
                        ✓ Selected
                    </span>
                </div>
            </button>
        </template>
    </div>

    <!-- Duration Summary Pill Banner -->
    <div class="p-3.5 rounded-2xl bg-indigo-50/80 dark:bg-indigo-950/60 border border-indigo-200/80 dark:border-indigo-800 flex items-center justify-between text-xs">
        <div class="flex items-center gap-2">
            <span class="text-base">📅</span>
            <span class="font-medium text-slate-700 dark:text-slate-300">Selected Duration:</span>
            <strong class="font-bold text-indigo-950 dark:text-indigo-200" x-text="selectedDuration ? (selectedDuration.name || (selectedDuration.duration_value || selectedDuration.duration_months) + (selectedDuration.duration_unit === 'day' ? ' Days' : ' Month(s)')) : ''"></strong>
        </div>
        <div class="font-mono font-black text-indigo-950 dark:text-white text-sm">
            ₹<span x-text="selectedDuration ? parseFloat(selectedDuration.final_price).toLocaleString('en-IN') : '0'"></span>
        </div>
    </div>

    <!-- Next Button -->
    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
        <button type="button" @click="goToStep(2)" :disabled="isLoadingQuote" class="py-2.5 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
            <span>Continue to Comparison & Math</span>
            <span>➔</span>
        </button>
    </div>
</div>
