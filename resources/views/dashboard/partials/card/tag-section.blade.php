<!-- Tag Selection Section (Clean, Focused, Responsive Mobile & Desktop) -->
<div class="px-3 sm:px-5 py-2.5 bg-slate-100/70 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-700 space-y-1.5">
    <div class="flex items-center justify-between">
        <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
            <span>🏷️</span> Tag:
        </label>
        <!-- Active Tag Indicator Badge -->
        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all"
              :class="getTagBadgeClass(bill.tag || defaultTag)"
              x-text="getTagDisplayLabel(bill.tag || defaultTag)"
              :title="getTagFullLabel(bill.tag || defaultTag)">
        </span>
    </div>

    <!-- Responsive Tag Pills Selection Bar -->
    <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
        <template x-for="tagItem in availableTags" :key="tagItem.code">
            <button type="button" 
                    @click="setBillTag(bill, tagItem.code)"
                    :title="tagItem.label"
                    class="px-2.5 py-1 rounded-xl text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer border shadow-xs"
                    :class="(bill.tag === tagItem.code || (!bill.tag && tagItem.code === defaultTag)) 
                        ? getActivePillClass(tagItem.color) 
                        : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                <span x-show="(bill.tag === tagItem.code || (!bill.tag && tagItem.code === defaultTag))" class="text-[9px]">✓</span>
                <span x-text="tagItem.short_label || tagItem.label"></span>
            </button>
        </template>
    </div>
</div>
