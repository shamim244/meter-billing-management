<x-app-layout>
    <div class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Breadcrumb & Back -->
            <div class="flex items-center justify-between">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-600 dark:text-cyan-400 hover:underline transition">
                    ← Back to Dashboard
                </a>
                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">CA: {{ $account->ca_number }}</span>
            </div>

            <!-- Consumer Overview Card -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                <div>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-cyan-300 border border-blue-100 dark:border-blue-800/60">
                        {{ $account->mru ? $account->mru->code : 'GENERAL' }}
                    </span>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2">
                        {{ $account->consumer_name ?: 'Consumer Account' }}
                    </h1>
                    <p class="text-xs sm:text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
                        CA Number: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $account->ca_number }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-6 border-t sm:border-t-0 sm:border-l border-slate-100 dark:border-slate-800 pt-3 sm:pt-0 sm:pl-6">
                    <div>
                        <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Total Bills</span>
                        <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono">{{ $bills->count() }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Total Paid/Billed</span>
                        <div class="text-xl sm:text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono">₹{{ number_format($bills->sum('total_amount'), 2) }}</div>
                    </div>
                </div>
            </div>

            <!-- Historical Bills Table -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Billing History Across Months</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Historical records and downloaded PDF bills for this consumer.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-xs uppercase font-bold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="py-3.5 px-6">Billing Period</th>
                                <th class="py-3.5 px-6 text-right">Total Amount</th>
                                <th class="py-3.5 px-6 text-center">Units Consumed</th>
                                <th class="py-3.5 px-6 text-center">Readings (Cur / Prev)</th>
                                <th class="py-3.5 px-6 text-center">Meter No</th>
                                <th class="py-3.5 px-6 text-center">Review Status</th>
                                <th class="py-3.5 px-6 text-center">Official PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @forelse($bills as $bill)
                                @php
                                    $statusKey = "{$bill->billing_year}_{$bill->billing_month}";
                                    $currentStatus = isset($statuses[$statusKey]) ? $statuses[$statusKey]->status : 'pending';
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-4 px-6 font-bold text-slate-900 dark:text-white font-mono">
                                        {{ $bill->bill_month_label ?: date('M, Y', mktime(0, 0, 0, $bill->billing_month, 1, $bill->billing_year)) }}
                                    </td>
                                    <td class="py-4 px-6 text-right font-black text-blue-600 dark:text-cyan-400 font-mono">
                                        ₹{{ number_format($bill->total_amount, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-center font-mono text-xs text-slate-700 dark:text-slate-300">
                                        {{ $bill->units_consumed ?? '—' }} kWh
                                    </td>
                                    <td class="py-4 px-6 text-center font-mono text-xs text-slate-700 dark:text-slate-300">
                                        {{ $bill->current_reading ?? '—' }} / {{ $bill->previous_reading ?? '—' }}
                                    </td>
                                    <td class="py-4 px-6 text-center font-mono text-xs text-slate-500 dark:text-slate-400">
                                        {{ $bill->meter_no ?: '—' }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase
                                            @if($currentStatus === 'submitted') bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300
                                            @elseif($currentStatus === 'critical') bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300
                                            @elseif($currentStatus === 'doubt') bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300
                                            @else bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 @endif">
                                            {{ $currentStatus }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        @if($bill->pdf_path)
                                            <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-900/60 hover:bg-blue-100 dark:hover:bg-blue-800 text-blue-700 dark:text-cyan-300 font-semibold text-xs rounded-xl transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                View PDF
                                            </a>
                                        @else
                                            <span class="text-xs text-slate-400 dark:text-slate-600 italic">No PDF</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-600 text-sm">
                                        No historical bills recorded for this consumer yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
