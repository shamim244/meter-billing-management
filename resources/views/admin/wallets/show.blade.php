<x-admin-layout>
    <x-slot name="header">
        Agent Wallet Console: {{ $user->name }}
    </x-slot>

    <div x-data="{
        showModal: false,
        adjustmentType: 'add',
        amount: '',
        reason: '',
        openModal(type) {
            this.adjustmentType = type;
            this.amount = '';
            this.reason = '';
            this.showModal = true;
        }
    }" class="space-y-6">

        <!-- Header Navigation & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.wallets.index') }}" class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-400 hover:text-white transition">
                    ← Back to Wallets
                </a>
                <div>
                    <h1 class="text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <span>👤</span> {{ $user->name }}'s Wallet Console
                    </h1>
                    <p class="text-xs text-slate-400">Agent Email: <span class="text-slate-300 font-mono">{{ $user->email }}</span> • ID: #{{ $user->id }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.wallets.export', $user->id) }}" class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <span>📥</span> Export CSV Ledger
                </a>
                <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                    <span>👤</span> View Profile
                </a>
            </div>
        </div>

        <!-- Flash Alerts -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>✅ {{ session('success') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between shadow-lg">
                <span>❌ {{ session('error') }}</span>
                <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
            </div>
        @endif

        <!-- Frozen Alert Banner if applicable -->
        @if($user->isWalletFrozen())
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-800 text-rose-200 text-xs flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <span class="text-lg">🔒</span>
                    <div>
                        <strong class="text-white block">This Agent's Wallet is Currently FROZEN</strong>
                        <span class="text-rose-300 text-[11px]">Reason: {{ $user->wallet_frozen_reason ?: 'Frozen by administrator.' }}</span>
                    </div>
                </div>

                <form action="{{ route('admin.wallets.toggle-freeze', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition">
                        🔓 Unfreeze Wallet Now
                    </button>
                </form>
            </div>
        @endif

        <!-- Central Wallet Card with 2 Adjustment Buttons & Freeze Toggle (PRD Section 7.4) -->
        <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">

                <!-- Left: Clear Prominent Balance Display -->
                <div class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <span>👛</span> Current Wallet Balance
                    </span>
                    <div class="text-4xl font-black font-mono tracking-tight {{ $balance < 0 ? 'text-rose-400' : ($balance < 200 ? 'text-amber-400' : 'text-emerald-400') }}">
                        ₹{{ number_format($balance, 2) }}
                    </div>
                    <div class="text-[11px] text-slate-400 flex items-center gap-2">
                        <span>Currency: <strong class="text-white font-mono">INR</strong></span>
                        <span>•</span>
                        <span>Status: <strong class="{{ $user->isWalletFrozen() ? 'text-rose-400' : 'text-emerald-400' }}">{{ $user->isWalletFrozen() ? 'FROZEN' : 'ACTIVE' }}</strong></span>
                    </div>
                </div>

                <!-- Right: Fast Actions: [+ Add Balance] and [− Deduct Balance] (Two Buttons Only per PRD) -->
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="openModal('add')" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl text-xs font-bold shadow-lg shadow-emerald-600/30 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2">
                        <span class="text-base font-black">+</span> Add Balance
                    </button>

                    <button type="button" @click="openModal('deduct')" class="px-5 py-3 bg-rose-600 hover:bg-rose-500 text-white rounded-2xl text-xs font-bold shadow-lg shadow-rose-600/30 hover:scale-[1.02] active:scale-[0.98] transition flex items-center gap-2">
                        <span class="text-base font-black">−</span> Deduct Balance
                    </button>

                    @if(!$user->isWalletFrozen())
                        <!-- Freeze Modal / Action -->
                        <div x-data="{ openFreeze: false }">
                            <button type="button" @click="openFreeze = true" class="px-4 py-3 bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 rounded-2xl text-xs font-bold transition flex items-center gap-1.5">
                                <span>🔒</span> Freeze
                            </button>

                            <!-- Freeze Reason Modal -->
                            <div x-show="openFreeze" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
                                <div @click.away="openFreeze = false" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
                                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                                        <span>🔒</span> Freeze Agent Wallet
                                    </h3>
                                    <p class="text-xs text-slate-400">
                                        Freezing prevents any further debits or automated bill download charges on this account.
                                    </p>

                                    <form action="{{ route('admin.wallets.toggle-freeze', $user->id) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                                Reason for freezing <span class="text-rose-400">*</span>
                                            </label>
                                            <textarea name="reason" required rows="3" placeholder="Provide audit reason (e.g. Chargeback investigation, suspicious activity, customer dispute)..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-3 focus:ring-rose-500"></textarea>
                                        </div>

                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" @click="openFreeze = false" class="px-4 py-2 text-xs text-slate-400 hover:text-white font-bold">
                                                Cancel
                                            </button>
                                            <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition">
                                                Confirm Freeze
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <form action="{{ route('admin.wallets.toggle-freeze', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-3 bg-emerald-600/20 border border-emerald-500/30 hover:bg-emerald-600/30 text-emerald-300 rounded-2xl text-xs font-bold transition flex items-center gap-1.5">
                                <span>🔓</span> Unfreeze Wallet
                            </button>
                        </form>
                    @endif
                </div>

            </div>

            <!-- Quick Stats Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 border-t border-slate-900 text-xs">
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Total Credited</span>
                    <span class="text-base font-black font-mono text-emerald-400">₹{{ number_format($stats['total_credited'], 2) }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Total Debited</span>
                    <span class="text-base font-black font-mono text-slate-200">₹{{ number_format($stats['total_debited'], 2) }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Ledger Transactions</span>
                    <span class="text-base font-black font-mono text-white">{{ number_format($stats['transaction_count']) }}</span>
                </div>
                <div>
                    <span class="text-slate-500 block text-[10px] uppercase font-bold">Admin Adjustments</span>
                    <span class="text-base font-black font-mono text-indigo-400">{{ number_format($stats['adjustment_count']) }}</span>
                </div>
            </div>
        </div>

        <!-- Referral & Earn Per-Agent Reward Override Box (PRD Section 7) -->
        <div class="glass-card rounded-3xl p-6 border border-slate-800/80 bg-gradient-to-r from-purple-950/20 via-slate-900/40 to-slate-950">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div class="flex items-center gap-3">
                    <span class="p-2.5 rounded-2xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            Referral Reward & Program Override
                            @if(isset($referralOverride['has_override']) && $referralOverride['has_override'])
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Custom Override Active</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700">Platform Default</span>
                            @endif
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Referral Code: <span class="font-mono text-purple-300 font-bold">{{ $referralOverride['coupon']?->code ?? 'Auto-generated on signup' }}</span>
                            • Status: <span class="{{ ($referralOverride['is_active'] ?? true) ? 'text-emerald-400' : 'text-rose-400' }} font-semibold">{{ ($referralOverride['is_active'] ?? true) ? 'Active' : 'Deactivated' }}</span>
                        </p>
                    </div>
                </div>

                @if($referralOverride['coupon'])
                    <form action="{{ route('admin.referrals.coupon.toggle', $referralOverride['coupon']) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3.5 py-1.5 rounded-xl {{ $referralOverride['is_active'] ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }} text-xs font-bold transition">
                            {{ $referralOverride['is_active'] ? '🚫 Deactivate Referral Code' : '✅ Re-activate Referral Code' }}
                        </button>
                    </form>
                @endif
            </div>

            <form action="{{ route('admin.referrals.users.override', $user) }}" method="POST" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Override Reward Type</label>
                    <select name="override_kind" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                        <option value="percentage" {{ ($referralOverride['discount_kind'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage (%) of Payment</option>
                        <option value="flat" {{ ($referralOverride['discount_kind'] ?? '') === 'flat' ? 'selected' : '' }}>Flat Rupee Amount (₹)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Custom Reward Value</label>
                    <input type="number" step="0.01" min="0" name="override_value" value="{{ $referralOverride['discount_value'] ?? '' }}" placeholder="Leave blank to use platform default" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-purple-600/20">
                        <span>💾</span> Save Override
                    </button>
                </div>
            </form>
        </div>

        <!-- 2-Click Balance Adjustment Minimal Modal (PRD Section 7.4) -->
        <div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
            <div @click.away="showModal = false" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <span x-text="adjustmentType === 'add' ? '➕' : '➖'"></span>
                        <span x-text="adjustmentType === 'add' ? 'Add Balance (Credit)' : 'Deduct Balance (Debit)'"></span>
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">✕</button>
                </div>

                <form action="{{ route('admin.wallets.adjust', $user->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="type" :value="adjustmentType">

                    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300">
                        Agent: <strong class="text-white">{{ $user->name }}</strong> (<span class="font-mono text-indigo-300">{{ $user->email }}</span>)<br>
                        Current Balance: <strong class="font-mono text-emerald-400">₹{{ number_format($balance, 2) }}</strong>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Amount (₹) <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" required placeholder="e.g. 500.00" class="w-full text-base font-black font-mono bg-slate-900 border-slate-800 rounded-xl text-white p-3 focus:ring-indigo-500">
                    </div>

                    <!-- Mandatory Reason -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Mandatory Reason / Audit Note <span class="text-rose-400">*</span>
                        </label>
                        <textarea name="reason" x-model="reason" required rows="3" placeholder="Provide clear reason for ledger audit trail (e.g. Billing error reversal, promotional bonus, goodwill refund, offline cash received)..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-3 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button type="button" @click="showModal = false" class="px-4 py-2.5 text-xs text-slate-400 hover:text-white font-bold">
                            Cancel
                        </button>
                        <button type="submit" :class="adjustmentType === 'add' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30' : 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/30'" class="px-5 py-2.5 text-white rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2">
                            <span x-text="adjustmentType === 'add' ? 'Confirm & Add Balance' : 'Confirm & Deduct Balance'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Admin Adjustments History -->
        @if($adjustments->isNotEmpty())
            <div class="bg-slate-950 rounded-2xl border border-slate-800 p-5 space-y-3 shadow-sm">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>⚖️</span> Administrative Adjustment Audit Trail
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/60 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                            <tr>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Admin Operator</th>
                                <th class="py-2.5 px-3">Adjustment</th>
                                <th class="py-2.5 px-3">Amount</th>
                                <th class="py-2.5 px-3">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                            @foreach($adjustments as $adj)
                                @php
                                    $adjTypeVal = $adj->type instanceof \BackedEnum ? $adj->type->value : (string) ($adj->type ?? '');
                                    $isCredit = $adjTypeVal === 'deposit';
                                    $meta = (array) ($adj->meta ?? []);
                                    $adminName = $meta['admin_name'] ?? ('Admin #' . ($meta['admin_id'] ?? ''));
                                    $reason = $meta['reason'] ?? ($meta['description'] ?? '—');
                                @endphp
                                <tr>
                                    <td class="py-2.5 px-3 font-sans text-slate-400 text-[11px]">{{ $adj->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="py-2.5 px-3 font-sans text-white font-bold">{{ $adminName }}</td>
                                    <td class="py-2.5 px-3 font-sans">
                                        @if($isCredit)
                                            <span class="text-emerald-400 font-bold">+ ADD</span>
                                        @else
                                            <span class="text-rose-400 font-bold">− DEDUCT</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-black {{ $isCredit ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $isCredit ? '+' : '−' }}₹{{ number_format(abs((float)$adj->amountFloat), 2) }}
                                    </td>
                                    <td class="py-2.5 px-3 font-sans text-slate-300 text-[11px] max-w-xs truncate">{{ $reason }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Filter & Search Bar for Ledger -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800">
            <form method="GET" action="{{ route('admin.wallets.show', $user->id) }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3 text-xs">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Type</label>
                    <select name="type" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>🟢 Credits Only</option>
                        <option value="debit" {{ ($filters['type'] ?? '') === 'debit' ? 'selected' : '' }}>🔴 Debits Only</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Source</label>
                    <select name="source" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                        <option value="">All Sources</option>
                        <option value="payment_topup" {{ ($filters['source'] ?? '') === 'payment_topup' ? 'selected' : '' }}>Payment Top-Up</option>
                        <option value="admin_adjustment" {{ ($filters['source'] ?? '') === 'admin_adjustment' ? 'selected' : '' }}>Admin Adjustment</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Search</label>
                    <input type="text" name="search" placeholder="Search ref or description..." value="{{ $filters['search'] ?? '' }}" class="w-full text-xs rounded-xl bg-slate-900 border-slate-800 text-white p-2.5 focus:ring-indigo-500">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold transition text-xs shadow-md shadow-indigo-600/30">
                        Filter
                    </button>
                    <a href="{{ route('admin.wallets.show', $user->id) }}" class="p-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-white rounded-xl transition text-xs font-bold">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Ledger Transactions Table -->
        <div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
            <div class="p-4 border-b border-slate-800 flex items-center justify-between">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📜</span> Full Ledger Activity (Immutable)
                </h2>
                <span class="text-xs text-slate-500">Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                        <tr>
                            <th class="py-3 px-4">Tx ID</th>
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Source</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
                        @forelse($transactions as $tx)
                            @php
                                $typeVal = $tx->type instanceof \BackedEnum ? $tx->type->value : (string) $tx->type;
                                $isCredit = $typeVal === 'deposit';
                                $meta = (array) ($tx->meta ?? []);
                                $source = $meta['source'] ?? ($isCredit ? 'Credit' : 'Debit');
                                $desc = $meta['description'] ?? ($meta['reason'] ?? '—');
                            @endphp
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-3 px-4 font-bold text-white">#{{ $tx->id }}</td>
                                <td class="py-3 px-4 font-sans text-slate-400 text-[11px]">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                                <td class="py-3 px-4 font-sans">
                                    @if($isCredit)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">+ CREDIT</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">− DEBIT</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-sans text-indigo-300 font-semibold text-[11px]">{{ ucwords(str_replace('_', ' ', $source)) }}</td>
                                <td class="py-3 px-4 font-black {{ $isCredit ? 'text-emerald-400' : 'text-white' }}">
                                    {{ $isCredit ? '+' : '−' }}₹{{ number_format(abs((float)$tx->amountFloat), 2) }}
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-300 text-[11px] max-w-xs truncate">{{ $desc }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500 font-sans">No transactions recorded for this wallet yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

    </div>
</x-admin-layout>
