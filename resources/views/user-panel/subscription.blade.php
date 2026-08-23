<x-user-panel-layout>
    <x-slot name="header">
        Subscription & Storage Allocation
    </x-slot>

    <div class="space-y-8" x-data="{
        showModal: false,
        selectedPlan: null,
        selectedDuration: null,
        walletBalance: {{ $walletBalance }},
        isProcessingWallet: false,
        walletError: null,
        walletSuccess: null,
        openCheckoutModal(plan, duration) {
            this.selectedPlan = plan;
            this.selectedDuration = duration;
            this.walletError = null;
            this.walletSuccess = null;
            this.isProcessingWallet = false;
            this.showModal = true;
        },
        async confirmWalletPayment() {
            if (!this.selectedPlan || !this.selectedDuration) return;
            this.isProcessingWallet = true;
            this.walletError = null;
            this.walletSuccess = null;

            try {
                const response = await fetch('{{ route('subscription.subscribe_wallet') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_id: this.selectedPlan.id,
                        duration_id: this.selectedDuration.id
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Wallet payment failed.');
                }

                this.walletSuccess = data.message;
                this.isProcessingWallet = false;
                setTimeout(() => {
                    window.location.reload();
                }, 1800);
            } catch (err) {
                this.isProcessingWallet = false;
                this.walletError = err.message;
            }
        },
        get directPurchaseUrl() {
            if (!this.selectedPlan || !this.selectedDuration) return '#';
            return `/subscription/purchase/${this.selectedPlan.id}/${this.selectedDuration.id}`;
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
                                        <div class="grid grid-cols-3 sm:grid-cols-5 gap-1.5 bg-slate-100 dark:bg-slate-950 p-1 rounded-xl">
                                            <template x-for="(d, idx) in durations" :key="d.id">
                                                <button type="button" @click="activeDurationIndex = idx" :class="activeDurationIndex === idx ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-white shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 font-medium'" class="py-1.5 px-1 rounded-lg text-[10px] transition text-center flex flex-col items-center">
                                                    <span x-text="d.duration_months + 'm'"></span>
                                                    <span x-show="d.discount_percent > 0" class="text-[8px] text-amber-500 font-black" x-text="'-' + d.discount_percent + '%'"></span>
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
                                        / <span x-text="currentDuration ? (currentDuration.duration_months + ' mo') : 'mo'"></span>
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
                                        <span>Extend / Renew Plan</span>
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

        <!-- Interactive Checkout Modal ("Pay from Wallet" vs "Pay Directly") -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showModal = false" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm transition-opacity"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 dark:border-slate-800 p-6 sm:p-8 space-y-6">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>💳</span> Choose Payment Method
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                Subscribing to <strong class="text-slate-800 dark:text-slate-200" x-text="selectedPlan ? selectedPlan.name : ''"></strong>
                                (<span x-text="selectedDuration ? (selectedDuration.duration_months + ' Month' + (selectedDuration.duration_months > 1 ? 's' : '')) : ''"></span>)
                            </p>
                        </div>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 font-bold p-1">✕</button>
                    </div>

                    <!-- Price Confirmation Banner -->
                    <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-bold text-indigo-600 dark:text-indigo-400 block">Total Payable Amount</span>
                            <span class="text-2xl font-black text-indigo-900 dark:text-indigo-100 font-mono">
                                ₹<span x-text="selectedDuration ? selectedDuration.final_price.toLocaleString('en-IN') : '0'"></span>
                            </span>
                        </div>
                        <div class="text-right text-xs">
                            <span class="text-slate-500 dark:text-slate-400 block">Wallet Balance</span>
                            <strong class="font-mono text-slate-900 dark:text-white">₹<span x-text="walletBalance.toLocaleString('en-IN')"></span></strong>
                        </div>
                    </div>

                    <!-- Feedback Alerts -->
                    <div x-show="walletSuccess" x-cloak class="p-3.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-xs font-bold">
                        <span x-text="walletSuccess"></span>
                    </div>

                    <div x-show="walletError" x-cloak class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-bold">
                        <span>❌ </span><span x-text="walletError"></span>
                    </div>

                    <!-- Option 1: Pay from Wallet (In-Place) -->
                    <div class="p-5 rounded-2xl border-2 transition" :class="selectedDuration && walletBalance >= selectedDuration.final_price ? 'border-emerald-500/40 bg-emerald-50/30 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950'">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="text-xl">👛</span>
                                <div>
                                    <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay from Wallet Balance</h4>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Instant activation with no redirect.</p>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-bold" :class="selectedDuration && walletBalance >= selectedDuration.final_price ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500'">
                                ₹<span x-text="walletBalance.toLocaleString('en-IN')"></span> Available
                            </span>
                        </div>

                        <div class="mt-4">
                            <template x-if="selectedDuration && walletBalance >= selectedDuration.final_price">
                                <button type="button" @click="confirmWalletPayment()" :disabled="isProcessingWallet" class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50">
                                    <span x-show="!isProcessingWallet">✓ Confirm & Subscribe from Wallet</span>
                                    <span x-show="isProcessingWallet" x-cloak>Activating Plan...</span>
                                </button>
                            </template>

                            <template x-if="selectedDuration && walletBalance < selectedDuration.final_price">
                                <div class="space-y-2">
                                    <p class="text-[11px] text-rose-500 font-semibold">
                                        Wallet balance is insufficient (Deficit: ₹<span x-text="(selectedDuration.final_price - walletBalance).toLocaleString('en-IN')"></span>).
                                    </p>
                                    <a href="{{ route('payments.create') }}" class="w-full py-2 px-3 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 font-bold text-xs flex items-center justify-center gap-1 hover:bg-indigo-100 transition">
                                        <span>👛 Top-Up Wallet First →</span>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Option 2: Pay Directly via PG / UPI / Bank Transfer -->
                    <div class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 space-y-3">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">💳</span>
                            <div>
                                <h4 class="text-xs font-bold text-slate-900 dark:text-white">Pay Directly (Gateway / UPI / Bank)</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Proceed to secure checkout for this specific plan.</p>
                            </div>
                        </div>

                        <a :href="directPurchaseUrl" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-bold text-xs shadow-md transition flex items-center justify-center gap-1.5">
                            <span>Proceed to Direct Payment →</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-user-panel-layout>
