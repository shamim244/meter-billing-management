<x-admin-layout>
    <x-slot name="header">
        User 360° Dossier — {{ $user->name }}
    </x-slot>

    <div class="space-y-6" x-data="{
        showGrantModal: false,
        showQuotaModal: false,
        showNotificationModal: false,
        showPurgeModal: false,
        grantMode: 'new_plan',
        selectedPlanId: '{{ $availablePlans->first()?->id ?? '' }}',
        purgeConfirm: ''
    }">
        <!-- Top Back & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 hover:text-white transition">
                <span>←</span> Back to All Users
            </a>

            <div class="flex flex-wrap items-center gap-2">
                <!-- Impersonate Button -->
                @if($user->id !== auth()->id() && !$user->hasRole('admin'))
                    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Log in as {{ $user->name }}? You will be redirected to their dashboard.');" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-500 hover:to-orange-500 text-white rounded-xl text-xs font-black shadow-lg shadow-amber-600/20 transition active:scale-95">
                            <span>🎭</span> Login as User
                        </button>
                    </form>
                @endif

                <!-- Direct Notification Dispatcher -->
                <button type="button" @click="showNotificationModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-cyan-300 border border-cyan-500/30 rounded-xl text-xs font-bold transition">
                    <span>📢</span> Send Alert
                </button>

                <!-- Grant Plan / Extend Button -->
                <button type="button" @click="showGrantModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition">
                    <span>🎁</span> Grant / Extend Plan
                </button>

                <!-- Override Quotas Button -->
                <button type="button" @click="showQuotaModal = true" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-amber-300 border border-amber-500/30 rounded-xl text-xs font-bold transition">
                    <span>🎯</span> Quotas
                </button>

                <!-- Manage Wallet Link -->
                <a href="{{ route('admin.wallets.show', $user->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-bold transition">
                    <span>👛</span> Wallet
                </a>

                <!-- Edit Profile -->
                <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition">
                    <span>✏️</span> Edit
                </a>

                <!-- Suspend / Activate Toggle -->
                @if($user->id !== auth()->id())
                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3.5 py-2 rounded-xl text-xs font-bold transition {{ $user->status === 'active' ? 'bg-rose-950/60 hover:bg-rose-900/80 text-rose-300 border border-rose-500/30' : 'bg-emerald-950/60 hover:bg-emerald-900/80 text-emerald-300 border border-emerald-500/30' }}">
                            {{ $user->status === 'active' ? '🚫 Suspend' : '✓ Activate' }}
                        </button>
                    </form>

                    <!-- Danger Purge Button -->
                    <button type="button" @click="showPurgeModal = true" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-600/40 transition">
                        <span>🗑️</span> Purge
                    </button>
                @endif
            </div>
        </div>

        <!-- Hero Identity Card -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-indigo-600/30 shrink-0">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="text-2xl font-black text-white tracking-tight">{{ $user->name }}</h1>
                            
                            <!-- Role Pill -->
                            @foreach($user->roles as $role)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $role->name === 'admin' ? 'bg-purple-950 text-purple-300 border border-purple-500/40' : 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                                    {{ $role->name }}
                                </span>
                            @endforeach

                            <!-- Status Pill -->
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $user->status === 'active' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/30' : 'bg-rose-950 text-rose-300 border border-rose-500/30' }}">
                                {{ $user->status }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-2">
                            <span class="flex items-center gap-1">
                                <span>📧</span> {{ $user->email }}
                            </span>
                            @if($user->email_verified_at)
                                <span class="text-emerald-400 font-bold flex items-center gap-0.5">
                                    <span>✓</span> Verified
                                </span>
                            @else
                                <span class="text-amber-400 font-bold">⚠️ Unverified</span>
                            @endif

                            @if($user->phone)
                                <span>•</span>
                                <span class="flex items-center gap-1 font-mono">
                                    <span>📞</span> {{ $user->phone }}
                                </span>
                            @endif

                            <span>•</span>
                            <span>Joined {{ $user->created_at->format('M d, Y (h:i A)') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="bg-slate-900/90 px-4 py-3 rounded-2xl border border-slate-800 text-center min-w-[100px]">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">User ID</div>
                        <div class="text-base font-black text-white font-mono mt-0.5">#{{ $user->id }}</div>
                    </div>
                </div>
            </div>
        </div>

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

        <!-- Two Column Main Body -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: MRUs & Billing Cycle Review Metrics -->
            <div class="lg:col-span-2 space-y-6">
                <!-- MRU Workspaces List -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>📁</span> MRU Workspaces ({{ $mrus->count() }})
                        </h2>
                        <span class="text-xs text-slate-400 font-medium">Permanent consumer master lists</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-300">
                            <thead class="bg-slate-900/80 text-slate-400 uppercase font-bold text-[10px]">
                                <tr>
                                    <th class="py-3 px-4">MRU Code</th>
                                    <th class="py-3 px-4">Name</th>
                                    <th class="py-3 px-4 text-center">Consumers</th>
                                    <th class="py-3 px-4 text-center">Cycles</th>
                                    <th class="py-3 px-4 text-center">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 font-medium">
                                @forelse($mrus as $mru)
                                    <tr class="hover:bg-slate-900/40 transition">
                                        <td class="py-3 px-4 font-mono font-bold text-cyan-400">
                                            {{ $mru->code }}
                                        </td>
                                        <td class="py-3 px-4 text-white font-semibold">
                                            {{ $mru->name }}
                                        </td>
                                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-200">
                                            {{ number_format($mru->consumer_accounts_count) }}
                                        </td>
                                        <td class="py-3 px-4 text-center font-mono text-indigo-400">
                                            {{ $mru->billing_cycles_count }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-slate-500">
                                            {{ $mru->created_at->format('M d, Y') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-8 text-center text-slate-500">
                                            No MRU workspaces created by this user yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Billing & Audit Activity -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <span>⚡</span> Bill Review & Audit Activity
                        </h2>
                        <span class="text-xs text-slate-400 font-mono">Lifetime Total: {{ number_format($billStats['total']) }}</span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Submitted / OK</div>
                            <div class="text-xl font-black text-emerald-400 font-mono mt-1">
                                {{ number_format($billStats['submitted']) }}
                            </div>
                        </div>

                        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Doubt / Re-check</div>
                            <div class="text-xl font-black text-amber-400 font-mono mt-1">
                                {{ number_format($billStats['doubt']) }}
                            </div>
                        </div>

                        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Critical / Issue</div>
                            <div class="text-xl font-black text-rose-400 font-mono mt-1">
                                {{ number_format($billStats['critical']) }}
                            </div>
                        </div>

                        <div class="bg-slate-900/70 p-4 rounded-2xl border border-slate-800 text-center">
                            <div class="text-[10px] font-bold text-slate-400 uppercase">Downloaded PDFs</div>
                            <div class="text-xl font-black text-indigo-400 font-mono mt-1">
                                {{ number_format($billStats['downloaded_pdfs']) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Recent Transactions & Plan Transition History -->
            <div class="space-y-6">
                <!-- Recent Wallet Transactions -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-white flex items-center gap-1.5">
                            <span>👛</span> Recent Wallet Activity
                        </h2>
                        <a href="{{ route('admin.wallets.show', $user->id) }}" class="text-[11px] text-cyan-400 hover:underline font-bold">
                            All →
                        </a>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($recentTransactions as $tx)
                            <div class="p-3 bg-slate-900/60 rounded-2xl border border-slate-800/80 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-white truncate max-w-[170px]">
                                        {{ $tx->meta['description'] ?? ($tx->meta['source'] ?? ucfirst($tx->type)) }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono">{{ $tx->created_at->format('M d, Y h:i A') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-bold {{ $tx->type === 'deposit' ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $tx->type === 'deposit' ? '+' : '-' }}₹{{ number_format(abs($tx->amount) / 100, 2) }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono uppercase">{{ $tx->type }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-slate-500">
                                No wallet transactions recorded.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Subscription Transition Logs -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h2 class="text-sm font-bold text-white flex items-center gap-1.5">
                            <span>🔄</span> Plan Transition History
                        </h2>
                    </div>

                    <div class="space-y-2.5">
                        @forelse($transitions as $trans)
                            <div class="p-3 bg-slate-900/60 rounded-2xl border border-slate-800/80 text-xs space-y-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-white capitalize">
                                        {{ $trans->action_type }}
                                    </span>
                                    <span class="text-[10px] font-mono font-bold text-indigo-400">
                                        ₹{{ number_format($trans->amount_charged, 2) }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $trans->fromPlan->name ?? 'Start' }} → <strong class="text-slate-200">{{ $trans->toPlan->name ?? 'Target' }}</strong>
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    {{ $trans->created_at->format('M d, Y h:i A') }}
                                </div>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-slate-500">
                                No plan transitions on record.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODALS ================= -->

        <!-- MODAL 1: Grant Plan / Extend Validity -->
        <div x-show="showGrantModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showGrantModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🎁</span> Grant Plan / Extend Validity
                    </h3>
                    <button type="button" @click="showGrantModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.users.grant-plan', $user) }}" class="space-y-4">
                    @csrf
                    
                    <!-- Mode Switch -->
                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800 text-xs">
                        <button type="button" @click="grantMode = 'new_plan'" :class="grantMode === 'new_plan' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition text-center">
                            Assign New Plan
                        </button>
                        <button type="button" @click="grantMode = 'extend_validity'" :class="grantMode === 'extend_validity' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white'" class="py-2 rounded-lg transition text-center">
                            + Add Days to Expiry
                        </button>
                    </div>
                    <input type="hidden" name="grant_mode" :value="grantMode">

                    <!-- Mode A: Assign New Plan -->
                    <div x-show="grantMode === 'new_plan'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Select Subscription Plan</label>
                            <select name="plan_id" x-model="selectedPlanId" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3">
                                @foreach($availablePlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }} ({{ $plan->included_mrus }} MRUs / {{ number_format($plan->included_consumers) }} CAs)</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Select Validity Duration</label>
                            @foreach($availablePlans as $plan)
                                <div x-show="selectedPlanId == '{{ $plan->id }}'" class="space-y-1">
                                    <select name="duration_id" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3" :disabled="selectedPlanId != '{{ $plan->id }}'">
                                        @foreach($plan->activeDurations as $dur)
                                            <option value="{{ $dur->id }}">{{ $dur->formatted_duration }} (Standard: ₹{{ number_format($dur->final_price, 2) }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Mode B: Extend Validity Days -->
                    <div x-show="grantMode === 'extend_validity'" class="space-y-3">
                        <label class="block text-xs font-bold text-slate-300">Days to add to active subscription</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button type="button" @click="$refs.daysInput.value = 30" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+30 Days</button>
                            <button type="button" @click="$refs.daysInput.value = 60" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+60 Days</button>
                            <button type="button" @click="$refs.daysInput.value = 90" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+90 Days</button>
                            <button type="button" @click="$refs.daysInput.value = 365" class="py-2 bg-slate-950 hover:bg-slate-800 border border-slate-800 rounded-xl text-xs font-bold text-white transition">+1 Year</button>
                        </div>
                        <input x-ref="daysInput" type="number" name="days_to_add" value="30" min="1" max="365" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono" placeholder="Custom days (e.g. 45)">
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="showGrantModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow">Apply Grant / Extension</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: Override Quotas -->
        <div x-show="showQuotaModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showQuotaModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🎯</span> Custom Quota & Rate Overrides
                    </h3>
                    <button type="button" @click="showQuotaModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.users.override-quotas', $user) }}" class="space-y-4">
                    @csrf
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Included MRUs Locked</label>
                            <input type="number" name="included_mrus_locked" value="{{ $user->activeSubscription->included_mrus_locked ?? ($user->activeSubscription->plan->included_mrus ?? 1) }}" min="1" max="1000" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Included Consumers Locked</label>
                            <input type="number" name="included_consumers_locked" value="{{ $user->activeSubscription->included_consumers_locked ?? ($user->activeSubscription->plan->included_consumers ?? 500) }}" min="10" max="1000000" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Extra MRU Rate (₹)</label>
                            <input type="number" step="0.01" name="extra_mru_rate_locked" value="{{ $user->activeSubscription->extra_mru_rate_locked ?? 20.00 }}" min="0" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Extra Consumer Rate (₹)</label>
                            <input type="number" step="0.01" name="extra_consumer_rate_locked" value="{{ $user->activeSubscription->extra_consumer_rate_locked ?? 0.20 }}" min="0" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3 font-mono">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="showQuotaModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-500 text-white shadow">Save Quota Overrides</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 3: Direct Notification Dispatcher -->
        <div x-show="showNotificationModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showNotificationModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>📢</span> Send Direct Notification to {{ $user->name }}
                    </h3>
                    <button type="button" @click="showNotificationModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('admin.users.send-notification', $user) }}" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Notification Title <span class="text-rose-400">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Account Update / Action Required" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Message Body <span class="text-rose-400">*</span></label>
                        <textarea name="body" rows="4" required placeholder="Enter the message you wish to send to this operator..." class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Priority</label>
                            <select name="priority" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-2 px-3">
                                <option value="routine">Routine (Info)</option>
                                <option value="critical">Critical (High Importance)</option>
                                <option value="urgent">Urgent (Immediate Action)</option>
                            </select>
                        </div>

                        <div class="flex items-center pt-5">
                            <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white">
                                <input type="checkbox" name="send_email" value="1" checked class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500">
                                <span>Also send to email</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="showNotificationModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" class="px-5 py-2 text-xs font-bold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white shadow">Dispatch Alert</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 4: Danger Zone Purge Account -->
        <div x-show="showPurgeModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div @click.away="showPurgeModal = false" class="bg-rose-950/90 border border-rose-600/50 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-rose-700/50 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span>⚠️</span> Purge User Account & Storage
                    </h3>
                    <button type="button" @click="showPurgeModal = false" class="text-rose-300 hover:text-white text-lg">✕</button>
                </div>

                <div class="text-xs text-rose-200 space-y-2 leading-relaxed">
                    <p>This action is <strong>irreversible</strong>. It will permanently delete:</p>
                    <ul class="list-disc list-inside space-y-0.5 text-rose-300 font-mono text-[11px]">
                        <li>All stored private PDF files on disk</li>
                        <li>MRU workspaces & consumer accounts</li>
                        <li>Billing records, status tags, & history</li>
                        <li>Wallet ledger and active subscriptions</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('admin.users.purge', $user) }}" class="space-y-4">
                    @csrf
                    @method('DELETE')
                    
                    <div>
                        <label class="block text-xs font-bold text-white mb-1">Type <code class="bg-black/40 px-1.5 py-0.5 rounded text-rose-300">DELETE</code> to confirm:</label>
                        <input type="text" name="confirm_text" x-model="purgeConfirm" placeholder="DELETE" required class="w-full text-xs bg-slate-950 border-rose-500/50 rounded-xl text-white py-2 px-3 font-mono">
                    </div>

                    <div class="pt-3 border-t border-rose-700/50 flex justify-end gap-2">
                        <button type="button" @click="showPurgeModal = false" class="px-4 py-2 text-xs rounded-xl bg-black/40 text-slate-300">Cancel</button>
                        <button type="submit" :disabled="purgeConfirm !== 'DELETE'" class="px-5 py-2 text-xs font-black rounded-xl bg-rose-600 hover:bg-rose-500 text-white disabled:opacity-40 disabled:cursor-not-allowed shadow transition">
                            Permanently Purge Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>
