<div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-xl overflow-hidden space-y-4 p-6">
    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>📜</span> Redemption Audit Logs ({{ $redemptions->total() }})
        </h2>
        <span class="text-xs text-slate-400 font-medium">Immutable transaction ledger</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-slate-400 uppercase font-bold text-[10px]">
                <tr>
                    <th class="py-3 px-4">Operator / Agent</th>
                    <th class="py-3 px-4">Action Type</th>
                    <th class="py-3 px-4 text-center">Original ₹</th>
                    <th class="py-3 px-4 text-center">Discount/Bonus ₹</th>
                    <th class="py-3 px-4 text-center">Final Amount ₹</th>
                    <th class="py-3 px-4 text-center">Redeemed Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-medium">
                @forelse($redemptions as $redemption)
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3 px-4">
                            @if($redemption->user)
                                <a href="{{ route('admin.users.show', $redemption->user) }}" class="font-bold text-white hover:text-indigo-400 transition">
                                    {{ $redemption->user->name }}
                                </a>
                                <div class="text-[10px] text-slate-400">{{ $redemption->user->email }}</div>
                            @else
                                <span class="text-slate-500">Deleted User (#{{ $redemption->user_id }})</span>
                            @endif
                        </td>

                        <td class="py-3 px-4 capitalize">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $redemption->redeemed_for_type === 'subscription_payment' ? 'bg-purple-950 text-purple-300 border border-purple-500/30' : 'bg-cyan-950 text-cyan-300 border border-cyan-500/30' }}">
                                {{ str_replace('_', ' ', $redemption->redeemed_for_type) }}
                            </span>
                        </td>

                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-300">
                            ₹{{ number_format($redemption->original_amount, 2) }}
                        </td>

                        <td class="py-3 px-4 text-center font-mono font-bold text-emerald-400">
                            {{ $redemption->redeemed_for_type === 'subscription_payment' ? '-' : '+' }}₹{{ number_format($redemption->discount_or_bonus_amount, 2) }}
                        </td>

                        <td class="py-3 px-4 text-center font-mono font-black text-white">
                            ₹{{ number_format($redemption->final_amount, 2) }}
                        </td>

                        <td class="py-3 px-4 text-center text-slate-400 font-mono text-[11px]">
                            {{ $redemption->redeemed_at->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">
                            No agents have redeemed this coupon code yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($redemptions->hasPages())
        <div class="pt-4 border-t border-slate-800">
            {{ $redemptions->links() }}
        </div>
    @endif
</div>
