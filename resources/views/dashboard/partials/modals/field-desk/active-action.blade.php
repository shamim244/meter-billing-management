<!-- CASE A: Active Action Exists -->
<template x-if="fieldDeskAction">
    <div class="space-y-3.5">
        
        <!-- Status & Category Banner -->
        <div class="p-3 rounded-2xl border"
             :style="'background-color: ' + (fieldDeskAction.category_color || '#10b981') + '10; border-color: ' + (fieldDeskAction.category_color || '#10b981') + '30;'">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-1.5 font-bold text-xs" :style="'color: ' + (fieldDeskAction.category_color || '#10b981')">
                    <span x-text="fieldDeskAction.category_icon || '📋'"></span>
                    <span x-text="fieldDeskAction.category_name"></span>
                </span>
                <div>
                    <template x-if="fieldDeskAction.is_due_today">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-700 dark:text-rose-300 animate-pulse">
                            🚨 Due Today (<span x-text="fieldDeskAction.target_date_formatted"></span>)
                        </span>
                    </template>
                    <template x-if="fieldDeskAction.is_overdue">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-700 dark:text-amber-300">
                            ⚠️ Overdue (<span x-text="fieldDeskAction.target_date_formatted"></span>)
                        </span>
                    </template>
                    <template x-if="fieldDeskAction.is_upcoming">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-500/20 text-blue-700 dark:text-blue-300">
                            📅 Target: <span x-text="fieldDeskAction.target_date_formatted"></span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- Financial Commitment if set -->
            <template x-if="fieldDeskAction.target_amount > 0">
                <div class="mt-2.5 pt-2 border-t border-slate-200/40 dark:border-slate-700/40 flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400">Promised Amount:</span>
                    <span class="font-bold font-mono text-emerald-600 dark:text-emerald-400 text-sm">
                        ₹<span x-text="Number(fieldDeskAction.target_amount).toLocaleString('en-IN')"></span>
                        <template x-if="fieldDeskBill?.total_amount">
                            <span class="text-[10px] text-slate-400 font-normal"> / Bill: ₹<span x-text="Math.round(fieldDeskBill.total_amount)"></span></span>
                        </template>
                    </span>
                </div>
            </template>
        </div>

        <!-- Private Note Box -->
        <div class="bg-slate-50 dark:bg-slate-800/60 p-3 rounded-2xl border border-slate-200 dark:border-slate-700 text-xs">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">📝 Dossier / Field Note:</span>
            <p class="text-slate-800 dark:text-slate-200 leading-relaxed font-sans" x-text="fieldDeskAction.private_note || 'No notes entered.'"></p>
        </div>

        <!-- Fast 2-Tap Action Buttons -->
        <div class="flex flex-wrap items-center gap-2 pt-1">
            <template x-if="fieldDeskAction.whatsapp_link">
                <a :href="fieldDeskAction.whatsapp_link" target="_blank"
                   @click="logFieldDeskActivity(fieldDeskAction.id, 'whatsapp_sent', 'WhatsApp reminder initiated from Billing Dashboard')"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95">
                    <span>💬</span> WhatsApp
                </a>
            </template>
            <template x-if="fieldDeskAction.clean_mobile">
                <a :href="'tel:' + fieldDeskAction.clean_mobile"
                   @click="logFieldDeskActivity(fieldDeskAction.id, 'call_made', 'Phone call placed from Billing Dashboard')"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-sm transition active:scale-95">
                    <span>📞</span> Call
                </a>
            </template>
            <button type="button" @click="quickSnoozeFromModal(fieldDeskAction.id, 2)"
                    class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95">
                🔄 +2 Days
            </button>
            <button type="button" @click="quickSnoozeFromModal(fieldDeskAction.id, 5)"
                    class="px-3 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold transition active:scale-95">
                🔄 +5 Days
            </button>
            <button type="button" @click="completeActionFromModal(fieldDeskAction.id)"
                    class="inline-flex items-center gap-1 px-3 py-2 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 rounded-xl text-xs font-bold border border-emerald-200 dark:border-emerald-800 transition active:scale-95">
                <span>✅</span> Done
            </button>
        </div>

    </div>
</template>
