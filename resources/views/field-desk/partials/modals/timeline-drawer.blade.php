        <!-- 3. TIMELINE DRAWER -->
        <div x-show="modals.timeline"
             x-cloak
             class="fixed inset-0 z-50 overflow-hidden bg-slate-900/60 backdrop-blur-sm flex justify-end">
            <div @click.away="modals.timeline = false"
                 class="w-full max-w-md bg-white dark:bg-slate-900 h-full p-6 shadow-2xl flex flex-col border-l border-slate-200 dark:border-slate-800">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                            <span>🕒</span> Touch History Timeline
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Audit log of touches and status changes
                        </p>
                    </div>
                    <button @click="modals.timeline = false" class="text-slate-400 hover:text-slate-600 text-sm">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto py-4 space-y-4">
                    <template x-if="timelineActivities.length === 0">
                        <div class="text-center py-10 text-xs text-slate-400">
                            No history records logged yet.
                        </div>
                    </template>
                    <template x-for="act in timelineActivities" :key="act.id">
                        <div class="flex items-start gap-3 text-xs">
                            <div class="mt-0.5 w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs shrink-0">
                                <template x-if="act.action_type === 'created'"><span>➕</span></template>
                                <template x-if="act.action_type === 'rescheduled'"><span>🔄</span></template>
                                <template x-if="act.action_type === 'completed'"><span>✅</span></template>
                                <template x-if="act.action_type === 'whatsapp_sent'"><span>💬</span></template>
                                <template x-if="act.action_type === 'call_made'"><span>📞</span></template>
                                <template x-if="!['created','rescheduled','completed','whatsapp_sent','call_made'].includes(act.action_type)"><span>📝</span></template>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 capitalize" x-text="act.action_type.replace('_', ' ')"></span>
                                    <span class="text-[10px] text-slate-400" x-text="act.created_at"></span>
                                </div>
                                <div class="text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed" x-text="act.note"></div>
                                <template x-if="act.amount_recorded > 0">
                                    <div class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                        Amount: ₹<span x-text="Number(act.amount_recorded).toLocaleString('en-IN')"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
                    <button @click="modals.timeline = false" class="w-full py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold">
                        Close Drawer
                    </button>
                </div>

            </div>
        </div>