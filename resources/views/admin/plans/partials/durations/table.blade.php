<!-- Durations Table Card -->
<div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>📋</span> Configured Validity Tiers ({{ $plan->durations->count() }})
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Active durations are immediately available to billing agents during checkout.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-xs font-semibold">
                {{ $plan->durations->where('is_active', true)->count() }} Active
            </span>
            <span class="px-2.5 py-1 bg-slate-800 text-slate-400 rounded-lg text-xs font-semibold">
                {{ $plan->durations->where('is_active', false)->count() }} Disabled
            </span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse">
            <thead>
                <tr class="border-b border-slate-800 text-slate-400 uppercase text-[10px] tracking-wider">
                    <th class="py-3 px-3">Unit</th>
                    <th class="py-3 px-3">Duration & Label</th>
                    <th class="py-3 px-3">Discount %</th>
                    <th class="py-3 px-3">Payable Price (₹)</th>
                    <th class="py-3 px-3">MRU Overage Rate</th>
                    <th class="py-3 px-3">Consumer Overage Rate</th>
                    <th class="py-3 px-3 text-center">Status</th>
                    <th class="py-3 px-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
                @forelse($plan->durations as $dur)
                    <tr class="hover:bg-slate-800/30 transition {{ !$dur->is_active ? 'opacity-60 bg-slate-950/40' : '' }}">
                        <!-- Unit Badge -->
                        <td class="py-3 px-3">
                            @if($dur->duration_unit === 'day')
                                <span class="px-2 py-0.5 bg-amber-500/10 border border-amber-500/30 text-amber-300 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                    ⏱️ DAYS
                                </span>
                            @else
                                <span class="px-2 py-0.5 bg-indigo-500/10 border border-indigo-500/30 text-indigo-300 rounded-md text-[10px] font-bold uppercase tracking-wider">
                                    📅 MONTHS
                                </span>
                            @endif
                        </td>

                        <!-- Duration & Label -->
                        <td class="py-3 px-3">
                            <div class="font-bold text-white text-sm">
                                {{ $dur->formatted_duration }}
                            </div>
                            @if($dur->name)
                                <div class="text-[10px] text-slate-400">{{ $dur->name }}</div>
                            @endif
                        </td>

                        <!-- Discount % -->
                        <td class="py-3 px-3">
                            @if($dur->discount_percent > 0)
                                <span class="px-2 py-0.5 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold rounded-md text-[11px]">
                                    {{ $dur->discount_percent }}% OFF
                                </span>
                            @else
                                <span class="text-slate-500">—</span>
                            @endif
                        </td>

                        <!-- Final Price -->
                        <td class="py-3 px-3 font-mono font-black text-sm text-emerald-400">
                            ₹{{ number_format($dur->final_price, 2) }}
                        </td>

                        <!-- Extra MRU Rate -->
                        <td class="py-3 px-3 font-mono text-slate-300">
                            @if($dur->extra_mru_rate !== null)
                                <span class="text-amber-400 font-semibold">₹{{ number_format($dur->extra_mru_rate, 2) }}</span>
                                <span class="text-[9px] text-slate-500 block">Custom Override</span>
                            @else
                                <span class="text-slate-400">₹{{ number_format($plan->extra_mru_rate, 2) }}</span>
                                <span class="text-[9px] text-slate-500 block">Base Rate</span>
                            @endif
                        </td>

                        <!-- Extra Consumer Rate -->
                        <td class="py-3 px-3 font-mono text-slate-300">
                            @if($dur->extra_consumer_rate !== null)
                                <span class="text-amber-400 font-semibold">₹{{ number_format($dur->extra_consumer_rate, 2) }}</span>
                                <span class="text-[9px] text-slate-500 block">Custom Override</span>
                            @else
                                <span class="text-slate-400">₹{{ number_format($plan->extra_consumer_rate, 2) }}</span>
                                <span class="text-[9px] text-slate-500 block">Base Rate</span>
                            @endif
                        </td>

                        <!-- Status Toggle -->
                        <td class="py-3 px-3 text-center">
                            <form method="POST" action="{{ route('admin.plans.durations.toggle', [$plan, $dur]) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="Click to Toggle Active State" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase transition flex items-center gap-1 mx-auto cursor-pointer {{ $dur->is_active ? 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 border border-slate-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $dur->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                    {{ $dur->is_active ? 'ACTIVE' : 'DISABLED' }}
                                </button>
                            </form>
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="openEditModal({{ json_encode($dur) }})" class="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition border border-slate-700 cursor-pointer" title="Edit Duration">
                                    ✏️
                                </button>

                                @if($plan->durations->count() > 1)
                                    <form method="POST" action="{{ route('admin.plans.durations.destroy', [$plan, $dur]) }}" onsubmit="return confirm('Are you sure you want to delete this duration tier?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg transition border border-rose-500/20 cursor-pointer" title="Delete Duration">
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-10 text-slate-500">
                            No durations configured for this plan. Click "+ Add New Duration" to get started.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
