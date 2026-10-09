{{-- Subscription & Plan Transition History Table --}}
@if(isset($subscriptionHistory) && $subscriptionHistory->isNotEmpty())
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📜</span> Subscription & Plan Transition History
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Audit log of active and previous plan durations, renewals, upgrades, and downgrades.
                </p>
            </div>
            <a href="{{ route('wallet.index') }}" class="text-xs font-bold text-indigo-600 dark:text-cyan-400 hover:underline flex items-center gap-1">
                <span>👛 View Wallet Ledger →</span>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                        <th class="py-3 px-3">Plan Name</th>
                        <th class="py-3 px-3">Duration</th>
                        <th class="py-3 px-3">Billing Period</th>
                        <th class="py-3 px-3">Locked Quota</th>
                        <th class="py-3 px-3 text-right">Price Paid</th>
                        <th class="py-3 px-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
                    @foreach($subscriptionHistory as $histSub)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3 px-3 font-bold text-slate-900 dark:text-white">
                                {{ $histSub->plan?->name ?? 'Custom Plan' }}
                            </td>
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-300 font-mono">
                                {{ $histSub->formatted_duration }}
                            </td>
                            <td class="py-3 px-3 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                {{ $histSub->billing_start ? $histSub->billing_start->format('M d, Y') : '—' }}
                                <span class="text-slate-400">→</span>
                                <span class="{{ $histSub->billing_end && $histSub->billing_end > now() ? 'text-emerald-600 dark:text-emerald-400 font-bold' : '' }}">
                                    {{ $histSub->billing_end ? $histSub->billing_end->format('M d, Y') : '—' }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-slate-600 dark:text-slate-300 font-mono text-[11px]">
                                {{ $histSub->included_mrus_locked }} MRUs / {{ number_format($histSub->included_consumers_locked) }} CAs
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                ₹{{ number_format($histSub->base_price_paid, 2) }}
                            </td>
                            <td class="py-3 px-3 text-center">
                                @if($histSub->status === 'active' && $histSub->billing_end > now())
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        ● Active
                                    </span>
                                @elseif($histSub->status === 'upgraded')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-50 dark:bg-purple-950/80 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                        🚀 Upgraded
                                    </span>
                                @elseif($histSub->status === 'downgraded')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        🔄 Downgraded
                                    </span>
                                @elseif($histSub->status === 'migrated')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-50 dark:bg-blue-950/80 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                                        Migrated
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        Expired
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
