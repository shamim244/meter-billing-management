{{-- Tab 2: Track Ticket by Reference Code --}}
<div x-show="activeTab === 'track'" class="p-6 overflow-y-auto space-y-4">
    <div>
        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
            Enter Ticket Reference Code
        </label>
        <div class="flex items-center gap-2">
            <input type="text"
                   x-model="trackCode"
                   @keydown.enter.prevent="trackTicket()"
                   placeholder="e.g. BUG-20260918-3KS3"
                   class="flex-1 text-xs font-mono font-bold uppercase tracking-wider px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:normal-case placeholder:font-sans placeholder:font-normal focus:ring-2 focus:ring-indigo-500">
            <button type="button"
                    @click="trackTicket()"
                    :disabled="isTrackLoading"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-xs transition active:scale-95 disabled:opacity-60 shrink-0">
                <span x-show="isTrackLoading">Searching...</span>
                <span x-show="!isTrackLoading">Check Status 🔍</span>
            </button>
        </div>
    </div>

    <!-- Recent Tickets Chips -->
    <div x-show="recentTickets.length > 0" class="space-y-1.5">
        <span class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">Recent Tickets:</span>
        <div class="flex flex-wrap items-center gap-1.5">
            <template x-for="code in recentTickets" :key="code">
                <button type="button" 
                        @click="trackCode = code; trackTicket()" 
                        class="px-2 py-1 rounded-lg text-[10px] font-mono font-bold bg-slate-100 dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 border border-slate-200 dark:border-slate-700 transition"
                        x-text="code"></button>
            </template>
        </div>
    </div>

    <!-- Track Error Alert -->
    <div x-show="trackError" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
        <span>⚠️</span>
        <span x-text="trackError"></span>
    </div>

    <!-- Track Result Display Box -->
    <div x-show="trackResult" x-cloak class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 space-y-3">
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-700/80 pb-2.5">
            <div class="flex items-center gap-2">
                <span class="font-mono font-black text-xs text-indigo-600 dark:text-indigo-400" x-text="trackResult?.issue_code"></span>
                <button type="button" @click="copyText(trackResult?.issue_code)" class="text-[10px] text-slate-400 hover:text-slate-600 p-0.5">📋</button>
            </div>

            <!-- Status Badge -->
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold flex items-center gap-1"
                  :class="{
                      'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300': trackResult?.status === 'pending',
                      'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300': trackResult?.status === 'verified',
                      'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300': trackResult?.status === 'in_progress',
                      'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300': trackResult?.status === 'resolved',
                      'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300': trackResult?.status === 'spam'
                  }">
                <span x-show="trackResult?.status === 'pending'">⏳</span>
                <span x-show="trackResult?.status === 'verified'">🔍</span>
                <span x-show="trackResult?.status === 'in_progress'">🛠️</span>
                <span x-show="trackResult?.status === 'resolved'">✅</span>
                <span x-show="trackResult?.status === 'spam'">⚠️</span>
                <span x-text="trackResult?.status_label"></span>
            </span>
        </div>

        <div>
            <h4 class="font-bold text-xs text-slate-900 dark:text-white" x-text="trackResult?.title"></h4>
            <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-1 leading-relaxed" x-text="trackResult?.description"></p>
        </div>

        <!-- AI Agent Resolution Highlight (If Resolved) -->
        <div x-show="trackResult?.ai_resolution_notes" class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 space-y-1">
            <div class="flex items-center gap-1.5 font-bold text-[11px]">
                <span>🤖</span>
                <span>AI Agent Fix Applied:</span>
            </div>
            <p class="text-[11px] font-mono leading-relaxed text-emerald-800 dark:text-emerald-300" x-text="trackResult?.ai_resolution_notes"></p>
            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 pt-0.5" x-show="trackResult?.resolved_at">
                <span>Resolved on:</span> <strong x-text="trackResult?.resolved_at"></strong>
            </div>
        </div>

        <!-- Timeline row -->
        <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 flex items-center justify-between text-[10px] text-slate-500 dark:text-slate-400 font-mono">
            <div>Reported: <span x-text="trackResult?.created_at_human"></span></div>
            <div x-show="trackResult?.category_label" x-text="trackResult?.category_label"></div>
        </div>
    </div>
</div>
