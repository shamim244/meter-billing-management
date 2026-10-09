<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
    <div class="p-5 border-b border-slate-800/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-white uppercase tracking-wider">Agent Subscriptions Ledger</h2>
            <p class="text-xs text-slate-400 mt-0.5">Real-time state machine tracking and access controls.</p>
        </div>

        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="flex flex-wrap items-center gap-2.5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search agent name/email..." class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500 w-48">
            <select name="lifecycle_status" onchange="this.form.submit()" class="text-xs bg-slate-950 border-slate-800 rounded-xl text-white py-1.5 px-3 focus:ring-indigo-500">
                <option value="">All States</option>
                <option value="active" {{ request('lifecycle_status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="renewal_due" {{ request('lifecycle_status') === 'renewal_due' ? 'selected' : '' }}>Renewal Due</option>
                <option value="grace_period" {{ request('lifecycle_status') === 'grace_period' ? 'selected' : '' }}>Grace Period</option>
                <option value="suspended" {{ request('lifecycle_status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="bg-slate-950/80 border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3.5 px-4 font-semibold">Agent / User</th>
                    <th class="py-3.5 px-4 font-semibold">Plan & Paid Rate</th>
                    <th class="py-3.5 px-4 font-semibold">Lifecycle State</th>
                    <th class="py-3.5 px-4 font-semibold">Billing Window</th>
                    <th class="py-3.5 px-4 font-semibold">Grace Window</th>
                    <th class="py-3.5 px-4 font-semibold">Auto-Renewal</th>
                    <th class="py-3.5 px-4 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                @forelse($subscriptions as $sub)
                    <tr class="hover:bg-slate-800/30 transition">
                        <td class="py-3.5 px-4">
                            <div class="font-bold text-white">{{ $sub->user?->name ?? 'User #' . $sub->user_id }}</div>
                            <div class="text-[10px] text-slate-500 font-mono">{{ $sub->user?->email }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="font-bold text-white">{{ $sub->plan?->name ?? 'Custom Tier' }}</span>
                            <div class="text-[10px] text-emerald-400">₹{{ number_format($sub->base_price_paid, 2) }} / {{ $sub->duration_months }}M</div>
                        </td>
                        <td class="py-3.5 px-4">
                            @php
                                $stateBadge = match($sub->lifecycle_status) {
                                    'active' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                    'renewal_due' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                    'grace_period' => 'bg-orange-500/20 text-orange-300 border-orange-500/30',
                                    'suspended' => 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                                    default => 'bg-slate-800 text-slate-400 border-slate-700',
                                };
                            @endphp
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $stateBadge }}">
                                {{ str_replace('_', ' ', $sub->lifecycle_status) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="text-white">{{ $sub->billing_start?->format('d M Y') }}</div>
                            <div class="text-[10px] text-slate-500">to {{ $sub->billing_end?->format('d M Y') }}</div>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($sub->grace_period_ends_at)
                                <div class="text-amber-300 font-semibold">{{ $sub->grace_period_ends_at->format('d M Y, h:i A') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $sub->grace_period_days }} Days Allowed</div>
                            @else
                                <span class="text-slate-500 text-[11px]">N/A (Active)</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if($sub->auto_renewal_enabled)
                                <span class="px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-md text-[10px] font-bold">
                                    ON (Wallet)
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-slate-800 text-slate-400 border border-slate-700 rounded-md text-[10px]">
                                    Manual
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <button type="button" @click="openOverride({{ $sub->id }}, '{{ addslashes($sub->user?->name ?? 'User') }}', '{{ $sub->lifecycle_status }}')" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 rounded-lg text-xs font-semibold border border-indigo-500/30 transition">
                                ⚡ Override State
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500 italic">
                            No agent subscriptions found matching your filters.
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
