<x-admin-layout>
    <x-slot name="header">
        Financial Analytics & Revenue Performance
    </x-slot>

    <div class="space-y-6">

        <!-- Top Navigation Tabs -->
        @include('admin.payments.nav')

        <!-- Primary Financial KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- All Time Collections -->
            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Revenue</span>
                    <span class="text-lg">💰</span>
                </div>
                <div class="text-2xl font-black text-emerald-400 font-mono">₹{{ number_format($totalCollected, 2) }}</div>
                <span class="text-[11px] text-slate-500">All-time successful collections</span>
            </div>

            <!-- This Month Collections -->
            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">This Month</span>
                    <span class="text-lg">📈</span>
                </div>
                <div class="text-2xl font-black text-cyan-400 font-mono">₹{{ number_format($monthCollected, 2) }}</div>
                <span class="text-[11px] text-slate-500">Month-to-date collections</span>
            </div>

            <!-- Today Collections -->
            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today</span>
                    <span class="text-lg">⚡</span>
                </div>
                <div class="text-2xl font-black text-indigo-400 font-mono">₹{{ number_format($todayCollected, 2) }}</div>
                <span class="text-[11px] text-slate-500">Processed in the last 24h</span>
            </div>

            <!-- Success Rate -->
            <div class="bg-slate-950 p-5 rounded-2xl border border-slate-800 space-y-1">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Success Rate</span>
                    <span class="text-lg">🎯</span>
                </div>
                <div class="text-2xl font-black text-purple-400 font-mono">{{ $successRate }}%</div>
                <span class="text-[11px] text-slate-500">Avg ticket: ₹{{ number_format($avgTicketSize, 2) }}</span>
            </div>
        </div>

        <!-- Volume & Breakdown Grids -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Payment Method Distribution -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>💳</span> Payment Channel Distribution
                </h3>
                <p class="text-xs text-slate-400">Total volume and revenue collected across each payment method.</p>

                <div class="space-y-3">
                    <!-- PG Gateway -->
                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center text-lg font-bold">⚡</div>
                            <div>
                                <div class="font-bold text-xs text-white">Instant PG (Cashfree / Razorpay)</div>
                                <div class="text-[11px] text-slate-400">{{ $modeBreakdown['pg']['count'] }} successful transactions</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-black text-white text-sm">₹{{ number_format($modeBreakdown['pg']['amount'], 2) }}</div>
                            <span class="text-[10px] text-emerald-400 font-semibold">Auto-verified</span>
                        </div>
                    </div>

                    <!-- Manual UPI -->
                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center text-lg font-bold">📱</div>
                            <div>
                                <div class="font-bold text-xs text-white">Manual UPI Transfers</div>
                                <div class="text-[11px] text-slate-400">{{ $modeBreakdown['manual_upi']['count'] }} verified payments</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-black text-white text-sm">₹{{ number_format($modeBreakdown['manual_upi']['amount'], 2) }}</div>
                            <span class="text-[10px] text-purple-400 font-semibold">Zero Fee UPI</span>
                        </div>
                    </div>

                    <!-- Bank Transfer -->
                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-lg font-bold">🏦</div>
                            <div>
                                <div class="font-bold text-xs text-white">Bank Transfer (NEFT / IMPS)</div>
                                <div class="text-[11px] text-slate-400">{{ $modeBreakdown['bank_transfer']['count'] }} verified deposits</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-black text-white text-sm">₹{{ number_format($modeBreakdown['bank_transfer']['amount'], 2) }}</div>
                            <span class="text-[10px] text-blue-400 font-semibold">High Value</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Status & Health Breakdown -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📊</span> Transaction Status Breakdown
                </h3>
                <p class="text-xs text-slate-400">Total pipeline count of all payments processed on the platform.</p>

                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block mb-1">Successful (Credited)</span>
                        <div class="text-xl font-black text-emerald-400 font-mono">{{ $successCount }}</div>
                    </div>

                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block mb-1">Pending Verification</span>
                        <div class="text-xl font-black text-amber-400 font-mono">{{ $pendingCount }}</div>
                    </div>

                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block mb-1">Rejected Manual Claims</span>
                        <div class="text-xl font-black text-rose-400 font-mono">{{ $rejectedCount }}</div>
                    </div>

                    <div class="p-4 bg-slate-900 rounded-xl border border-slate-800">
                        <span class="text-[11px] text-slate-400 block mb-1">Gateway Failed / Dropped</span>
                        <div class="text-xl font-black text-slate-400 font-mono">{{ $failedCount }}</div>
                    </div>
                </div>

                <!-- Purpose Breakdown -->
                <div class="pt-2 border-t border-slate-900 space-y-2">
                    <span class="text-xs font-bold text-slate-300 block">Revenue by Purpose</span>
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                            <span class="text-[10px] text-slate-400 block">👛 Wallet Top-Ups</span>
                            <span class="font-mono font-bold text-white">₹{{ number_format($purposeBreakdown['wallet_topup']['amount'], 2) }}</span>
                            <span class="text-[10px] text-slate-500 block">({{ $purposeBreakdown['wallet_topup']['count'] }} orders)</span>
                        </div>
                        <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800/80">
                            <span class="text-[10px] text-slate-400 block">⭐ Direct Subscriptions</span>
                            <span class="font-mono font-bold text-white">₹{{ number_format($purposeBreakdown['direct_subscription']['amount'], 2) }}</span>
                            <span class="text-[10px] text-slate-500 block">({{ $purposeBreakdown['direct_subscription']['count'] }} orders)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Trends & Top Billing Agents -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Monthly Collections Trend -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>📅</span> 6-Month Revenue Trend
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                            <tr>
                                <th class="py-2.5 px-3">Month</th>
                                <th class="py-2.5 px-3">Transactions</th>
                                <th class="py-2.5 px-3 text-right">Revenue Collected</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            @foreach($monthlyTrend as $m)
                                <tr class="hover:bg-slate-900/40">
                                    <td class="py-2.5 px-3 font-sans font-bold text-white">{{ $m['month'] }}</td>
                                    <td class="py-2.5 px-3 text-slate-400">{{ $m['count'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-black text-cyan-400">₹{{ number_format($m['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Spending Billing Agents -->
            <div class="bg-slate-950 p-6 rounded-2xl border border-slate-800 space-y-4">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 flex items-center gap-2">
                    <span>👑</span> Top Billing Agents by Volume
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                            <tr>
                                <th class="py-2.5 px-3">Billing Agent</th>
                                <th class="py-2.5 px-3">Payments</th>
                                <th class="py-2.5 px-3 text-right">Total Contributed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            @forelse($topAgents as $agent)
                                <tr class="hover:bg-slate-900/40">
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-white">{{ $agent->user->name ?? 'Agent #' . $agent->user_id }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $agent->user->email ?? '' }}</div>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-slate-400">{{ $agent->transaction_count }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-emerald-400">₹{{ number_format((float)$agent->total_spent, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500">No payment data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
