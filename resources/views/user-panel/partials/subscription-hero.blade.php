{{-- Storage & Quota Status Hero --}}
<div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-2">
                @if(!$activeSubscription)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 animate-pulse">
                        ⚠️ No Active Plan
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-cyan-300 border border-blue-200 dark:border-blue-800">
                        Current Plan: {{ $activeSubscription->plan?->name }}
                    </span>
                @endif
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
