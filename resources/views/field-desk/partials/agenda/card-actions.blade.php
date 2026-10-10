<!-- Card Footer: Quick Actions Bar (1-2 tap velocity) -->
<div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
    
    <!-- Communication & Snooze Shortcuts -->
    <div class="flex flex-wrap items-center gap-1.5">
        
        <!-- 1-Click WhatsApp Trigger -->
        <template x-if="item.whatsapp_link">
            <a :href="item.whatsapp_link" target="_blank"
               @click="logCommunication(item.id, 'whatsapp_sent', 'WhatsApp reminder initiated')"
               class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95"
               title="Send pre-filled Hindi WhatsApp template">
                <span>💬</span> WhatsApp
            </a>
        </template>

        <!-- 1-Click Phone Call Trigger -->
        <template x-if="item.mobile">
            <a :href="'tel:' + item.mobile"
               @click="logCommunication(item.id, 'call_made', 'Phone call placed')"
               class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95"
               title="Call consumer directly">
                <span>📞</span> Call
            </a>
        </template>

        <!-- 1-Click GPS Navigation -->
        <template x-if="item.map_link">
            <a :href="item.map_link" target="_blank"
               class="inline-flex items-center gap-1 px-3 py-1.5 bg-rose-500 hover:bg-rose-600 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95"
               title="Open GPS navigation in Google Maps">
                <span>📍</span> Map
            </a>
        </template>

        <!-- +2 Days Quick Snooze -->
        <template x-if="item.status !== 'completed'">
            <button @click="quickReschedule(item.id, 2)"
                    class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95"
                    title="Snooze target date by 2 days">
                +2d
            </button>
        </template>

        <!-- +5 Days Quick Snooze -->
        <template x-if="item.status !== 'completed'">
            <button @click="quickReschedule(item.id, 5)"
                    class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95"
                    title="Snooze target date by 5 days">
                +5d
            </button>
        </template>

        <!-- +7 Days Quick Snooze -->
        <template x-if="item.status !== 'completed'">
            <button @click="quickReschedule(item.id, 7)"
                    class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95"
                    title="Snooze target date by 7 days">
                +7d
            </button>
        </template>

        <!-- Mark Done Button -->
        <template x-if="item.status !== 'completed'">
            <button @click="openCompleteModal(item)"
                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold border border-emerald-200 dark:border-emerald-800 transition active:scale-95">
                <span>✅</span> Mark Done
            </button>
        </template>
    </div>

    <!-- Utility & History Controls -->
    <div class="flex items-center gap-2">
        <!-- View Timeline Drawer -->
        <button @click="openTimelineDrawer(item.id)"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                title="View complete interaction touch timeline">
            <span>🕒</span> History
        </button>

        <!-- Edit Action -->
        <button @click="openEditModal(item)"
                class="p-1.5 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                title="Edit details">
            ✏️
        </button>

        <!-- Delete / Cancel Action -->
        <button @click="deleteAction(item.id)"
                class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition"
                title="Delete action">
            🗑️
        </button>
    </div>

</div>
