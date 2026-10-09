{{-- Available Subscription Plans Grid --}}
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
                            <div class="flex items-center gap-1.5">
                                @if($plan->is_free)
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                        100% Free
                                    </span>
                                @endif
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                    {{ $plan->included_mrus }} MRUs
                                </span>
                            </div>
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

                        <!-- Pricing Summary Box -->
                        <div class="mt-6 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-100 dark:border-slate-800/80 space-y-2">
                            <div class="flex items-baseline justify-between">
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Plan Rate:</span>
                                <div class="text-right">
                                    <span class="text-xl font-black text-slate-900 dark:text-white font-mono">
                                        ₹<span x-text="parseFloat(currentPrice).toLocaleString('en-IN')"></span>
                                    </span>
                                </div>
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
                        @elseif($plan->is_free)
                            <button type="button" @click="openCheckoutModal(Object.assign({{ $plan->toJson() }}, { durations: {{ $durations->values()->toJson() }} }), currentDuration)" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs flex items-center justify-center gap-1 shadow-md shadow-emerald-600/20 transition text-center cursor-pointer">
                                <span>⚡ Activate Free Plan (₹0)</span>
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
