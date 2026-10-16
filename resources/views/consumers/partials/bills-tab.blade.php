{{-- Bill Ledger & History Tab --}}
<div x-show="activeTab === 'bills'" class="space-y-4">
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Billing History Across Months ({{ $bills->count() }})</h3>
            <span class="text-xs font-mono text-slate-500">CA: {{ $account->ca_number }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 text-slate-500 font-bold">
                        <th class="py-2.5 px-4">Period</th>
                        <th class="py-2.5 px-3 text-right">Units (kWh)</th>
                        <th class="py-2.5 px-3 text-right">Amount (₹)</th>
                        <th class="py-2.5 px-3 text-right">Working Read</th>
                        <th class="py-2.5 px-3 text-right">PDF Read</th>
                        <th class="py-2.5 px-3 text-center">Basis</th>
                        <th class="py-2.5 px-3 text-center">Status</th>
                        <th class="py-2.5 px-4 text-center">PDF</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                    @forelse($bills as $b)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
                            <td class="py-2.5 px-4 font-bold font-mono text-slate-900 dark:text-white">
                                {{ DateTime::createFromFormat('!m', $b->billing_month)->format('M') }}, {{ $b->billing_year }}
                            </td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold">{{ $b->units_consumed ?? 0 }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">₹{{ number_format((float)$b->total_amount, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-cyan-500 font-bold">{{ $b->working_reading ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-400">{{ $b->current_reading ?: '—' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $b->billing_basis ?: 'OK' }}</td>
                            <td class="py-2.5 px-3 text-center">
                                @php
                                    $st = $statuses->get("{$b->billing_year}_{$b->billing_month}")?->status ?? $b->review_status ?? 'pending';
                                @endphp
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase
                                      {{ $st === 'submitted' ? 'bg-emerald-500/10 text-emerald-500' : ($st === 'critical' ? 'bg-rose-500/10 text-rose-500' : ($st === 'doubt' ? 'bg-amber-500/10 text-amber-500' : 'bg-slate-500/10 text-slate-400')) }}">
                                    {{ $st }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-center">
                                @if($b->pdf_path)
                                    <a href="{{ route('bills.pdf', $b->id) }}" target="_blank" class="p-1 rounded-md text-brand-600 dark:text-cyan-400 hover:bg-brand-50 dark:hover:bg-brand-950/40 font-bold" title="View PDF">
                                        📄 View
                                    </a>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">No PDF</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                No bill records found for this consumer.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
