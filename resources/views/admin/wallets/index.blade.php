<x-admin-layout>
    <x-slot name="header">
        Agent Wallet Ledgers & System Balances
    </x-slot>

    <div class="space-y-6">

        <!-- Top Header & Breadcrumb -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                    <span>👛</span> Agent Wallets Master Ledger
                </h1>
                <p class="text-xs text-slate-400">
                    Monitor user wallet balances, perform manual credit/debit adjustments, and manage freeze states.
                </p>
            </div>

            <div>
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold transition flex items-center gap-2">
                    <span>👥</span> User Management
                </a>
            </div>
        </div>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Wallets</span>
                <div class="text-2xl font-black font-mono text-white">{{ $stats['total_wallets'] }}</div>
                <span class="text-[10px] text-slate-500">Registered agent accounts</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total System Balance</span>
                <div class="text-2xl font-black font-mono text-emerald-400">₹{{ number_format($stats['total_balance'], 2) }}</div>
                <span class="text-[10px] text-slate-500">Combined agent liabilities</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Frozen Wallets</span>
                <div class="text-2xl font-black font-mono {{ $stats['frozen_wallets'] > 0 ? 'text-rose-400' : 'text-slate-400' }}">
                    {{ $stats['frozen_wallets'] }}
                </div>
                <span class="text-[10px] text-slate-500">Restricted debit status</span>
            </div>

            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Admin Adjustments</span>
                <div class="text-2xl font-black font-mono text-indigo-400">{{ $stats['total_adjustments'] }}</div>
                <span class="text-[10px] text-slate-500">Total manual adjustments logged</span>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800">
            <form method="GET" action="{{ route('admin.wallets.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div class="sm:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by agent name, email, or phone..." class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white placeholder-slate-500 p-2.5 focus:ring-indigo-500">
                </div>

                <div>
                    <select name="status" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 Active (Normal)</option>
                        <option value="frozen" {{ request('status') === 'frozen' ? 'selected' : '' }}>🔒 Frozen Wallets</option>
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition text-xs shadow-md shadow-indigo-600/30">
                        Filter
                    </button>
                    <a href="{{ route('admin.wallets.index') }}" class="p-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl transition text-xs font-bold">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Wallets Table -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                        <tr>
                            <th class="py-3 px-4">Agent / User</th>
                            <th class="py-3 px-4">Current Balance</th>
                            <th class="py-3 px-4">Plan Tier</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono">
                        @forelse($users as $u)
                            @php
                                $balance = (float) $u->balanceFloat;
                                $isFrozen = $u->isWalletFrozen();
                            @endphp
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-3.5 px-4 font-sans">
                                    <div class="font-bold text-white">{{ $u->name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $u->email }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-sm font-black {{ $balance < 0 ? 'text-rose-400' : ($balance < 200 ? 'text-amber-400' : 'text-emerald-400') }}">
                                        ₹{{ number_format($balance, 2) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-sans">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">
                                        {{ $u->plan_tier ?? 'free' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-sans">
                                    @if($isFrozen)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            🔒 FROZEN
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            ACTIVE
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-right font-sans">
                                    <a href="{{ route('admin.wallets.show', $u->id) }}" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-bold transition inline-flex items-center gap-1">
                                        <span>⚙️</span> Manage Wallet →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500 font-sans">No agent wallets found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
