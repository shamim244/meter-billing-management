{{-- Step 2 (Flow B: Upgrade / Downgrade / New): Comparison & Proration Math + MRU Locking --}}
<div x-show="currentStep === 2" class="space-y-4">
    <!-- Side-by-Side Quota Comparison -->
    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-3">
        <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <span>Quota & Capacity Changes</span>
            <span :class="quote.action_type === 'upgrade' ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400'"
                  x-text="quote.action_type === 'upgrade' ? '🚀 Upgrading Capacity' : '🔄 Downsizing Capacity'"></span>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-1">
            <!-- MRU Comparison Card -->
            <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-bold uppercase block">MRU Workspaces</span>
                <div class="flex items-center gap-2 mt-1">
                    <template x-if="quote.current_subscription">
                        <span class="text-xs text-slate-400 line-through font-mono" x-text="quote.current_subscription.included_mrus + ' MRUs'"></span>
                    </template>
                    <span class="text-xs font-bold text-slate-400">➔</span>
                    <strong class="text-sm font-black font-mono" 
                            :class="quote.plan.included_mrus > (quote.current_subscription?.included_mrus || 0) ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400'"
                            x-text="quote.plan.included_mrus + ' MRUs'"></strong>
                </div>
            </div>

            <!-- Consumer Quota Comparison Card -->
            <div class="p-3 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800">
                <span class="text-[10px] text-slate-400 font-bold uppercase block">Monthly Consumers</span>
                <div class="flex items-center gap-2 mt-1">
                    <template x-if="quote.current_subscription">
                        <span class="text-xs text-slate-400 line-through font-mono" x-text="quote.current_subscription.included_consumers.toLocaleString()"></span>
                    </template>
                    <span class="text-xs font-bold text-slate-400">➔</span>
                    <strong class="text-sm font-black font-mono" 
                            :class="quote.plan.included_consumers > (quote.current_subscription?.included_consumers || 0) ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600 dark:text-amber-400'"
                            x-text="quote.plan.included_consumers.toLocaleString()"></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Mathematical Proration Breakdown & Validity Preview -->
    <div class="p-4 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2.5 text-xs">
        <div class="flex items-center justify-between font-semibold">
            <span class="text-slate-600 dark:text-slate-300">📅 New Plan Validity:</span>
            <strong class="font-mono text-slate-900 dark:text-white text-xs font-bold">
                <span x-text="quote.start_date"></span> → <span x-text="quote.end_date"></span>
            </strong>
        </div>

        <template x-if="quote.proration">
            <div class="space-y-1.5 pt-2.5 border-t border-slate-200 dark:border-slate-700 text-xs">
                <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                    <span>Unused Days Credit (<span x-text="quote.proration.days_remaining + ' of ' + quote.proration.total_days_in_cycle + ' days'"></span>):</span>
                    <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'-₹' + quote.proration.old_plan_credit.toLocaleString('en-IN')"></span>
                </div>
                <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                    <span>Target Plan Duration Price:</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + quote.proration.new_plan_cost.toLocaleString('en-IN')"></span>
                </div>
            </div>
        </template>

        <div class="pt-2.5 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
            <span class="font-bold uppercase tracking-wider text-[11px]" :class="quote.action_type === 'downgrade' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300'">
                <span x-text="quote.action_type === 'downgrade' ? '💰 Prorated Wallet Refund:' : 'Total Amount to Pay:'"></span>
            </span>
            <span class="text-lg font-black font-mono" :class="quote.action_type === 'downgrade' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-950 dark:text-white'">
                <span x-text="quote.action_type === 'downgrade' ? '+₹' + quote.prorated_credit.toLocaleString('en-IN') : '₹' + quote.final_amount.toLocaleString('en-IN')"></span>
            </span>
        </div>
    </div>

    <!-- MRU Quota Conflict (if downgrade requires locking MRUs) -->
    <div x-show="mruConflict" x-cloak class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-300 dark:border-amber-900 space-y-2.5">
        <div class="flex items-center gap-2 text-xs font-bold text-amber-900 dark:text-amber-200">
            <span class="text-base">⚠️</span>
            <span>Active MRUs Exceed Quota (Please Lock <strong x-text="excessMrus"></strong> MRU to proceed)</span>
        </div>
        <p class="text-[11px] text-amber-800 dark:text-amber-300">
            Your new plan allows up to <strong x-text="newPlanQuota"></strong> active MRUs. Lock the MRUs you don't need right now:
        </p>
        <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
            <template x-for="m in activeMrus" :key="m.id">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-200 dark:border-slate-800 text-xs">
                    <span class="font-mono font-bold text-blue-600 dark:text-cyan-400" x-text="m.code + ' - ' + m.name"></span>
                    <button type="button" @click="lockMruFromModal(m.id)" :disabled="isLockingMru" class="px-2.5 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold transition disabled:opacity-50 cursor-pointer">
                        <span>🔒 Lock</span>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <button type="button" @click="goToStep(1)" class="py-2.5 px-4 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
            <span>⬅ Back to Duration</span>
        </button>

        <button type="button" @click="goToStep(3)" :disabled="isLoadingQuote || mruConflict" class="py-2.5 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
            <span x-show="!mruConflict">Continue to Payment</span>
            <span x-show="mruConflict" x-cloak>Lock <span x-text="excessMrus"></span> MRUs to Continue</span>
            <span x-show="!mruConflict">➔</span>
        </button>
    </div>
</div>
