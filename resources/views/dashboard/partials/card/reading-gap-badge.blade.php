<!-- Reading Gap & Physical Meter Status Badge (Module 13) -->
<div class="inline-flex items-center gap-1 gap-status-badge">
    <template x-if="bill.reading_gap_info && bill.reading_gap_info.gap_status === 'over_posted'">
        <button type="button"
                @click.stop="openInspectionModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 select-none active:scale-95 cursor-pointer"
                :title="'Over-Posted reading by ' + Math.abs(bill.reading_gap_info.net_gap) + ' kWh. Click to inspect or adjust.'">
            <span>⚠️</span>
            <span x-text="'Over ' + bill.reading_gap_info.net_gap + ' kWh'"></span>
            <span class="text-[9px] bg-amber-500/30 px-1 py-0.2 rounded" x-text="bill.reading_gap_info.estimated_catchup_cycles + 'mo'"></span>
        </button>
    </template>

    <template x-if="bill.reading_gap_info && bill.reading_gap_info.gap_status === 'under_posted'">
        <button type="button"
                @click.stop="openInspectionModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 select-none active:scale-95 cursor-pointer"
                :title="'Under-Posted gap of +' + bill.reading_gap_info.net_gap + ' kWh. Safe slice: +' + bill.reading_gap_info.gap_slice_applied + ' kWh/mo.'">
            <span>⚖️</span>
            <span x-text="'+' + bill.reading_gap_info.net_gap + ' Gap'"></span>
            <span class="text-[9px] bg-rose-500/30 px-1 py-0.2 rounded" x-text="'+' + bill.reading_gap_info.gap_slice_applied + '/mo'"></span>
        </button>
    </template>

    <template x-if="bill.reading_gap_info && bill.reading_gap_info.gap_status === 'in_sync'">
        <button type="button"
                @click.stop="openInspectionModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 select-none active:scale-95 cursor-pointer"
                title="Meter in sync with posted reading. Click to inspect.">
            <span>🟢</span>
            <span>Sync</span>
        </button>
    </template>

    <template x-if="!bill.reading_gap_info || bill.reading_gap_info.gap_status === 'uninspected'">
        <button type="button"
                @click.stop="openInspectionModal(bill)"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[10px] font-bold font-mono transition border border-dashed border-white/20 text-slate-300 hover:text-white hover:bg-white/10 active:scale-95 select-none cursor-pointer"
                title="Record physical wall meter reading from field inspection">
            <span>🔍</span>
            <span>+Inspect</span>
        </button>
    </template>

    <!-- Inspection Staleness Warning Pill -->
    <template x-if="bill.reading_gap_info && bill.reading_gap_info.is_stale">
        <span class="inline-flex items-center px-1 py-0.2 rounded text-[9px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 animate-pulse"
              :title="'Inspection is older than ' + bill.reading_gap_info.days_since_inspection + ' days. Re-inspection recommended.'">
            ⏱️ Stale
        </span>
    </template>
</div>
