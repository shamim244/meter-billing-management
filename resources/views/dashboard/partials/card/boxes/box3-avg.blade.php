<!-- Box 3: 📊 Average Usage (Avg kWh) -->
<div @click="openMeterHistoryModal(bill.ca_number, bill.consumer_name)" 
     class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border shadow-sm flex flex-col justify-between cursor-pointer transition group" 
     :class="colorSettings?.enabled ? getAvgUnitStyle(bill.smart_avg_units, 'border') : 'border-slate-200/80 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500'" 
     title="Click to view 2D Monthly Reading History & calculation audit">
    <div class="flex items-center justify-between">
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📊 Avg Usage</span>
        <span class="text-[9px] text-indigo-500 font-bold opacity-70 group-hover:opacity-100 transition">History ↗</span>
    </div>
    <div class="text-base sm:text-lg my-0.5 sm:my-1 font-mono text-center transition" 
         :class="getAvgUnitStyle(bill.smart_avg_units)" 
         x-text="(bill.smart_avg_units ?? 50) + ' kWh'"></div>
    <div class="text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate" x-text="bill.smart_avg_label || 'History Avg'"></div>
</div>
