{{-- Historical Bills Table --}}
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
