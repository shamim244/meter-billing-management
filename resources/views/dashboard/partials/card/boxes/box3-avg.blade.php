<!-- Box 3: 📊 Average Usage (Avg kWh) -->
<div @click="openMeterHistoryModal(bill.ca_number, bill.consumer_name)" 
     class="p-2.5 sm:p-3 rounded-2xl border shadow-xs flex flex-col justify-between cursor-pointer transition group relative" 
     :class="bill.is_avg_excluded ? 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-300 dark:border-amber-700/80' : (colorSettings?.enabled ? getAvgUnitStyle(bill.smart_avg_units, 'border') : 'bg-white dark:bg-slate-800 border-slate-200/80 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500')" 
     :title="bill.is_avg_excluded ? ('Excluded: ' + (bill.avg_exclude_reason || 'Safe Filter')) : 'Click to view 2D Monthly Reading History'">
    
    <div class="flex items-center justify-between gap-1">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📊 Avg Usage</span>
        <div class="flex items-center gap-1">
            <template x-if="bill.is_over_posted">
                <span class="px-1.5 py-0.2 text-[9px] font-black rounded bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-700">⚠️ Over</span>
            </template>
            <template x-if="bill.is_avg_excluded && !bill.is_over_posted">
                <span class="px-1.5 py-0.2 text-[9px] font-black rounded bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300">🚫 Excl</span>
            </template>
            <template x-if="bill.is_custom_override">
                <span class="px-1.5 py-0.2 text-[9px] font-black rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-cyan-300 border border-indigo-200 dark:border-indigo-800">🎯 Lock</span>
            </template>
            <button type="button" @click.stop="openIndividualTuningModal(bill)" class="text-[10px] text-slate-400 hover:text-indigo-600 dark:hover:text-cyan-400 px-1 py-0.5 rounded transition cursor-pointer" title="Configure individual override / exclusion">
                ⚙️
            </button>
        </div>
    </div>

    <div class="text-base sm:text-lg my-0.5 sm:my-1 font-mono text-center transition" 
         :class="bill.is_avg_excluded ? 'text-amber-600 dark:text-amber-400 font-bold' : getAvgUnitStyle(bill.smart_avg_units)" 
         x-text="(bill.smart_avg_units ?? 50) + ' kWh'"></div>

    <div class="flex items-center justify-between text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate">
        <span class="truncate" x-text="bill.is_avg_excluded ? (bill.avg_exclude_reason || 'Excluded') : (bill.smart_avg_label || 'History Avg')"></span>
        <template x-if="bill.is_clamped_min">
            <span class="text-[8px] font-bold text-amber-500 shrink-0 font-mono">Floor</span>
        </template>
        <template x-if="bill.is_clamped_max">
            <span class="text-[8px] font-bold text-rose-500 shrink-0 font-mono">Cap</span>
        </template>
    </div>
</div>
