<x-app-layout>
    <div class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Top Navigation & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div>
                    <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>💳</span> Payment & Balance Ledger
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Track your wallet top-ups, subscription payments, and proof submissions.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('payments.sandbox') }}" class="px-4 py-2.5 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <span>🧪</span> Test Sandbox Mode
                    </a>
                    <a href="{{ route('payments.create') }}" class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                        <span>⚡</span> Make a Payment / Top-Up
                    </a>
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <span>✅ {{ session('success') }}</span>
                    <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400">✕</button>
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <span>ℹ️ {{ session('info') }}</span>
                    <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400">✕</button>
                </div>
            @endif

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Paid</span>
                        <span class="text-lg">💰</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">₹{{ number_format($stats['total_paid'], 2) }}</div>
                    <span class="text-[11px] text-slate-400">Successful payments to date</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Verification</span>
                        <span class="text-lg">⏳</span>
                    </div>
                    <div class="text-2xl font-black text-amber-500 mt-1">{{ $stats['pending_verification'] }}</div>
                    <span class="text-[11px] text-slate-400">Awaiting admin review</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Last Transaction</span>
                        <span class="text-lg">⚡</span>
                    </div>
                    <div class="text-sm font-bold text-slate-900 dark:text-white mt-2">
                        @if($stats['recent_success'])
                            ₹{{ number_format((float)$stats['recent_success']->amount, 2) }} <span class="text-slate-400 font-normal text-xs">on {{ $stats['recent_success']->created_at->format('d M Y') }}</span>
                        @else
                            <span class="text-slate-400">No transactions yet</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-slate-400">Most recent payment</span>
                </div>
            </div>

            <!-- Ledger Table -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Transaction History</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/50 text-[11px] uppercase font-bold text-slate-400 border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="px-5 py-3.5">ID & Date</th>
                                <th class="px-5 py-3.5">Mode</th>
                                <th class="px-5 py-3.5">Purpose</th>
                                <th class="px-5 py-3.5">Amount</th>
                                <th class="px-5 py-3.5">Reference / UTR</th>
                                <th class="px-5 py-3.5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($payments as $payment)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="font-mono font-bold text-slate-900 dark:text-white">#{{ $payment->id }}</div>
                                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $payment->created_at->format('d M Y, h:i A') }}</div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold">
                                            <span>{{ $payment->mode->icon() }}</span>
                                            <span>{{ $payment->mode->label() }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap font-medium text-slate-700 dark:text-slate-300">
                                        {{ $payment->purpose->label() }}
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap font-mono font-black text-sm text-slate-900 dark:text-white">
                                        ₹{{ number_format((float)$payment->amount, 2) }}
                                    </td>
                                    <td class="px-5 py-4">
                                        @if($payment->utr_number)
                                            <div class="font-mono text-cyan-600 dark:text-cyan-400 font-bold">UTR: {{ $payment->utr_number }}</div>
                                        @elseif($payment->bank_reference)
                                            <div class="font-mono text-purple-600 dark:text-purple-400 font-bold">Ref: {{ $payment->bank_reference }}</div>
                                        @elseif($payment->gateway_payment_id)
                                            <div class="font-mono text-emerald-600 dark:text-emerald-400">PG: {{ $payment->gateway_payment_id }}</div>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        @if($payment->status->value === 'pending_verification')
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 inline-flex items-center gap-1">
                                                <span>⏳</span> Pending Admin Verification
                                            </span>
                                        @elseif($payment->status->value === 'success')
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 inline-flex items-center gap-1">
                                                <span>✅</span> Successful
                                            </span>
                                        @elseif($payment->status->value === 'rejected')
                                            <div>
                                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 inline-flex items-center gap-1">
                                                    <span>❌</span> Rejected
                                                </span>
                                                @if($payment->rejection_reason)
                                                    <div class="text-[11px] text-rose-600 dark:text-rose-400 mt-1 max-w-xs">
                                                        Reason: {{ $payment->rejection_reason }}
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400">
                                                {{ $payment->status->label() }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                        <div class="text-3xl mb-2">💳</div>
                                        <p class="font-bold text-sm text-slate-700 dark:text-slate-300">No payment records yet</p>
                                        <p class="text-xs text-slate-500 mt-0.5">Top-up your wallet or pay for a subscription to get started.</p>
                                        <a href="{{ route('payments.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-bold shadow transition">
                                            <span>⚡</span> Initiate Payment
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($payments->hasPages())
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                        {{ $payments->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
