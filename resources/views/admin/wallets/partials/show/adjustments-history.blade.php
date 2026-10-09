{{-- Recent Admin Adjustments History --}}
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
