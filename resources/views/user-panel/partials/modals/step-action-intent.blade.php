{{-- Step 1 (Flow A: Active Plan): Choose Action Intent --}}
<div x-show="currentStep === 1" class="space-y-4">
    <div class="text-xs font-semibold text-slate-600 dark:text-slate-300">
        Select what you would like to do with your active plan:
    </div>

    <div class="space-y-3">
        <!-- Option A: Extend Validity -->
        <button type="button" 
                @click="switchActionMode('extend')"
                :class="selectedActionMode === 'extend' ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-50/90 dark:bg-indigo-950/70 ring-2 ring-indigo-500/30' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'"
                class="w-full p-4 rounded-2xl border text-left transition flex items-start justify-between gap-3 cursor-pointer">
            <div class="flex items-start gap-3">
                <span class="text-2xl p-2 rounded-xl bg-indigo-100 dark:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800">⏳</span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold" :class="selectedActionMode === 'extend' ? 'text-indigo-950 dark:text-indigo-100' : 'text-slate-900 dark:text-white'">Extend Current Validity</span>
                        <span class="text-[9px] font-black uppercase tracking-wider text-emerald-800 dark:text-emerald-200 bg-emerald-100 dark:bg-emerald-950 px-2 py-0.5 rounded-full border border-emerald-300 dark:border-emerald-800">Recommended</span>
                    </div>
                    <p class="text-xs mt-1 leading-snug" :class="selectedActionMode === 'extend' ? 'text-indigo-800 dark:text-indigo-300 font-medium' : 'text-slate-500 dark:text-slate-400'">
                        Keeps your active cycle and adds extra time directly onto your current expiration date.
                    </p>
                </div>
            </div>
            <div class="mt-1">
                <span class="w-6 h-6 rounded-full border-2 flex items-center justify-center font-black text-xs"
                      :class="selectedActionMode === 'extend' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 text-transparent'">
                    ✓
                </span>
            </div>
        </button>

        <!-- Option B: Shift Plan Period -->
        <button type="button" 
                @click="switchActionMode('shift')"
                :class="selectedActionMode === 'shift' ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-50/90 dark:bg-indigo-950/70 ring-2 ring-indigo-500/30' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-slate-300 dark:hover:border-slate-700'"
                class="w-full p-4 rounded-2xl border text-left transition flex items-start justify-between gap-3 cursor-pointer">
            <div class="flex items-start gap-3">
                <span class="text-2xl p-2 rounded-xl bg-purple-100 dark:bg-purple-900/60 border border-purple-200 dark:border-purple-800">🔄</span>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold" :class="selectedActionMode === 'shift' ? 'text-indigo-950 dark:text-indigo-100' : 'text-slate-900 dark:text-white'">Shift / Reset Billing Period</span>
                    </div>
                    <p class="text-xs mt-1 leading-snug" :class="selectedActionMode === 'shift' ? 'text-indigo-800 dark:text-indigo-300 font-medium' : 'text-slate-500 dark:text-slate-400'">
                        Start a fresh period from today. Remaining unused days from your current cycle are <strong>credited / deducted</strong>.
                    </p>
                </div>
            </div>
            <div class="mt-1">
                <span class="w-6 h-6 rounded-full border-2 flex items-center justify-center font-black text-xs"
                      :class="selectedActionMode === 'shift' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 text-transparent'">
                    ✓
                </span>
            </div>
        </button>
    </div>

    <!-- Next Button -->
    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-end">
        <button type="button" @click="goToStep(2)" class="py-2.5 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 cursor-pointer">
            <span>Continue to Duration</span>
            <span>➔</span>
        </button>
    </div>
</div>
