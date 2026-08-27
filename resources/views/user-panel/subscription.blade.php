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
        selectedActionMode: 'shift',
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
            this.selectedDuration = duration;
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

            // Determine initial default action mode
            const currentPlanId = {{ $activeSubscription ? $activeSubscription->plan_id : 'null' }};
            const currentDurationMonths = {{ $activeSubscription ? ($activeSubscription->duration_months ?? 1) : 'null' }};
            if (currentPlanId && currentPlanId === plan.id && currentDurationMonths === (duration.duration_months || duration.duration_value)) {
                this.selectedActionMode = 'extend';
            } else {
                this.selectedActionMode = 'shift';
            }

            await this.fetchQuote(this.selectedActionMode);
        },

        async switchActionMode(mode) {
            if (this.selectedActionMode === mode) return;
            this.selectedActionMode = mode;
            await this.fetchQuote(mode);
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
                    duration: this.selectedDuration.formatted_duration || (this.selectedDuration.duration_months + ' Month(s)'),
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
            <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-brand-50 dark:bg-brand-950/80 text-brand-700 dark:text-cyan-300 border border-brand-200/60 dark:border-brand-800/60">
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
                        <span class="font-mono font-black text-brand-600 dark:text-cyan-400">
                            {{ $stats['mru_count'] }} / {{ $activeSubscription ? $activeSubscription->included_mrus_locked : '0' }}
                        </span>
                    </div>
                    <div class="w-full h-2 bg-slate-200 dark:bg-slate-800 rounded-full overflow-hidden">
                        @php
                            $includedMrus = $activeSubscription ? $activeSubscription->included_mrus_locked : 1;
                            $pct = $includedMrus > 0 ? min(100, round(($stats['mru_count'] / $includedMrus) * 100)) : 100;
                        @endphp
                        <div class="h-full bg-gradient-to-r from-brand-500 to-cyan-400 rounded-full transition-all duration-300" style="width: {{ $pct }}%"></div>
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
                        }" class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border flex flex-col justify-between relative transition duration-200 {{ $isCurrentPlan ? 'border-brand-500 dark:border-cyan-400 shadow-xl ring-2 ring-brand-500/20' : 'border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md' }}">
                            @if($isCurrentPlan)
                                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-brand-600 text-white text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full shadow-md">
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
                                                <button type="button" @click="activeDurationIndex = idx" :class="activeDurationIndex === idx ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-white shadow-sm font-bold border border-indigo-500/30' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'" class="py-1.5 px-2.5 rounded-lg text-[11px] transition text-center flex items-center gap-1">
                                                    <span x-text="(d.duration_value || d.duration_months) + (d.duration_unit === 'day' ? 'd' : 'm')"></span>
                                                    <span x-show="d.discount_percent > 0" class="text-[9px] text-amber-500 font-black" x-text="'-' + d.discount_percent + '%'"></span>
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
                                    <button type="button" @click="openCheckoutModal({{ $plan->toJson() }}, currentDuration)" class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs shadow-sm transition text-center flex items-center justify-center gap-1">
                                        <span>Manage / Extend Plan</span>
                                        <span>→</span>
                                    </button>
                                @else
                                    <button type="button" @click="openCheckoutModal({{ $plan->toJson() }}, currentDuration)" class="w-full py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-brand-500/20 transition text-center">
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

        <!-- Modal 1: Transparent Pre-Payment Plan Transition & Proration Summary Modal -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-5">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <template x-if="quote && quote.action_type === 'upgrade'">
                                    <span>🚀 Plan Upgrade Summary</span>
                                </template>
                                <template x-if="quote && quote.action_type === 'downgrade'">
                                    <span>🔄 Plan Downgrade & Refund Summary</span>
                                </template>
                                <template x-if="!quote || (quote.action_type !== 'upgrade' && quote.action_type !== 'downgrade')">
                                    <span>💳 Subscription & Checkout Summary</span>
                                </template>
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Subscribing to <strong class="text-slate-800 dark:text-slate-200" x-text="selectedPlan ? selectedPlan.name : ''"></strong>
                                (<span x-text="selectedDuration ? (selectedDuration.formatted_duration || selectedDuration.duration_months + ' Month(s)') : ''"></span>)
                            </p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold p-1">✕</button>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoadingQuote" class="py-8 text-center space-y-3">
                        <div class="inline-block w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Calculating proration and live quota summary...</p>
                    </div>

                    <!-- Main Quote Content (Loaded) -->
                    <div x-show="!isLoadingQuote && quote" class="space-y-4">

                        <!-- Action Choice Selector: Shift vs Extend (If user has an active subscription) -->
                        <template x-if="quote.available_actions && quote.available_actions.includes('shift') && quote.available_actions.includes('extend')">
                            <div class="space-y-2">
                                <label class="text-[10px] uppercase tracking-wider font-bold text-slate-400 dark:text-slate-500 block">
                                    Select What You Want to Do
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    <!-- Option A: Shift / Switch Period (Starts Today with Balance Adjustment) -->
                                    <button type="button" 
                                            @click="switchActionMode('shift')"
                                            :class="selectedActionMode === 'shift' ? 'border-brand-500 bg-brand-50/60 dark:bg-brand-950/40 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 hover:border-slate-300'"
                                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                    <span>🔄</span> Shift / Switch Now
                                                </span>
                                                <span x-show="selectedActionMode === 'shift'" class="text-[9px] font-bold uppercase tracking-wider text-brand-700 dark:text-cyan-300 bg-brand-100 dark:bg-brand-900/60 px-1.5 py-0.5 rounded-md">Selected</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                                                Starts today. Remaining unused days from current cycle are <strong>credited / deducted</strong>.
                                            </p>
                                        </div>
                                        <div class="mt-2.5 pt-2 border-t border-slate-200/60 dark:border-slate-800 text-[11px] font-mono">
                                            <template x-if="quote.shift_option">
                                                <span class="font-bold" :class="quote.shift_option.is_downgrade ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                                    <span x-text="quote.shift_option.is_downgrade ? '+₹' + quote.shift_option.prorated_credit.toLocaleString('en-IN') + ' Refund' : 'Pay ₹' + quote.shift_option.final_amount.toLocaleString('en-IN')"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </button>

                                    <!-- Option B: Extend Validity (+Add Time to End) -->
                                    <button type="button" 
                                            @click="switchActionMode('extend')"
                                            :class="selectedActionMode === 'extend' ? 'border-indigo-500 bg-indigo-50/60 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900 hover:border-slate-300'"
                                            class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer">
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                    <span>⏳</span> Extend Validity
                                                </span>
                                                <span x-show="selectedActionMode === 'extend'" class="text-[9px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/60 px-1.5 py-0.5 rounded-md">Selected</span>
                                            </div>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug">
                                                Keeps current cycle. Adds full duration directly onto your <strong>current expiration date</strong>.
                                            </p>
                                        </div>
                                        <div class="mt-2.5 pt-2 border-t border-slate-200/60 dark:border-slate-800 text-[11px] font-mono">
                                            <template x-if="quote.extend_option">
                                                <span class="font-bold text-slate-900 dark:text-white">
                                                    Pay ₹<span x-text="quote.extend_option.final_amount.toLocaleString('en-IN')"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <!-- Effective Validity Dates Preview -->
                        <div class="p-3 rounded-xl bg-slate-100/90 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 flex items-center justify-between text-xs">
                            <span class="text-slate-500 dark:text-slate-400 font-medium">📅 Effective Plan Validity:</span>
                            <strong class="font-mono text-slate-900 dark:text-white text-[11px] font-bold">
                                <span x-text="quote.start_date"></span> → <span x-text="quote.end_date"></span>
                            </strong>
                        </div>

                        <!-- Quota & Capacity Comparison Card -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 space-y-2.5">
                            <div class="text-[10px] uppercase tracking-wider font-bold text-slate-400 dark:text-slate-500">
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

                        <!-- Mathematical Proration Card (for Shift Mode with Proration) -->
                        <template x-if="selectedActionMode === 'shift' && quote.proration">
                            <div class="p-4 rounded-2xl border space-y-2.5 text-xs" :class="quote.action_type === 'downgrade' ? 'bg-blue-50/50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-900/60' : 'bg-indigo-50/50 dark:bg-indigo-950/30 border-indigo-200 dark:border-indigo-900/60'">
                                <div class="flex items-center justify-between text-[11px] font-bold" :class="quote.action_type === 'downgrade' ? 'text-blue-800 dark:text-blue-300' : 'text-indigo-800 dark:text-indigo-300'">
                                    <span>⏳ Unused Cycle Time Adjusted:</span>
                                    <span class="font-mono" x-text="quote.proration.days_remaining + ' of ' + quote.proration.total_days_in_cycle + ' days'"></span>
                                </div>
                                <div class="space-y-1.5 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60 text-[11px]">
                                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                        <span>Unused Current Plan Credit:</span>
                                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="'-₹' + quote.proration.old_plan_credit.toLocaleString('en-IN')"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-slate-600 dark:text-slate-400">
                                        <span>Target Plan Cost:</span>
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="'₹' + quote.proration.new_plan_cost.toLocaleString('en-IN')"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Pre-Payment Action Highlight Banner -->
                        <template x-if="quote.action_type === 'downgrade'">
                            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider font-bold text-emerald-700 dark:text-emerald-300 block">💰 Prorated Refund to Wallet</span>
                                    <span class="text-2xl font-black font-mono text-emerald-800 dark:text-emerald-100">
                                        +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span>
                                    </span>
                                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        You will NOT be charged. This unused balance will be refunded to your wallet.
                                    </p>
                                </div>
                                <div class="text-right text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 block">Wallet Balance</span>
                                    <strong class="font-mono text-slate-900 dark:text-white">₹<span x-text="walletBalance.toLocaleString('en-IN')"></span></strong>
                                </div>
                            </div>
                        </template>

                        <template x-if="quote.action_type !== 'downgrade'">
                            <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] uppercase tracking-wider font-bold text-indigo-600 dark:text-indigo-400 block">
                                        <span x-text="selectedActionMode === 'shift' && quote.action_type === 'upgrade' ? 'Net Prorated Amount to Pay' : 'Total Payable Amount'"></span>
                                    </span>
                                    <span class="text-2xl font-black text-indigo-900 dark:text-indigo-100 font-mono">
                                        ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span>
                                    </span>
                                </div>
                                <div class="text-right text-xs">
                                    <span class="text-slate-500 dark:text-slate-400 block">Wallet Balance</span>
                                    <strong class="font-mono text-slate-900 dark:text-white">₹<span x-text="walletBalance.toLocaleString('en-IN')"></span></strong>
                                </div>
                            </div>
                        </template>

                        <!-- Error alert -->
                        <div x-show="walletError && !mruConflict" x-cloak class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold">
                            <span>❌ </span><span x-text="walletError"></span>
                        </div>

                        <!-- MRU Downgrade Conflict & Quick Lock Resolution Section -->
                        <div x-show="mruConflict" x-cloak class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900/60 space-y-3">
                            <div class="flex items-start gap-2.5">
                                <span class="text-xl">⚠️</span>
                                <div>
                                    <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200">Active MRU Quota Exceeded</h4>
                                    <p class="text-[11px] text-amber-800 dark:text-amber-300 mt-0.5">
                                        Target plan includes <strong x-text="newPlanQuota"></strong> MRU(s). You have <strong x-text="activeMrus.length"></strong> active. Please lock or delete <strong x-text="excessMrus"></strong> MRU(s) to continue.
                                    </p>
                                </div>
                            </div>

                            <div x-show="lockSuccessMsg" x-cloak class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-950/70 border border-emerald-300 text-emerald-800 dark:text-emerald-300 text-[11px] font-semibold flex items-center gap-1.5">
                                <span>✓</span> <span x-text="lockSuccessMsg"></span>
                            </div>

                            <div class="space-y-2 pt-2 border-t border-amber-200/70 dark:border-amber-800/70">
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    <span>Choose MRU to lock right now:</span>
                                    <span class="text-amber-600 dark:text-amber-400 font-mono" x-text="'Need ' + excessMrus + ' more'"></span>
                                </div>

                                <div class="space-y-1.5 max-h-40 overflow-y-auto pr-1">
                                    <template x-for="m in activeMrus" :key="m.id">
                                        <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-amber-200/70 dark:border-slate-800 shadow-xs">
                                            <div class="flex items-center gap-2">
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-blue-50 dark:bg-blue-950 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800" x-text="m.code"></span>
                                                <div>
                                                    <div class="text-xs font-bold text-slate-900 dark:text-white" x-text="m.name"></div>
                                                    <div class="text-[10px] text-slate-400 font-mono" x-text="m.consumers_count + ' consumers'"></div>
                                                </div>
                                            </div>
                                            <button type="button" @click="lockMruFromModal(m.id)" :disabled="isLockingMru" class="px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-[10px] font-bold shadow-xs transition flex items-center gap-1 disabled:opacity-50">
                                                <span>🔒 Lock</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-amber-200/70 dark:border-amber-800/70 flex items-center justify-between">
                                <span class="text-[11px] text-slate-500">Or manage existing zones:</span>
                                <a href="{{ route('mrus.index') }}" class="text-xs font-bold text-blue-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
                                    <span>🗂️ Manage / Delete MRUs on MRU Page →</span>
                                </a>
                            </div>
                        </div>

                        <!-- Confirmation & Action Section -->
                        <div class="space-y-3 pt-2">
                            <!-- Case A: Downgrade (Instant Confirmation with Credit) -->
                            <template x-if="quote.action_type === 'downgrade'">
                                <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50">
                                    <span x-show="!isProcessingWallet">✓ Confirm Downgrade & Receive +₹<span x-text="quote.prorated_credit.toLocaleString('en-IN')"></span> to Wallet</span>
                                    <span x-show="isProcessingWallet" x-cloak>Applying Downgrade...</span>
                                </button>
                            </template>

                            <!-- Case B: Upgrade or New/Renewal (Payment Options) -->
                            <template x-if="quote.action_type !== 'downgrade'">
                                <div class="space-y-3">
                                    <!-- Option 1: Pay from Wallet -->
                                    <div class="p-4 rounded-2xl border-2 transition" :class="walletBalance >= quote.final_amount ? 'border-emerald-500/40 bg-emerald-50/30 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950'">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg">👛</span>
                                                <div>
                                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay from Wallet Balance</h4>
                                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Instant activation with no redirect.</p>
                                                </div>
                                            </div>
                                            <span class="text-xs font-mono font-bold" :class="walletBalance >= quote.final_amount ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500'">
                                                ₹<span x-text="walletBalance.toLocaleString('en-IN')"></span> Available
                                            </span>
                                        </div>

                                        <div class="mt-3">
                                            <template x-if="walletBalance >= quote.final_amount">
                                                <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet || mruConflict" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50">
                                                    <span x-show="!isProcessingWallet">✓ Pay ₹<span x-text="quote.final_amount.toLocaleString('en-IN')"></span> from Wallet</span>
                                                    <span x-show="isProcessingWallet" x-cloak>Processing Payment...</span>
                                                </button>
                                            </template>

                                            <template x-if="walletBalance < quote.final_amount">
                                                <div class="space-y-2">
                                                    <p class="text-[11px] text-rose-500 font-semibold">
                                                        Wallet balance is insufficient (Deficit: ₹<span x-text="(quote.final_amount - walletBalance).toLocaleString('en-IN')"></span>).
                                                    </p>
                                                    <a href="{{ route('payments.create') }}" class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 font-bold text-xs flex items-center justify-center gap-1 hover:bg-indigo-100 transition">
                                                        <span>👛 Top-Up Wallet First →</span>
                                                    </a>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Option 2: Pay Directly -->
                                    <div class="p-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between">
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
                        </div>
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
