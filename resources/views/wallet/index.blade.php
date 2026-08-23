<x-app-layout>
    <div class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Top Header & Fast Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                        <span>👛</span> Agent Wallet & Financial Ledger
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Prepaid balance for automated NBPDCL bill downloading and meter processing tasks.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <a href="{{ route('wallet.export') }}" class="px-3.5 py-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-300 transition flex items-center gap-1.5 shadow-sm">
                        <span>📥</span> Export CSV Ledger
                    </a>
                    <a href="{{ route('payments.create', ['purpose' => 'wallet_topup']) }}" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-cyan-600 hover:from-indigo-500 hover:to-cyan-500 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition flex items-center gap-1.5">
                        <span>⚡</span> + Add Funds / Top-Up
                    </a>
                </div>
            </div>

            <!-- Frozen Wallet Warning Banner -->
            @if($user->isWalletFrozen())
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-900 dark:text-rose-200 text-xs flex items-start gap-3 shadow-sm">
                    <span class="text-lg">🔒</span>
                    <div class="space-y-0.5">
                        <div class="font-bold">Wallet Frozen by Administrator</div>
                        <p class="text-rose-700 dark:text-rose-300 text-[11px]">
                            {{ $user->wallet_frozen_reason ?: 'Debits on this wallet are temporarily paused. Contact billing support if you need assistance.' }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <!-- Available Balance -->
                <div class="bg-gradient-to-br from-indigo-900/90 to-slate-900 text-white p-5 rounded-3xl border border-indigo-500/30 shadow-lg shadow-indigo-950/30 space-y-2 relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs text-indigo-200 font-bold uppercase tracking-wider">
                        <span>Available Balance</span>
                        <span>👛</span>
                    </div>
                    <div class="text-3xl font-black font-mono tracking-tight text-white">
                        ₹{{ number_format((float)$balance, 2) }}
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] {{ $balance > 200 ? 'text-emerald-400' : 'text-amber-400 font-bold' }}">
                        @if($balance <= 0)
                            <span>⚠️ Zero Balance (Top-up required)</span>
                        @elseif($balance < 200)
                            <span>⚠️ Low Balance Warning</span>
                        @else
                            <span>✓ Active & Ready for Tasks</span>
                        @endif
                    </div>
                </div>

                <!-- Total Credited -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Credited</span>
                    <div class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400">
                        ₹{{ number_format($stats['total_credited'], 2) }}
                    </div>
                    <span class="text-[10px] text-slate-400">Lifetime top-ups & bonuses</span>
                </div>

                <!-- Total Debited -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Debited</span>
                    <div class="text-xl font-black font-mono text-slate-800 dark:text-slate-200">
                        ₹{{ number_format($stats['total_debited'], 2) }}
                    </div>
                    <span class="text-[10px] text-slate-400">Processed downloads & fees</span>
                </div>

                <!-- Total Transactions -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-1">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Ledger Count</span>
                    <div class="text-xl font-black font-mono text-slate-800 dark:text-slate-200">
                        {{ number_format($stats['transaction_count']) }}
                    </div>
                    <span class="text-[10px] text-slate-400">Immutable ledger entries</span>
                </div>
            </div>

            <!-- Filters Bar -->
            <form method="GET" action="{{ route('wallet.index') }}" class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[180px]">
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search description, reference ID..." class="w-full text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2">
                </div>

                <div>
                    <select name="type" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2">
                        <option value="">All Types</option>
                        <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>Credits (+)</option>
                        <option value="debit" {{ ($filters['type'] ?? '') === 'debit' ? 'selected' : '' }}>Debits (−)</option>
                    </select>
                </div>

                <div>
                    <select name="source" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2">
                        <option value="">All Sources</option>
                        <option value="payment_topup" {{ ($filters['source'] ?? '') === 'payment_topup' ? 'selected' : '' }}>Payment Top-up</option>
                        <option value="admin_adjustment" {{ ($filters['source'] ?? '') === 'admin_adjustment' ? 'selected' : '' }}>Admin Adjustment</option>
                        <option value="bill_download_fee" {{ ($filters['source'] ?? '') === 'bill_download_fee' ? 'selected' : '' }}>Bill Download Fee</option>
                        <option value="subscription_fee" {{ ($filters['source'] ?? '') === 'subscription_fee' ? 'selected' : '' }}>Subscription Fee</option>
                    </select>
                </div>

                <div>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2" title="From Date">
                </div>

                <div>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="text-xs rounded-xl bg-slate-50 dark:bg-slate-800/80 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white px-3 py-2" title="To Date">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                        Filter
                    </button>
                    @if(!empty(array_filter($filters ?? [])))
                        <a href="{{ route('wallet.index') }}" class="text-xs text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <!-- Ledger Table -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                        <span>📜</span> Transaction Ledger (Immutable Record)
                    </h2>
                    <span class="text-xs text-slate-400">Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/75 dark:bg-slate-950/60 text-[10px] uppercase font-bold text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Tx ID</th>
                                <th class="py-3 px-4">Date & Time</th>
                                <th class="py-3 px-4">Type</th>
                                <th class="py-3 px-4">Source</th>
                                <th class="py-3 px-4">Amount</th>
                                <th class="py-3 px-4">Description</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-xs">
                            @forelse($transactions as $tx)
                                @php
                                    $isCredit = $tx->type === 'deposit';
                                    $meta = (array) ($tx->meta ?? []);
                                    $source = $meta['source'] ?? ($isCredit ? 'Credit' : 'Debit');
                                    $desc = $meta['description'] ?? ($meta['reason'] ?? '—');
                                @endphp
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/40 transition">
                                    <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">#{{ $tx->id }}</td>
                                    <td class="py-3.5 px-4 font-sans text-slate-500 dark:text-slate-400 text-[11px]">
                                        {{ $tx->created_at->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="py-3.5 px-4 font-sans">
                                        @if($isCredit)
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60">
                                                + CREDIT
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/60">
                                                − DEBIT
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-sans font-semibold text-slate-700 dark:text-slate-300 text-[11px]">
                                        {{ ucwords(str_replace('_', ' ', $source)) }}
                                    </td>
                                    <td class="py-3.5 px-4 font-black {{ $isCredit ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                        {{ $isCredit ? '+' : '−' }}₹{{ number_format((float)$tx->amountFloat, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 font-sans text-slate-600 dark:text-slate-400 text-[11px] max-w-xs truncate">
                                        {{ $desc }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-slate-400 font-sans text-xs">
                                        No transactions recorded in wallet ledger yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
