<!-- Subscribers Table Card -->
<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Subscribers Ledger</h2>
            <p class="text-xs text-slate-400 mt-0.5">Every row represents a legally binding locked pricing snapshot.</p>
        </div>
        <span class="px-3 py-1 bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 rounded-lg text-xs font-bold">
            {{ $subscriptions->total() }} Total Subscriptions
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3.5 px-4 font-semibold">Agent / User</th>
                    <th class="py-3.5 px-4 font-semibold">Duration & Price Paid</th>
                    <th class="py-3.5 px-4 font-semibold">Locked MRU Quota</th>
                    <th class="py-3.5 px-4 font-semibold">Locked CA Quota</th>
                    <th class="py-3.5 px-4 font-semibold">Active Period</th>
                    <th class="py-3.5 px-4 font-semibold">Status</th>
                    <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                @forelse($subscriptions as $sub)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-white">{{ $sub->user?->name ?? 'Deleted User' }}</div>
                            <div class="text-[11px] text-slate-500 font-mono">{{ $sub->user?->email }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-emerald-400">₹{{ number_format($sub->base_price_paid, 2) }}</div>
                            <div class="text-[10px] text-slate-400">{{ $sub->duration_months }} Month(s)</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-white">{{ $sub->included_mrus_locked }} MRUs</span>
                            <div class="text-[10px] text-amber-400">₹{{ number_format($sub->extra_mru_rate_locked, 2) }}/extra</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-white">{{ $sub->included_consumers_locked }} CAs</span>
                            <div class="text-[10px] text-amber-400">₹{{ number_format($sub->extra_consumer_rate_locked, 2) }}/extra</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="text-white">{{ $sub->billing_start?->format('d M Y') }}</div>
                            <div class="text-[10px] text-slate-500">to {{ $sub->billing_end?->format('d M Y') }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($sub->isActive())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    ACTIVE
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-800 text-slate-400 border border-slate-700">
                                    {{ strtoupper($sub->status) }}
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            @if($sub->user)
                                <button type="button" @click="openMigrate({{ $sub->user->id }}, '{{ addslashes($sub->user->name) }}')" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 rounded-lg text-xs font-semibold border border-indigo-500/30 transition">
                                    🔄 Migrate Plan
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500 italic">
                            No active subscribers currently on this plan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($subscriptions->hasPages())
        <div class="p-4 border-t border-slate-800">
            {{ $subscriptions->links() }}
        </div>
    @endif
</div>
