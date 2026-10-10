<!-- Global Bills Table -->
<div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-900 border-b border-slate-800 text-xs uppercase font-bold text-slate-500 tracking-wider">
                <tr>
                    <th class="py-4 px-6">CA Number</th>
                    <th class="py-4 px-6">Consumer Name</th>
                    <th class="py-4 px-6">Tenant Owner</th>
                    <th class="py-4 px-6">MRU / Area</th>
                    <th class="py-4 px-6 text-right">Amount</th>
                    <th class="py-4 px-6 text-center">Period</th>
                    <th class="py-4 px-6 text-center">Units</th>
                    <th class="py-4 px-6 text-center">PDF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80 font-medium">
                @forelse($bills as $bill)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-4 px-6 font-mono font-bold text-indigo-400">
                            {{ $bill->ca_number }}
                        </td>
                        <td class="py-4 px-6 text-white font-semibold truncate max-w-[200px]">
                            {{ $bill->consumer_name ?: '—' }}
                        </td>
                        <td class="py-4 px-6 text-xs text-slate-400">
                            {{ $bill->user ? $bill->user->name : 'Unknown' }}
                            <span class="block text-[11px] text-slate-600">{{ $bill->user ? $bill->user->email : '' }}</span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="px-2.5 py-1 rounded text-xs font-mono font-semibold bg-slate-900 text-cyan-300 border border-slate-800">
                                {{ $bill->mru ? $bill->mru->code : 'UNKNOWN' }}
                            </span>
                        </td>
                        <td class="py-4 px-6 text-right font-extrabold text-white">
                            ₹{{ number_format($bill->total_amount, 2) }}
                        </td>
                        <td class="py-4 px-6 text-center font-mono text-xs text-slate-400">
                            {{ $bill->billing_month }}/{{ $bill->billing_year }}
                        </td>
                        <td class="py-4 px-6 text-center font-mono text-xs">
                            {{ $bill->units_consumed ?? '—' }}
                        </td>
                        <td class="py-4 px-6 text-center">
                            @if($bill->pdf_path)
                                <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="px-2.5 py-1 rounded-lg text-xs font-bold bg-indigo-950 hover:bg-indigo-900 text-indigo-300 border border-indigo-500/20 transition">
                                    PDF
                                </a>
                            @else
                                <span class="text-xs text-slate-600 italic">No File</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-500">
                            No bills found matching search and filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($bills->hasPages())
        <div class="px-6 py-4 border-t border-slate-800 bg-slate-900/60">
            {{ $bills->withQueryString()->links() }}
        </div>
    @endif
</div>
