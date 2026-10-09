<!-- 1. Plan & Pricing Summary Card (Server-Derived, Fixed) -->
<div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-3">
            <div class="flex items-center gap-2">
                <span class="px-3 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {{ $plan->name }}
                </span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">
                    {{ $duration->formatted_duration }} Duration
                </span>
                @if($duration->discount_percent > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                        {{ $duration->discount_percent }}% OFF
                    </span>
                @endif
            </div>

            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ $plan->name }} Plan Activation
            </h2>

            @if($plan->description)
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xl">
                    {{ $plan->description }}
                </p>
            @endif

            <!-- Quota Inclusions -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Included MRUs</div>
                    <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($plan->included_mrus) }}</div>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Consumers / Cycle</div>
                    <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($plan->included_consumers) }}</div>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Extra MRU Rate</div>
                    <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($duration->extra_mru_rate ?? $plan->extra_mru_rate, 2) }}</div>
                </div>
                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold">Extra CA Rate</div>
                    <div class="text-sm font-black text-slate-900 dark:text-white font-mono mt-0.5">₹{{ number_format($duration->extra_consumer_rate ?? $plan->extra_consumer_rate, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Pricing Summary Display Box (Fixed, Non-Editable) -->
        <div class="bg-gradient-to-br from-indigo-900/40 to-slate-900/60 dark:bg-slate-950 p-6 rounded-3xl border border-indigo-500/20 dark:border-slate-800 min-w-[280px] space-y-4">
            <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Amount Due</div>

            @if($pricingDetails['action_type'] === 'upgrade' && $pricingDetails['proration'])
                <div class="space-y-1.5 text-xs text-slate-300 border-b border-slate-800 pb-3">
                    <div class="flex justify-between">
                        <span class="text-slate-400">New Plan Cost (Prorated):</span>
                        <span class="font-mono font-bold">₹{{ number_format($pricingDetails['proration']['new_plan_cost'], 2) }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-400">
                        <span>Current Plan Credit:</span>
                        <span class="font-mono font-bold">-₹{{ number_format($pricingDetails['proration']['old_plan_credit'], 2) }}</span>
                    </div>
                    <div class="text-[10px] text-slate-500">
                        {{ $pricingDetails['proration']['days_remaining'] }} of {{ $pricingDetails['proration']['total_days_in_cycle'] }} cycle days remaining.
                    </div>
                </div>
            @elseif($pricingDetails['discount_percent'] > 0)
                <div class="space-y-1 text-xs text-slate-400 border-b border-slate-800 pb-2">
                    <div class="flex justify-between">
                        <span>Standard Price:</span>
                        <span class="line-through font-mono">₹{{ number_format($plan->base_price * $duration->duration_months, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-amber-400">
                        <span>Duration Discount:</span>
                        <span class="font-bold">{{ $duration->discount_percent }}% OFF</span>
                    </div>
                </div>
            @endif

            <div>
                <div class="text-3xl font-black text-white font-mono tracking-tight">
                    ₹{{ number_format($pricingDetails['final_amount'], 2) }}
                </div>
                <span class="text-[11px] text-slate-400 block mt-0.5">Fixed total amount for this checkout</span>
            </div>

            <!-- Wallet Option Shortcut -->
            @if($walletBalance >= $pricingDetails['final_amount'])
                <form method="POST" action="{{ route('subscription.subscribe_wallet') }}">
                    @csrf
                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                    <input type="hidden" name="duration_id" value="{{ $duration->id }}">
                    <input type="hidden" name="action_mode" value="{{ $pricingDetails['action_mode'] }}">
                    <button type="submit" class="w-full py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-1.5">
                        <span>👛 Pay from Wallet Balance (₹{{ number_format($walletBalance, 2) }})</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
