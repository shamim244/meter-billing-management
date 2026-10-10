<!-- Wallets Table -->
<div class="bg-slate-950 rounded-2xl border border-slate-800 overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-900/80 text-[10px] uppercase text-slate-400 border-b border-slate-800 font-bold">
                <tr>
                    <th class="py-3 px-4">Agent / User</th>
                    <th class="py-3 px-4">Current Balance</th>
                    <th class="py-3 px-4">Plan Tier</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono">
                @forelse($users as $u)
                    @php
                        $balance = (float) $u->balanceFloat;
                        $isFrozen = $u->isWalletFrozen();
                    @endphp
                    <tr class="hover:bg-slate-900/40 transition">
                        <td class="py-3.5 px-4 font-sans">
                            <div class="font-bold text-white">{{ $u->name }}</div>
                            <div class="text-[11px] text-slate-500">{{ $u->email }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="text-sm font-black {{ $balance < 0 ? 'text-rose-400' : ($balance < 200 ? 'text-amber-400' : 'text-emerald-400') }}">
                                ₹{{ number_format($balance, 2) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 font-sans">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 uppercase">
                                {{ $u->plan_tier ?? 'free' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 font-sans">
                            @if($isFrozen)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                    🔒 FROZEN
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    ACTIVE
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right font-sans">
                            <a href="{{ route('admin.wallets.show', $u->id) }}" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 rounded-xl text-xs font-bold transition inline-flex items-center gap-1">
                                <span>⚙️</span> Manage Wallet →
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-500 font-sans">No agent wallets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $users->links() }}
        </div>
    @endif
</div>
