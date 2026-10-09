<!-- 4 Stat Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- 1. Subscription Card -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-bold uppercase tracking-wider text-[10px]">Active Subscription</span>
                <button type="button" @click="showGrantModal = true" class="text-[10px] text-indigo-400 hover:underline font-bold">
                    + Grant Plan
                </button>
            </div>
            @if($user->activeSubscription)
                <div class="text-lg font-black text-white">
                    {{ $user->activeSubscription->plan->name ?? 'Custom Plan' }}
                </div>
                <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $user->activeSubscription->lifecycle_status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : ($user->activeSubscription->lifecycle_status === 'grace_period' ? 'bg-amber-950 text-amber-300 border border-amber-500/30' : 'bg-rose-950 text-rose-300 border border-rose-500/30') }}">
                        {{ str_replace('_', ' ', $user->activeSubscription->lifecycle_status) }}
                    </span>
                    @if($user->activeSubscription->auto_renew)
                        <span class="text-[10px] text-cyan-400 font-bold">Auto-Renew ON</span>
                    @endif
                </div>
            @else
                <div class="text-lg font-black text-slate-400">No Active Plan</div>
                <div class="text-xs text-slate-500 mt-0.5">Free / Tier: {{ $user->plan_tier ?? 'free' }}</div>
            @endif
        </div>

        <div class="pt-3 border-t border-slate-800/80 mt-3 text-xs text-slate-400 flex items-center justify-between">
            @if($user->activeSubscription && $user->activeSubscription->billing_end)
                <span>Expires: <strong>{{ $user->activeSubscription->billing_end->format('M d, Y') }}</strong></span>
                <button type="button" @click="showGrantModal = true; grantMode = 'extend_validity'" class="text-[10px] text-cyan-400 hover:underline font-bold">+ Extend</button>
            @else
                <span>Lifetime / Manual quota</span>
            @endif
        </div>
    </div>

    <!-- 2. Wallet Card -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-bold uppercase tracking-wider text-[10px]">Wallet Ledger</span>
                <span>👛</span>
            </div>
            <div class="text-2xl font-black font-mono text-emerald-400">
                ₹{{ number_format($user->wallet?->balance ?? 0, 2) }}
            </div>
            <div class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                @if($user->isWalletFrozen())
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-950 text-rose-300 border border-rose-500/30">
                        ❄️ Frozen
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-950 text-emerald-300 border border-emerald-500/30">
                        ✓ Active
                    </span>
                @endif
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800/80 mt-3 flex items-center justify-between text-xs">
            <span class="text-slate-400">Adjustments:</span>
            <a href="{{ route('admin.wallets.show', $user->id) }}" class="text-cyan-400 hover:underline font-bold">
                View Ledger →
            </a>
        </div>
    </div>

    <!-- 3. MRUs & Consumers Card -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-bold uppercase tracking-wider text-[10px]">MRUs & Master Base</span>
                <button type="button" @click="showQuotaModal = true" class="text-[10px] text-amber-400 hover:underline font-bold">
                    ⚙️ Quota
                </button>
            </div>
            <div class="text-2xl font-black font-mono text-cyan-400">
                {{ $mrus->count() }} <span class="text-sm font-sans font-bold text-slate-400">MRU(s)</span>
            </div>
            <div class="text-xs text-slate-400 mt-0.5">
                <strong class="text-slate-200 font-mono">{{ number_format($user->consumerAccounts()->count()) }}</strong> active consumers
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800/80 mt-3 text-xs text-slate-400">
            <span>Locked Quota: <strong>{{ $user->activeSubscription->included_mrus_locked ?? ($user->activeSubscription->plan->included_mrus ?? 1) }} MRUs / {{ number_format($user->activeSubscription->included_consumers_locked ?? ($user->activeSubscription->plan->included_consumers ?? 500)) }} CAs</strong></span>
        </div>
    </div>

    <!-- 4. Storage Usage Card -->
    <div class="bg-slate-950 p-5 rounded-3xl border border-slate-800 shadow-lg flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                <span class="font-bold uppercase tracking-wider text-[10px]">Disk Storage Footprint</span>
                <span>💾</span>
            </div>
            <div class="text-xl font-black font-mono text-indigo-300">
                {{ $storageMetrics['used_mb'] }} MB <span class="text-xs font-sans font-medium text-slate-400">/ {{ $storageMetrics['limit_mb'] }} MB</span>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-indigo-500 h-1.5 rounded-full" style="width: {{ min(100, $storageMetrics['percent']) }}%"></div>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800/80 mt-3 text-xs text-slate-400 flex items-center justify-between">
            <span>Usage: <strong>{{ $storageMetrics['percent'] }}%</strong></span>
            <span>{{ $billStats['downloaded_pdfs'] }} PDFs</span>
        </div>
    </div>
</div>
