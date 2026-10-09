{{-- Step 2 (Flow A: Active Plan): Choose Duration & Review Math --}}
<div x-show="currentStep === 2" class="space-y-4">
    <div>
        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 block mb-1.5">
            Select Billing Duration:
        </span>
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
    </div>

    <!-- Compact 2-Line Math & Validity Preview Box -->
    <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
        <div class="flex items-center justify-between font-semibold">
            <span class="text-slate-600 dark:text-slate-300">📅 New Plan Validity:</span>
            <strong class="font-mono text-slate-900 dark:text-white text-xs font-bold">
                <span x-text="quote.start_date"></span> → <span x-text="quote.end_date"></span>
            </strong>
        </div>

        <template x-if="selectedActionMode === 'shift' && quote.proration">
            <div class="space-y-1.5 pt-2 border-t border-slate-200 dark:border-slate-700 text-xs">
                <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                    <span>Unused Days Credit (<span x-text="quote.proration.days_remaining + ' of ' + quote.proration.total_days_in_cycle + ' days'"></span>):</span>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'-₹' + quote.proration.old_plan_credit.toLocaleString('en-IN')"></span>
                </div>
                <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                    <span>Target Duration Cost:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + quote.proration.new_plan_cost.toLocaleString('en-IN')"></span>
                </div>
            </div>
        </template>

        <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <span class="font-bold uppercase tracking-wider text-[11px]" :class="quote.action_type === 'downgrade' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300'">
                <span x-text="quote.action_type === 'downgrade' ? '💰 Prorated Wallet Refund:' : 'Total Amount to Pay:'"></span>
            </span>
            <span class="text-lg font-black font-mono" :class="quote.action_type === 'downgrade' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-950 dark:text-white'">
                <span x-text="quote.action_type === 'downgrade' ? '+₹' + quote.prorated_credit.toLocaleString('en-IN') : '₹' + quote.final_amount.toLocaleString('en-IN')"></span>
            </span>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <button type="button" @click="goToStep(1)" class="py-2.5 px-4 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
            <span>⬅ Back to Action</span>
        </button>

        <button type="button" @click="goToStep(3)" :disabled="isLoadingQuote || mruConflict" class="py-2.5 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
            <span>Continue to Payment</span>
            <span>➔</span>
        </button>
    </div>
</div>
