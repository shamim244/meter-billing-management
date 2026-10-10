<!-- Card Header: Category & Priority & Timing -->
<div class="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-slate-100 dark:border-slate-800">
    <div class="flex items-center gap-2">
        <!-- Dynamic Category Pill -->
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold"
              :style="'background-color: ' + item.category_color + '15; color: ' + item.category_color + '; border: 1px solid ' + item.category_color + '30;'">
            <span x-text="item.category_icon"></span>
            <span x-text="item.category_name"></span>
        </span>

        <!-- Priority Badge -->
        <span x-show="item.priority === 'urgent'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-200 dark:border-red-800/60">
            🚨 Urgent
        </span>
        <span x-show="item.priority === 'high'" class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400 border border-amber-200 dark:border-amber-800/60">
            ⚠️ High
        </span>

        <!-- Reschedule Count Badge -->
        <span x-show="item.reschedule_count > 0" class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60">
            🔄 Snoozed <span x-text="item.reschedule_count"></span>x
        </span>
    </div>

    <!-- Due Timing Badge -->
    <div>
        <template x-if="item.status === 'completed'">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                ✅ Resolved
            </span>
        </template>
        <template x-if="item.status !== 'completed' && item.is_due_today">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 animate-pulse">
                🚨 Due Today (<span x-text="item.target_date_formatted"></span>)
            </span>
        </template>
        <template x-if="item.status !== 'completed' && item.is_overdue">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60">
                ⚠️ Overdue by <span x-text="Math.abs(item.days_diff)"></span>d (<span x-text="item.target_date_formatted"></span>)
            </span>
        </template>
        <template x-if="item.status !== 'completed' && item.is_upcoming">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/60">
                📅 Due in <span x-text="item.days_diff"></span>d (<span x-text="item.target_date_formatted"></span>)
            </span>
        </template>
    </div>
</div>
