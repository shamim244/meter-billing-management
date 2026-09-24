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
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-cyan-300 border border-blue-100 dark:border-blue-800/60">
                            {{ $account->mru ? $account->mru->code : 'GENERAL' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Tariff: {{ $account->tariff_category ?: 'DS-II' }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Basis: {{ $account->billing_basis ?: 'OK' }}
                        </span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-2">
                        {{ $account->consumer_name ?: 'Consumer Account' }}
                    </h1>
                    <p class="text-xs sm:text-sm font-mono text-slate-500 dark:text-slate-400 mt-1">
                        CA Number: <span class="font-bold text-slate-800 dark:text-slate-200">{{ $account->ca_number }}</span>
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 pt-3 lg:pt-0 lg:pl-6">
                    <div>
                        <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Total Bills</span>
                        <div class="text-lg sm:text-xl font-black text-slate-900 dark:text-white font-mono">{{ $bills->count() }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Total Billed</span>
                        <div class="text-lg sm:text-xl font-black text-blue-600 dark:text-cyan-400 font-mono">₹{{ number_format($bills->sum('total_amount'), 2) }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Baseline Amt</span>
                        <div class="text-lg sm:text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">₹{{ number_format((float)($account->baseline_amount ?: 0), 2) }}</div>
                    </div>
                    <div>
                        <span class="text-[10px] sm:text-xs text-slate-400 dark:text-slate-500 uppercase font-semibold">Initial Reading</span>
                        <div class="text-lg sm:text-xl font-black text-indigo-600 dark:text-indigo-400 font-mono">{{ $account->baseline_previous_reading !== null ? $account->baseline_previous_reading : ($account->last_working_reading !== null ? $account->last_working_reading : '—') }}</div>
                    </div>
                </div>
            </div>

            <!-- 📊 Dedicated Monthly Meter Reading History (2D Matrix: Official PDF vs Field Working Reading) -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center gap-2">
                                <span>📊</span> Dedicated Monthly Meter Reading History (2D Matrix)
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                                Dual-Source History
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Direct side-by-side comparison of Official NBPDCL PDF bill extractions vs Operator field entries. Working readings take calculation precedence for monthly averages.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0">
                        <div class="px-3.5 py-1.5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-900/60 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500 block">Smart Avg Basis</span>
                            <span class="text-sm font-black text-indigo-700 dark:text-indigo-300 font-mono">{{ $meterMatrix['average_units'] ?? 50 }} kWh</span>
                        </div>
                        <div class="px-3.5 py-1.5 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-center">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Recorded Periods</span>
                            <span class="text-sm font-black text-slate-800 dark:text-slate-200 font-mono">{{ $meterMatrix['periods_count'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="py-3.5 px-4">Billing Month</th>
                                <th class="py-3.5 px-4 text-center">Official PDF Reading</th>
                                <th class="py-3.5 px-4 text-center">PDF Units</th>
                                <th class="py-3.5 px-4 text-center">Field Working Reading</th>
                                <th class="py-3.5 px-4 text-center">Working Units</th>
                                <th class="py-3.5 px-4 text-center">Smart Avg Units Used</th>
                                <th class="py-3.5 px-4 text-center">Basis</th>
                                <th class="py-3.5 px-4 text-center">Cycle Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @forelse($meterMatrix['periods'] ?? [] as $period)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-3 px-4 font-bold text-slate-900 dark:text-white font-mono text-xs">
                                        {{ $period['month_name'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        @if($period['has_pdf'])
                                            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $period['pdf_reading'] ?? '—' }}</span>
                                            @if($period['pdf_previous'])
                                                <span class="text-[10px] text-slate-400 block">Prev: {{ $period['pdf_previous'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400 italic">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        @if($period['pdf_units'] !== null)
                                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $period['pdf_units'] }} kWh</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        @if($period['has_working'])
                                            <span class="font-black text-blue-600 dark:text-cyan-400 bg-blue-50/60 dark:bg-blue-950/60 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-900/60">
                                                {{ $period['working_reading'] }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 italic">Not entered</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        @if($period['working_units'] !== null)
                                            <span class="font-bold text-blue-600 dark:text-cyan-400">{{ $period['working_units'] }} kWh</span>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        <span class="font-black px-2 py-0.5 rounded text-xs {{ $period['has_working'] ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                                            {{ $period['effective_units'] }} kWh
                                        </span>
                                        @if(!empty($period['delta_formula']))
                                            <span class="text-[9px] text-slate-500 font-mono block">{{ $period['delta_formula'] }}</span>
                                        @endif
                                        <span class="text-[9px] text-slate-400 block">Source: {{ $period['has_working'] ? 'Worker (Priority)' : 'PDF Bill' }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase
                                            @if($period['billing_basis'] === 'OK') bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300
                                            @elseif($period['billing_basis'] === 'LK') bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300
                                            @elseif($period['billing_basis'] === 'MD') bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300
                                            @else bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 @endif">
                                            {{ $period['billing_basis'] ?: 'OK' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($period['is_closed'])
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300">
                                                <span>🔒</span> Closed (Submitted)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-cyan-300">
                                                <span>📝</span> Active Cycle
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-6 text-center text-slate-400 dark:text-slate-600 text-xs">
                                        No dual-source meter readings recorded yet. Once bills are parsed or working readings are entered, they will appear here.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Historical Bills Table -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Billing History Across Months</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Historical records, master attributes, and meter readings for this consumer.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-xs uppercase font-bold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="py-3.5 px-4">Billing Period</th>
                                <th class="py-3.5 px-4 text-center">Tariff</th>
                                <th class="py-3.5 px-4 text-center">Basis</th>
                                <th class="py-3.5 px-4 text-right">Total Amount</th>
                                <th class="py-3.5 px-4 text-center">Units Consumed</th>
                                <th class="py-3.5 px-4 text-center">Working Reading</th>
                                <th class="py-3.5 px-4 text-center">Readings (Cur / Prev)</th>
                                <th class="py-3.5 px-4 text-center">Meter No</th>
                                <th class="py-3.5 px-4 text-center">Review Status</th>
                                <th class="py-3.5 px-4 text-center">Official PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @forelse($bills as $bill)
                                @php
                                    $statusKey = "{$bill->billing_year}_{$bill->billing_month}";
                                    $currentStatus = isset($statuses[$statusKey]) ? $statuses[$statusKey]->status : 'pending';
                                    $rowTariff = $bill->tariff_category ?: ($account->tariff_category ?: 'DS-II');
                                    $rowBasis = $bill->billing_basis ?: ($account->billing_basis ?: 'OK');
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-4 px-4 font-bold text-slate-900 dark:text-white font-mono">
                                        {{ $bill->bill_month_label ?: date('M, Y', mktime(0, 0, 0, $bill->billing_month, 1, $bill->billing_year)) }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        {{ $rowTariff }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono text-xs">
                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase
                                            @if($rowBasis === 'OK') bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300
                                            @elseif($rowBasis === 'LK') bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300
                                            @elseif($rowBasis === 'MD') bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300
                                            @else bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 @endif">
                                            {{ $rowBasis }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-right font-black text-blue-600 dark:text-cyan-400 font-mono">
                                        ₹{{ number_format((float)$bill->total_amount, 2) }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono text-xs text-slate-700 dark:text-slate-300">
                                        {{ $bill->units_consumed ?? '—' }} kWh
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono text-xs font-bold text-blue-600 dark:text-cyan-400">
                                        {{ $bill->working_reading ?? '—' }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono text-xs text-slate-700 dark:text-slate-300">
                                        {{ $bill->current_reading ?? '—' }} / {{ $bill->previous_reading ?? '—' }}
                                    </td>
                                    <td class="py-4 px-4 text-center font-mono text-xs text-slate-500 dark:text-slate-400">
                                        {{ $bill->meter_no ?: ($account->meter_no ?: '—') }}
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase
                                            @if($currentStatus === 'submitted') bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300
                                            @elseif($currentStatus === 'critical') bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300
                                            @elseif($currentStatus === 'doubt') bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300
                                            @else bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 @endif">
                                            {{ $currentStatus }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-center">
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
                                    <td colspan="10" class="py-8 text-center text-slate-400 dark:text-slate-600 text-sm">
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
