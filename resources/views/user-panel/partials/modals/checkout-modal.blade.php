{{-- Modal 1: Subscription & Plan Transition Checkout Modal --}}
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-5">

            <!-- Modal Header with Smart Badges -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <template x-if="isSamePlan">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 dark:bg-blue-950 text-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-800 flex items-center gap-1">
                                <span>⚡ Current Plan</span>
                            </span>
                        </template>
                        <template x-if="!isSamePlan && hasActiveSubscription && quote && quote.action_type === 'upgrade'">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 flex items-center gap-1">
                                <span>🚀 Upgrade Plan</span>
                            </span>
                        </template>
                        <template x-if="!isSamePlan && hasActiveSubscription && quote && quote.action_type === 'downgrade'">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 flex items-center gap-1">
                                <span>🔄 Downgrade Plan</span>
                            </span>
                        </template>
                        <template x-if="!hasActiveSubscription">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center gap-1">
                                <span>✨ New Subscription</span>
                            </span>
                        </template>

                        <h3 class="text-base font-black text-slate-900 dark:text-white" x-text="selectedPlan ? selectedPlan.name : 'Plan Checkout'"></h3>
                    </div>

                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <template x-if="isSamePlan">
                            <span>Extend current expiration date or shift your billing period</span>
                        </template>
                        <template x-if="!isSamePlan && quote && quote.action_type === 'upgrade'">
                            <span>Upgrade to unlock more MRU workspaces and higher consumer quotas</span>
                        </template>
                        <template x-if="!isSamePlan && quote && quote.action_type === 'downgrade'">
                            <span>Switch to a smaller plan with instant prorated wallet refund</span>
                        </template>
                        <template x-if="!hasActiveSubscription">
                            <span>Configure your duration and activate your subscription</span>
                        </template>
                    </p>
                </div>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold p-1">✕</button>
            </div>

            <!-- Top Stepper Bar (Unified 3-Step Wizard) -->
            <div class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 dark:bg-slate-800/80 rounded-2xl text-xs font-semibold">
                <!-- Step 1 Button -->
                <button type="button" 
                        @click="goToStep(1)" 
                        :class="currentStep === 1 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : (currentStep > 1 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'text-slate-400 dark:text-slate-500 opacity-60 cursor-not-allowed')"
                        class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5 text-center font-bold cursor-pointer">
                    <span class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center font-mono"
                          :class="currentStep === 1 ? 'bg-white text-indigo-700' : (currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500')">
                        <template x-if="currentStep > 1"><span>✓</span></template>
                        <template x-if="currentStep <= 1"><span>1</span></template>
                    </span>
                    <span class="truncate" x-text="isSamePlan ? '1. Action' : '1. Duration'"></span>
                </button>

                <!-- Step 2 Button -->
                <button type="button" 
                        @click="currentStep >= 2 && goToStep(2)" 
                        :disabled="currentStep < 2"
                        :class="currentStep === 2 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : (currentStep > 2 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'text-slate-400 dark:text-slate-500 opacity-60 cursor-not-allowed')"
                        class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5 text-center font-bold">
                    <span class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center font-mono"
                          :class="currentStep === 2 ? 'bg-white text-indigo-700' : (currentStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500')">
                        <template x-if="currentStep > 2"><span>✓</span></template>
                        <template x-if="currentStep <= 2"><span>2</span></template>
                    </span>
                    <span class="truncate" x-text="isSamePlan ? '2. Duration' : '2. Comparison'"></span>
                </button>

                <!-- Step 3 Button -->
                <button type="button" 
                        @click="currentStep === 3 && goToStep(3)" 
                        :disabled="currentStep < 3 || mruConflict"
                        :class="currentStep === 3 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 dark:text-slate-500 opacity-60 cursor-not-allowed'"
                        class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5 text-center font-bold">
                    <span class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center font-mono"
                          :class="currentStep === 3 ? 'bg-white text-indigo-700' : 'bg-slate-200 dark:bg-slate-700 text-slate-500'">
                        <span>3</span>
                    </span>
                    <span class="truncate">3. Payment</span>
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="isLoadingQuote" class="py-8 text-center space-y-3">
                <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Loading plan quote & pricing...</p>
            </div>

            <!-- Content Area when Quote is Ready -->
            <div x-show="!isLoadingQuote && quote" class="space-y-4">
                <!-- FLOW A: CURRENT ACTIVE PLAN -->
                <template x-if="isSamePlan">
                    <div class="space-y-4">
                        @include('user-panel.partials.modals.step-action-intent')
                        @include('user-panel.partials.modals.step-active-duration')
                        @include('user-panel.partials.modals.step-payment')
                    </div>
                </template>

                <!-- FLOW B: UPGRADE / DOWNGRADE / NEW SUBSCRIPTION -->
                <template x-if="!isSamePlan">
                    <div class="space-y-4">
                        @include('user-panel.partials.modals.step-transition-duration')
                        @include('user-panel.partials.modals.step-transition-comparison')
                        @include('user-panel.partials.modals.step-payment')
                    </div>
                </template>
            </div>

        </div>
    </div>
</div>
