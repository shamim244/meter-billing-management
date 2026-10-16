{{-- Reading Gap & Reconciliation Tab --}}
<div x-show="activeTab === 'gap'" class="space-y-6">
    <!-- Gap Overview Status Banner -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xl">⚖️</span>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Reading Reconciliation Intelligence</h3>
                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono"
                      :class="{
                          'bg-amber-500/15 text-amber-500 border border-amber-500/30': '{{ $gapSummary['gap_status'] }}' === 'over_posted',
                          'bg-rose-500/15 text-rose-500 border border-rose-500/30': '{{ $gapSummary['gap_status'] }}' === 'under_posted',
                          'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30': '{{ $gapSummary['gap_status'] }}' === 'in_sync',
                          'bg-slate-500/15 text-slate-400 border border-slate-500/30': '{{ $gapSummary['gap_status'] }}' === 'uninspected'
                      }">
                    {{ $gapSummary['badge_label'] }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Last physical inspection was on {{ $account->last_inspected_at ? $account->last_inspected_at->format('d M Y') : 'never' }}.
                @if($gapSummary['gap_status'] === 'under_posted')
                    Controlled recovery active: <strong class="text-slate-800 dark:text-slate-200">+{{ $gapSummary['gap_slice_applied'] }} kWh/mo</strong> over ~{{ $gapSummary['estimated_recovery_cycles'] }} cycles.
                @elseif($gapSummary['gap_status'] === 'over_posted')
                    Meter is currently running behind posted billing by <strong class="text-amber-500">{{ abs($gapSummary['net_gap']) }} kWh</strong>.
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openInspectionModal()" class="px-3.5 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center gap-1.5 transition cursor-pointer">
                <span>+</span> Record Physical Visit
            </button>
        </div>
    </div>

    <!-- Historical Visits Audit List -->
    <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Physical Visit Audit Trail ({{ $inspections->count() }})</h3>
            @if($inspections->isNotEmpty())
                <button type="button" @click="clearAllInspections()" class="text-xs font-bold text-rose-500 hover:underline cursor-pointer">
                    Clear All Visits
                </button>
            @endif
        </div>

        @if($inspections->isEmpty())
            <div class="py-8 text-center text-xs text-slate-500">
                No physical visits recorded yet for this consumer.
            </div>
        @else
            <div class="space-y-2">
                @foreach($inspections as $item)
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 flex items-center justify-between text-xs">
                        <div>
                            <div class="flex items-center gap-2 font-mono">
                                <span class="font-bold text-slate-900 dark:text-cyan-400">{{ $item->physical_reading }} kWh</span>
                                <span class="text-slate-400">•</span>
                                <span class="text-slate-600 dark:text-slate-300">{{ $item->inspected_at ? $item->inspected_at->format('d M Y') : 'N/A' }}</span>
                                <span class="text-[10px] text-slate-400">({{ $item->inspected_at ? $item->inspected_at->diffForHumans() : '' }})</span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $item->notes ?: 'Routine physical verification' }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="deleteSingleInspection({{ $item->id }})" class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-200 transition" title="Delete">🗑️</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
