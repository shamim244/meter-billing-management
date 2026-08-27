<x-user-panel-layout>
    <x-slot name="header">
        Subscription & Storage Allocation
    </x-slot>

    <div class="space-y-8" x-data="{
        showModal: false,
        isLoadingQuote: false,
        quote: null,
        selectedPlan: null,
        selectedDuration: null,
        selectedActionMode: 'extend',
        currentStep: 1,
        hasActiveSubscription: {{ $activeSubscription ? 'true' : 'false' }},
        walletBalance: {{ (float) $walletBalance }},
        isProcessingWallet: false,
        walletError: null,
        mruConflict: false,
        activeMrus: [],
        excessMrus: 0,
        newPlanQuota: 0,
        isLockingMru: false,
        lockSuccessMsg: null,
        showReceiptModal: false,
        receiptData: null,

        get availableDurations() {
            return (this.selectedPlan && this.selectedPlan.durations) ? this.selectedPlan.durations : [];
        },

        get isSamePlan() {
            const currentPlanId = {{ $activeSubscription ? $activeSubscription->plan_id : 'null' }};
            return !!(currentPlanId && this.selectedPlan && currentPlanId === this.selectedPlan.id);
        },

        get selectedDurationPrice() {
            if (this.quote && this.quote.action_type === 'upgrade') {
                return parseFloat(this.quote.final_amount) || 0;
            }
            if (this.quote && this.quote.action_type === 'downgrade') {
                return 0;
            }
            return this.selectedDuration ? (parseFloat(this.selectedDuration.final_price) || 0) : 0;
        },

        async openCheckoutModal(plan, duration) {
            this.selectedPlan = plan;
            this.selectedDuration = duration || (plan.durations && plan.durations.length ? plan.durations[0] : null);
            this.walletError = null;
            this.mruConflict = false;
            this.activeMrus = [];
            this.excessMrus = 0;
            this.newPlanQuota = 0;
            this.lockSuccessMsg = null;
            this.isProcessingWallet = false;
            this.quote = null;
            this.isLoadingQuote = true;
            this.showModal = true;

            // 🌟 2. Smart Pre-Selection
            const currentPlanId = {{ $activeSubscription ? $activeSubscription->plan_id : 'null' }};
            if (this.hasActiveSubscription && currentPlanId && currentPlanId === plan.id) {
                // Current Active Plan -> 3-Step Wizard starts at Step 1 (Extend)
                this.selectedActionMode = 'extend';
                this.currentStep = 1;
            } else if (this.hasActiveSubscription) {
                // Different Plan -> Direct Transition/Checkout
                this.selectedActionMode = 'shift';
                this.currentStep = 1;
            } else {
                // New Subscription
                this.selectedActionMode = 'new';
                this.currentStep = 1;
            }

            await this.fetchQuote(this.selectedActionMode);
        },

        async selectDuration(duration) {
            if (this.selectedDuration && this.selectedDuration.id === duration.id) return;
            this.selectedDuration = duration;
            await this.fetchQuote(this.selectedActionMode);
        },

        async switchActionMode(mode) {
            if (this.selectedActionMode === mode) return;
            this.selectedActionMode = mode;
            await this.fetchQuote(mode);
        },

        goToStep(step) {
            if (step === 3 && this.mruConflict) return;
            this.currentStep = step;
        },

        async fetchQuote(mode) {
            if (!this.selectedPlan || !this.selectedDuration) return;
            this.isLoadingQuote = true;
            this.walletError = null;
            try {
                const res = await fetch(`/subscription/quote/${this.selectedPlan.id}/${this.selectedDuration.id}?action_mode=${mode}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.quote = data;
                    if (data.action_type === 'downgrade' && data.downgrade_eligibility && !data.downgrade_eligibility.eligible) {
                        this.mruConflict = true;
                        this.activeMrus = data.downgrade_eligibility.active_mrus || [];
                        this.excessMrus = data.downgrade_eligibility.excess_mrus || 0;
                        this.newPlanQuota = data.downgrade_eligibility.new_plan_quota || 0;
                    } else {
                        this.mruConflict = false;
                    }
                } else {
                    this.walletError = data.message || 'Failed to calculate quote.';
                }
            } catch (err) {
                this.walletError = 'Failed to load plan quote.';
            } finally {
                this.isLoadingQuote = false;
            }
        },

        async confirmWalletPayment() {
            if (!this.selectedPlan || !this.selectedDuration) return;
            this.isProcessingWallet = true;
            this.walletError = null;
            this.lockSuccessMsg = null;

            try {
                const response = await fetch('{{ route('subscription.subscribe_wallet', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: this.selectedPlan.id,
                        duration_id: this.selectedDuration.id,
                        action_mode: this.selectedActionMode
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    if (data.ineligible_mrus) {
                        this.mruConflict = true;
                        this.activeMrus = data.active_mrus || [];
                        this.excessMrus = data.excess_mrus || 0;
                        this.newPlanQuota = data.new_plan_quota || 0;
                    }
                    throw new Error(data.message || 'Wallet payment failed.');
                }

                // Show Receipt Modal
                this.receiptData = {
                    planName: this.selectedPlan.name,
                    actionType: this.quote?.action_type || (this.selectedActionMode === 'extend' ? 'extend' : 'shift'),
                    message: data.message,
                    amountPaid: this.quote?.final_amount || 0,
                    amountCredited: this.quote?.prorated_credit || 0,
                    duration: this.selectedDuration ? (this.selectedDuration.formatted_duration || this.selectedDuration.name || ((this.selectedDuration.duration_value || this.selectedDuration.duration_months) + (this.selectedDuration.duration_unit === 'day' ? ' Days' : ' Month(s)'))) : '',
                };
                this.showModal = false;
                this.showReceiptModal = true;
            } catch (err) {
                this.walletError = err.message;
            } finally {
                this.isProcessingWallet = false;
            }
        },

        async lockMruFromModal(mruId) {
            this.isLockingMru = true;
            this.lockSuccessMsg = null;
            try {
                const res = await fetch('/mrus/' + mruId + '/lock', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ reason: 'plan_downgrade' })
                });
                const d = await res.json();
                if (d.success) {
                    this.activeMrus = this.activeMrus.filter(m => m.id !== mruId);
                    this.excessMrus = Math.max(0, this.excessMrus - 1);
                    this.lockSuccessMsg = d.message;
                    if (this.excessMrus <= 0) {
                        this.mruConflict = false;
                        this.walletError = null;
                    }
                } else {
                    this.walletError = d.message || 'Failed to lock MRU.';
                }
            } catch (e) {
                this.walletError = e.message;
            } finally {
                this.isLockingMru = false;
            }
        },

        get directPurchaseUrl() {
            if (!this.selectedPlan || !this.selectedDuration) return '#';
            return `/subscription/purchase/${this.selectedPlan.id}/${this.selectedDuration.id}?action_mode=${this.selectedActionMode}`;
        }
    }">
        <!-- Session Flash Messages -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                <span>{{ session('success') }}</span>
                <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 font-bold">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                <span>❌ {{ session('error') }}</span>
                <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 font-bold">✕</button>
            </div>
        @endif

        @if(session('info'))
            <div class="p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800/60 text-indigo-800 dark:text-indigo-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                <span>ℹ️ {{ session('info') }}</span>
                <button @click="$el.parentElement.remove()" class="text-indigo-600 dark:text-indigo-400 font-bold">✕</button>
            </div>
        @endif

        <!-- Storage & Quota Status Hero -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800">
                            Current Plan: {{ $activeSubscription ? $activeSubscription->plan?->name : 'No Active Plan' }}
                        </span>
                        @if($activeSubscription)
                            <span class="text-xs text-slate-400">•</span>
                            <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                Expires: {{ $activeSubscription->billing_end ? $activeSubscription->billing_end->format('M d, Y') : 'Active' }}
                            </span>
                        @endif
                        <span class="text-xs text-slate-400">•</span>
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                            Wallet Balance: ₹{{ number_format($walletBalance, 2) }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Subscription & Quota Management</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl leading-relaxed">
                        Scale your MRU workspaces and consumer audit capacity. Choose from our duration-discounted tiers below.
                    </p>
                </div>

                <div class="bg-slate-50 dark:bg-slate-950/60 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800/80 min-w-[260px] space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 dark:text-slate-400 font-semibold">Active MRU Quota</span>
                        <span class="font-mono font-black text-blue-600 dark:text-cyan-400">
                            {{ $stats['mru_count'] }} / {{ $activeSubscription ? $activeSubscription->included_mrus_locked : '0' }}
                        </span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        @php
                            $includedMrus = $activeSubscription ? $activeSubscription->included_mrus_locked : 1;
                            $pct = $includedMrus > 0 ? min(100, round(($stats['mru_count'] / $includedMrus) * 100)) : 100;
                        @endphp
                        <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-400 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] font-mono text-slate-500 dark:text-slate-400">
                        <span>{{ $stats['consumer_count'] }} Total Consumers</span>
                        <span>{{ $stats['bills_count'] }} Bills Parsed</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Subscription Plans (From Database) -->
        <div>
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Available Subscription Plans</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Select a plan and choose your preferred billing duration.</p>
                </div>
                <a href="{{ route('payments.create') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                    <span>👛 Add Funds to Wallet →</span>
                </a>
            </div>

            @if($plans->isEmpty())
                <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 text-center space-y-3">
                    <span class="text-3xl">📦</span>
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">No active plans currently configured.</h3>
                    <p class="text-xs text-slate-400">Please check back soon or contact support.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($plans as $plan)
                        @php
                            $isCurrentPlan = $activeSubscription && $activeSubscription->plan_id === $plan->id;
                            $durations = $plan->durations->sortBy('duration_months');
                            $defaultDuration = $durations->first();
                        @endphp

                        <div x-data="{
                            activeDurationIndex: 0,
                            durations: {{ $durations->values()->toJson() }},
                            get currentDuration() {
                                return this.durations[this.activeDurationIndex] || null;
                            },
                            get currentPrice() {
                                return this.currentDuration ? this.currentDuration.final_price : {{ $plan->base_price }};
                            },
                            get currentDiscount() {
                                return this.currentDuration ? this.currentDuration.discount_percent : 0;
                            }
                        }" class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border flex flex-col justify-between relative transition duration-200 {{ $isCurrentPlan ? 'border-indigo-600 dark:border-indigo-500 shadow-xl ring-2 ring-indigo-500/20' : 'border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md' }}">
                            @if($isCurrentPlan)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-md">
                                    Current Active Plan
                                </div>
                            @endif

                            <div>
                                <div class="flex items-center justify-between">
                                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $plan->name }}</h3>
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                        {{ $plan->included_mrus }} MRUs
                                    </span>
                                </div>

                                @if($plan->description)
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 line-clamp-2">
                                        {{ $plan->description }}
                                    </p>
                                @endif

                                <!-- Duration Selector Tabs / Pills -->
                                @if($durations->isNotEmpty())
                                    <div class="mt-4">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1.5">Select Duration:</span>
                                        <div class="flex flex-wrap gap-1.5 bg-slate-100 dark:bg-slate-950 p-1.5 rounded-xl">
                                            <template x-for="(d, idx) in durations" :key="d.id">
                                                <button type="button" @click="activeDurationIndex = idx" :class="activeDurationIndex === idx ? 'bg-indigo-600 text-white font-black shadow-sm border border-indigo-600' : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white font-bold border border-slate-200 dark:border-slate-800'" class="py-1.5 px-2.5 rounded-lg text-[11px] transition text-center flex items-center gap-1 cursor-pointer">
                                                    <span x-text="(d.duration_value || d.duration_months) + (d.duration_unit === 'day' ? 'd' : 'm')"></span>
                                                    <span x-show="d.discount_percent > 0" :class="activeDurationIndex === idx ? 'text-amber-200 font-black' : 'text-amber-600 dark:text-amber-400 font-black'" class="text-[9px]" x-text="'-' + d.discount_percent + '%'"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                @endif

                                <!-- Dynamic Price Display -->
                                <div class="mt-4 flex items-baseline gap-1.5">
                                    <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tight font-mono">
                                        ₹<span x-text="currentPrice.toLocaleString('en-IN')"></span>
                                    </span>
                                    <span class="text-xs text-slate-400 font-medium">
                                        / <span x-text="currentDuration ? (currentDuration.name || (currentDuration.duration_value || currentDuration.duration_months) + (currentDuration.duration_unit === 'day' ? ' Days' : ' Months')) : 'Period'"></span>
                                    </span>
                                </div>

                                <!-- Plan Quotas & Specs -->
                                <div class="mt-4 p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800/80 text-xs space-y-1.5">
                                    <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                        <span>Included MRUs:</span>
                                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-black">{{ $plan->included_mrus }}</span>
                                    </div>
                                    <div class="font-bold text-slate-800 dark:text-slate-200 flex items-center justify-between">
                                        <span>Included Consumers / Cycle:</span>
                                        <span class="font-mono text-indigo-600 dark:text-indigo-400 font-black">{{ number_format($plan->included_consumers) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between border-t border-slate-200/60 dark:border-slate-700/60 pt-1.5">
                                        <span>Extra MRU Rate:</span>
                                        <span class="font-mono">₹{{ number_format($plan->extra_mru_rate, 2) }}</span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                                        <span>Extra Consumer Rate:</span>
                                        <span class="font-mono">₹{{ number_format($plan->extra_consumer_rate, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
                                @if($isCurrentPlan)
                                    <button type="button" @click="openCheckoutModal(Object.assign({{ $plan->toJson() }}, { durations: {{ $durations->values()->toJson() }} }), currentDuration)" class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-100 font-bold text-xs shadow-sm transition text-center flex items-center justify-center gap-1 cursor-pointer">
                                        <span>Manage / Extend Plan</span>
                                        <span>→</span>
                                    </button>
                                @else
                                    <button type="button" @click="openCheckoutModal(Object.assign({{ $plan->toJson() }}, { durations: {{ $durations->values()->toJson() }} }), currentDuration)" class="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-indigo-600/20 transition text-center cursor-pointer">
                                        <span>{{ $activeSubscription ? 'Change / Upgrade Plan' : 'Subscribe Now' }}</span>
                                        <span>→</span>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Subscription & Plan Transition History Table -->
        @if(isset($subscriptionHistory) && $subscriptionHistory->isNotEmpty())
            <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>📜</span> Subscription & Plan Transition History
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Audit log of active and previous plan durations, renewals, upgrades, and downgrades.
                        </p>
                    </div>
                    <a href="{{ route('wallet.index') }}" class="text-xs font-bold text-indigo-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
                        <span>👛 View Wallet Ledger →</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                                <th class="py-3 px-3">Plan Name</th>
                                <th class="py-3 px-3">Duration</th>
                                <th class="py-3 px-3">Billing Period</th>
                                <th class="py-3 px-3">Locked Quota</th>
                                <th class="py-3 px-3 text-right">Price Paid</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                            @foreach($subscriptionHistory as $histSub)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                        {{ $histSub->plan?->name ?? 'Custom Plan' }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-300 font-mono">
                                        {{ $histSub->formatted_duration }}
                                    </td>
                                    <td class="py-3 px-3 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                        {{ $histSub->billing_start ? $histSub->billing_start->format('M d, Y') : '—' }}
                                        <span class="text-slate-400">→</span>
                                        <span class="{{ $histSub->billing_end && $histSub->billing_end > now() ? 'text-emerald-600 dark:text-emerald-400 font-bold' : '' }}">
                                            {{ $histSub->billing_end ? $histSub->billing_end->format('M d, Y') : '—' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                        {{ $histSub->included_mrus_locked }} MRUs / {{ number_format($histSub->included_consumers_locked) }} CAs
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                        ₹{{ number_format($histSub->base_price_paid, 2) }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        @if($histSub->status === 'active' && $histSub->billing_end > now())
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                ● Active
                                            </span>
                                        @elseif($histSub->status === 'upgraded')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                                🚀 Upgraded
                                            </span>
                                        @elseif($histSub->status === 'downgraded')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                🔄 Downgraded
                                            </span>
                                        @elseif($histSub->status === 'migrated')
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                                Migrated
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                                Expired
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Modal 1: Subscription & Plan Transition Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-5">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <template x-if="isSamePlan">
                                    <span>Manage Current Plan (<span x-text="selectedPlan ? selectedPlan.name : ''"></span>)</span>
                                </template>
                                <template x-if="!isSamePlan && hasActiveSubscription">
                                    <span x-text="(quote && quote.action_type === 'downgrade' ? '🔄 Switch to ' : '🚀 Upgrade to ') + (selectedPlan ? selectedPlan.name : '')"></span>
                                </template>
                                <template x-if="!hasActiveSubscription">
                                    <span>💳 Subscribe to <span x-text="selectedPlan ? selectedPlan.name : ''"></span></span>
                                </template>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                <template x-if="isSamePlan">
                                    <span>Extend current expiration date or shift your billing period</span>
                                </template>
                                <template x-if="!isSamePlan">
                                    <span>Review quota capacity adjustments and complete subscription</span>
                                </template>
                            </p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold p-1">✕</button>
                    </div>

                    <!-- 🌟 1. Modern Top Stepper Bar: ONLY for Current Active Plan (isSamePlan) -->
                    <div x-show="isSamePlan" class="grid grid-cols-3 gap-2 p-1.5 bg-slate-100 dark:bg-slate-800/80 rounded-2xl text-xs font-semibold">
                        <!-- Step 1: Action -->
                        <button type="button" 
                                @click="goToStep(1)" 
                                :class="currentStep === 1 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : (currentStep > 1 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'text-slate-400 dark:text-slate-500 opacity-60 cursor-not-allowed')"
                                class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5 text-center font-bold">
                            <span class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center font-mono"
                                  :class="currentStep === 1 ? 'bg-white text-indigo-700' : (currentStep > 1 ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500')">
                                <template x-if="currentStep > 1"><span>✓</span></template>
                                <template x-if="currentStep <= 1"><span>1</span></template>
                            </span>
                            <span class="truncate">1. Action</span>
                        </button>

                        <!-- Step 2: Duration -->
                        <button type="button" 
                                @click="currentStep >= 2 && goToStep(2)"
                                :class="currentStep === 2 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : (currentStep > 2 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800' : 'text-slate-600 dark:text-slate-400')"
                                class="py-2 px-2.5 rounded-xl transition flex items-center justify-center gap-1.5 text-center font-bold">
                            <span class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center font-mono"
                                  :class="currentStep === 2 ? 'bg-white text-indigo-700' : (currentStep > 2 ? 'bg-emerald-600 text-white' : 'bg-slate-200 dark:bg-slate-700 text-slate-500')">
                                <template x-if="currentStep > 2"><span>✓</span></template>
                                <template x-if="currentStep <= 2"><span>2</span></template>
                            </span>
                            <span class="truncate">2. Duration</span>
                        </button>

                        <!-- Step 3: Payment -->
                        <button type="button" 
                                @click="currentStep === 3 && goToStep(3)"
                                :class="currentStep === 3 ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-600 dark:text-slate-400'"
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

                    <div x-show="!isLoadingQuote && quote" class="space-y-4">

                        <!-- ======================================================= -->
                        <!-- A. 3-STEP WIZARD (ONLY FOR CURRENT ACTIVE PLAN)        -->
                        <!-- ======================================================= -->
                        <template x-if="isSamePlan">
                            <div class="space-y-4">
                                <!-- STEP 1: CHOOSE ACTION INTENT -->
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

                                <!-- STEP 2: CHOOSE DURATION & REVIEW MATH -->
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

                                        <!-- Proration details if shifting with balance adjustment -->
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

                                        <!-- Net Payable / Refund Highlight -->
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

                                <!-- STEP 3: PAYMENT / CONFIRMATION -->
                                <div x-show="currentStep === 3" class="space-y-4">
                                    <!-- Downgrade Refund Theme -->
                                    <template x-if="quote.action_type === 'downgrade'">
                                        <div class="space-y-4">
                                            <div class="p-5 rounded-3xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-center space-y-2">
                                                <span class="text-3xl">💰</span>
                                                <h4 class="text-sm font-black text-emerald-900 dark:text-emerald-100">Wallet Credit Refund</h4>
                                                <div class="text-3xl font-black font-mono text-emerald-700 dark:text-emerald-300">
                                                    +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span>
                                                </div>
                                                <p class="text-xs text-emerald-800 dark:text-emerald-300 max-w-xs mx-auto">
                                                    You will NOT be charged. The unused balance of your previous plan will be deposited into your wallet balance instantly.
                                                </p>
                                            </div>

                                            <div x-show="walletError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-800 text-xs font-bold">
                                                <span>❌ </span><span x-text="walletError"></span>
                                            </div>

                                            <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-sm shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                                                <span x-show="!isProcessingWallet">✓ Confirm & Receive +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span></span>
                                                <span x-show="isProcessingWallet" x-cloak>Applying...</span>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Normal Payment Theme -->
                                    <template x-if="quote.action_type !== 'downgrade'">
                                        <div class="space-y-3">
                                            <!-- Summary Banner -->
                                            <div class="p-4 rounded-2xl bg-indigo-50/90 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 flex items-center justify-between">
                                                <div>
                                                    <span class="text-[10px] uppercase tracking-wider font-bold text-indigo-700 dark:text-indigo-300 block">Total Payable</span>
                                                    <span class="text-2xl font-black font-mono text-indigo-950 dark:text-white">
                                                        ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span>
                                                    </span>
                                                </div>
                                                <div class="text-right text-xs">
                                                    <span class="text-indigo-800 dark:text-indigo-300 block font-medium">Your Wallet Balance</span>
                                                    <strong class="font-mono text-slate-900 dark:text-white text-sm font-bold">₹<span x-text="walletBalance.toLocaleString('en-IN')"></span></strong>
                                                </div>
                                            </div>

                                            <div x-show="walletError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-800 text-xs font-bold">
                                                <span>❌ </span><span x-text="walletError"></span>
                                            </div>

                                            <!-- Pay from Wallet -->
                                            <div class="p-4 rounded-2xl border-2 transition" :class="walletBalance >= quote.final_amount ? 'border-emerald-500/40 bg-emerald-50/30 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950'">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-xl">👛</span>
                                                        <div>
                                                            <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay from Wallet Balance</h4>
                                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Instant activation with no payment redirect.</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <template x-if="walletBalance >= quote.final_amount">
                                                        <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                                                            <span x-show="!isProcessingWallet">✓ Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> from Wallet Balance</span>
                                                            <span x-show="isProcessingWallet" x-cloak>Processing Payment...</span>
                                                        </button>
                                                    </template>

                                                    <template x-if="walletBalance < quote.final_amount">
                                                        <div class="space-y-2">
                                                            <p class="text-[11px] text-rose-500 font-semibold">
                                                                Insufficient balance (Deficit: ₹<span x-text="(quote.final_amount - walletBalance).toLocaleString('en-IN')"></span>).
                                                            </p>
                                                            <a href="{{ route('payments.create') }}" class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 font-bold text-xs flex items-center justify-center gap-1 hover:bg-indigo-100 transition">
                                                                <span>👛 Top-Up Wallet First →</span>
                                                            </a>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Pay Directly -->
                                            <div class="p-3.5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-lg">💳</span>
                                                    <div>
                                                        <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay Directly</h4>
                                                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Gateway / UPI QR / Bank Transfer</p>
                                                    </div>
                                                </div>
                                                <a :href="directPurchaseUrl" class="py-2 px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs shadow-xs transition">
                                                    <span>Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> →</span>
                                                </a>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Back to Step 2 Button -->
                                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex justify-start">
                                        <button type="button" @click="goToStep(2)" class="py-2 px-4 rounded-xl bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold text-xs transition cursor-pointer">
                                            <span>⬅ Back to Duration</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- ======================================================= -->
                        <!-- B. DIRECT 1-SCREEN CHECKOUT (FOR OTHER / NEW PLANS)     -->
                        <!-- ======================================================= -->
                        <template x-if="!isSamePlan">
                            <div class="space-y-4">
                                <!-- Duration Selector Cards -->
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400 block mb-1.5">
                                        Select Billing Duration:
                                    </span>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                        <template x-for="d in availableDurations" :key="d.id">
                                            <button type="button" 
                                                    @click="selectDuration(d)"
                                                    :class="selectedDuration && selectedDuration.id === d.id ? 'border-indigo-600 dark:border-indigo-500 bg-indigo-600 text-white shadow-lg shadow-indigo-600/30 ring-2 ring-indigo-400/50' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 hover:border-indigo-300 dark:hover:border-indigo-700'"
                                                    class="p-3 rounded-2xl border text-left transition flex flex-col justify-between relative cursor-pointer">
                                                <div class="flex items-center justify-between gap-1">
                                                    <span class="text-xs font-bold" :class="selectedDuration && selectedDuration.id === d.id ? 'text-white' : 'text-slate-900 dark:text-white'" x-text="d.name || (d.duration_value || d.duration_months) + (d.duration_unit === 'day' ? ' Days' : ' Month(s)')"></span>
                                                    <template x-if="d.discount_percent > 0">
                                                        <span class="text-[9px] font-black px-1.5 py-0.5 rounded-md" 
                                                              :class="selectedDuration && selectedDuration.id === d.id ? 'bg-amber-400 text-amber-950 font-black' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800 font-bold'"
                                                              x-text="d.discount_percent + '% OFF'"></span>
                                                    </template>
                                                </div>
                                                <div class="mt-2 flex items-baseline justify-between">
                                                    <div class="text-sm font-mono font-black" :class="selectedDuration && selectedDuration.id === d.id ? 'text-white' : 'text-slate-900 dark:text-white'">
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

                                <!-- Capacity & Quota Comparison Card -->
                                <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 space-y-2 text-xs">
                                    <div class="text-[10px] uppercase tracking-wider font-bold text-slate-500 dark:text-slate-400">
                                        Capacity & Quota Comparison
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 text-xs">
                                        <div>
                                            <span class="text-[11px] text-slate-400 block">MRU Workspaces</span>
                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 mt-0.5">
                                                <template x-if="quote.current_subscription">
                                                    <span class="text-slate-400 line-through" x-text="quote.current_subscription.included_mrus + ' MRUs'"></span>
                                                </template>
                                                <span class="text-indigo-600 dark:text-cyan-400 font-mono font-black" x-text="quote.plan.included_mrus + ' MRUs'"></span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-[11px] text-slate-400 block">Monthly Consumers</span>
                                            <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5 mt-0.5">
                                                <template x-if="quote.current_subscription">
                                                    <span class="text-slate-400 line-through" x-text="quote.current_subscription.included_consumers.toLocaleString()"></span>
                                                </template>
                                                <span class="text-indigo-600 dark:text-cyan-400 font-mono font-black" x-text="quote.plan.included_consumers.toLocaleString()"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Compact Mathematical Proration & Validity Preview Box -->
                                <div class="p-3.5 rounded-2xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-2 text-xs">
                                    <div class="flex items-center justify-between font-semibold">
                                        <span class="text-slate-600 dark:text-slate-300">📅 New Plan Validity:</span>
                                        <strong class="font-mono text-slate-900 dark:text-white text-xs font-bold">
                                            <span x-text="quote.start_date"></span> → <span x-text="quote.end_date"></span>
                                        </strong>
                                    </div>

                                    <!-- Proration details if shifting with balance adjustment -->
                                    <template x-if="quote.proration">
                                        <div class="space-y-1.5 pt-2 border-t border-slate-200 dark:border-slate-700 text-xs">
                                            <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                                                <span>Unused Days Credit (<span x-text="quote.proration.days_remaining + ' of ' + quote.proration.total_days_in_cycle + ' days'"></span>):</span>
                                                <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'-₹' + quote.proration.old_plan_credit.toLocaleString('en-IN')"></span>
                                            </div>
                                            <div class="flex items-center justify-between text-slate-700 dark:text-slate-300">
                                                <span>Target Plan Cost:</span>
                                                <span class="font-mono font-bold text-slate-900 dark:text-white" x-text="'₹' + quote.proration.new_plan_cost.toLocaleString('en-IN')"></span>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Net Payable / Refund Highlight -->
                                    <div class="pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
                                        <span class="font-bold uppercase tracking-wider text-[11px]" :class="quote.action_type === 'downgrade' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-700 dark:text-slate-300'">
                                            <span x-text="quote.action_type === 'downgrade' ? '💰 Prorated Wallet Refund:' : 'Total Amount to Pay:'"></span>
                                        </span>
                                        <span class="text-lg font-black font-mono" :class="quote.action_type === 'downgrade' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-950 dark:text-white'">
                                            <span x-text="quote.action_type === 'downgrade' ? '+₹' + quote.prorated_credit.toLocaleString('en-IN') : '₹' + quote.final_amount.toLocaleString('en-IN')"></span>
                                        </span>
                                    </div>
                                </div>

                                <!-- MRU Quota Conflict (if downgrade requires locking MRUs) -->
                                <div x-show="mruConflict" x-cloak class="p-3.5 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/60 space-y-2.5">
                                    <div class="flex items-center gap-2 text-xs font-bold text-amber-900 dark:text-amber-200">
                                        <span>⚠️</span>
                                        <span>Active MRUs Exceed Quota (Lock <strong x-text="excessMrus"></strong> MRU to continue)</span>
                                    </div>
                                    <div class="space-y-1.5 max-h-32 overflow-y-auto pr-1">
                                        <template x-for="m in activeMrus" :key="m.id">
                                            <div class="flex items-center justify-between p-2 rounded-xl bg-white dark:bg-slate-900 border border-amber-200/70 dark:border-slate-800 text-xs">
                                                <span class="font-mono font-bold text-blue-600 dark:text-cyan-400" x-text="m.code + ' - ' + m.name"></span>
                                                <button type="button" @click="lockMruFromModal(m.id)" :disabled="isLockingMru" class="px-2 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold transition disabled:opacity-50 cursor-pointer">
                                                    <span>🔒 Lock</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Payment Actions Section for Direct Modal -->
                                <template x-if="quote.action_type === 'downgrade'">
                                    <div class="space-y-3">
                                        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-center space-y-1.5">
                                            <span class="text-2xl">💰</span>
                                            <h4 class="text-xs font-black text-emerald-900 dark:text-emerald-100">Wallet Credit Refund: +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span></h4>
                                            <p class="text-[11px] text-emerald-800 dark:text-emerald-300">
                                                The unused balance of your previous plan will be credited to your wallet immediately.
                                            </p>
                                        </div>

                                        <div x-show="walletError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-800 text-xs font-bold">
                                            <span>❌ </span><span x-text="walletError"></span>
                                        </div>

                                        <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-3 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                                            <span x-show="!isProcessingWallet">✓ Confirm Switch & Receive +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span></span>
                                            <span x-show="isProcessingWallet" x-cloak>Applying Switch...</span>
                                        </button>
                                    </div>
                                </template>

                                <template x-if="quote.action_type !== 'downgrade'">
                                    <div class="space-y-3">
                                        <div x-show="walletError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 text-rose-800 text-xs font-bold">
                                            <span>❌ </span><span x-text="walletError"></span>
                                        </div>

                                        <!-- Pay from Wallet -->
                                        <div class="p-3.5 rounded-2xl border-2 transition" :class="walletBalance >= quote.final_amount ? 'border-emerald-500/40 bg-emerald-50/30 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950'">
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-base">👛</span>
                                                    <span class="text-xs font-bold text-slate-900 dark:text-white">Pay from Wallet</span>
                                                </div>
                                                <strong class="font-mono text-xs text-slate-900 dark:text-white">Balance: ₹<span x-text="walletBalance.toLocaleString('en-IN')"></span></strong>
                                            </div>

                                            <template x-if="walletBalance >= quote.final_amount">
                                                <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50 cursor-pointer">
                                                    <span x-show="!isProcessingWallet">✓ Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> from Wallet</span>
                                                    <span x-show="isProcessingWallet" x-cloak>Processing Payment...</span>
                                                </button>
                                            </template>

                                            <template x-if="walletBalance < quote.final_amount">
                                                <div class="space-y-1.5">
                                                    <p class="text-[11px] text-rose-500 font-semibold">
                                                        Insufficient balance (Deficit: ₹<span x-text="(quote.final_amount - walletBalance).toLocaleString('en-IN')"></span>).
                                                    </p>
                                                    <a href="{{ route('payments.create') }}" class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 font-bold text-xs flex items-center justify-center gap-1 hover:bg-indigo-100 transition">
                                                        <span>👛 Top-Up Wallet First →</span>
                                                    </a>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Pay Directly -->
                                        <div class="p-3 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <span class="text-base">💳</span>
                                                <div>
                                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay Directly</h4>
                                                    <p class="text-[10px] text-slate-500 dark:text-slate-400">Gateway / UPI QR / Bank Transfer</p>
                                                </div>
                                            </div>
                                            <a :href="directPurchaseUrl" class="py-2 px-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs shadow-xs transition">
                                                <span>Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> →</span>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modal 2: Post-Action Confirmation & Receipt Modal -->
        <div x-show="showReceiptModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="receipt-modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showReceiptModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showReceiptModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-5 text-center">

                    <div class="w-16 h-16 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-3xl mx-auto shadow-inner">
                        ✓
                    </div>

                    <div class="space-y-1">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">
                            <span x-text="receiptData?.actionType === 'downgrade' ? 'Plan Downgrade Applied!' : 'Plan Activated Successfully!'"></span>
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400" x-text="receiptData?.message"></p>
                    </div>

                    <!-- Receipt Details Box -->
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 text-xs space-y-2 text-left">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Activated Plan:</span>
                            <strong class="text-slate-900 dark:text-white" x-text="receiptData?.planName"></strong>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Duration:</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="receiptData?.duration"></span>
                        </div>
                        <template x-if="receiptData?.actionType === 'downgrade'">
                            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">Prorated Refund Credited:</span>
                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400" x-text="'+₹' + receiptData?.amountCredited?.toLocaleString('en-IN')"></span>
                            </div>
                        </template>
                        <template x-if="receiptData?.actionType !== 'downgrade'">
                            <div class="flex items-center justify-between pt-2 border-t border-slate-200 dark:border-slate-700">
                                <span class="text-slate-700 dark:text-slate-300 font-bold">Amount Paid:</span>
                                <span class="font-mono font-black text-slate-900 dark:text-white" x-text="'₹' + receiptData?.amountPaid?.toLocaleString('en-IN')"></span>
                            </div>
                        </template>
                    </div>

                    <div class="pt-2">
                        <button type="button" @click="window.location.reload()" class="w-full py-3 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs shadow-md transition">
                            ✓ Back to Subscriptions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-user-panel-layout>
