{{-- Tab 3: My Reports List --}}
<div x-show="activeTab === 'my_reports'" class="p-6 overflow-y-auto space-y-3">
    <div class="flex items-center justify-between">
        <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Your Recent Bug Reports</span>
        <a href="{{ route('user-panel.issues') }}" class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
            Open Full Issue Center →
        </a>
    </div>

    <div x-show="isMyReportsLoading" class="py-8 text-center text-xs text-slate-400">
        <span class="inline-block w-4 h-4 border-2 border-slate-400 border-t-indigo-600 rounded-full animate-spin"></span>
        <span class="ml-2">Loading your tickets...</span>
    </div>

    <div x-show="!isMyReportsLoading && myReportsList.length === 0" class="py-8 text-center space-y-2">
        <div class="text-2xl">🎉</div>
        <div class="text-xs font-bold text-slate-700 dark:text-slate-300">No Bug Reports Recorded</div>
        <p class="text-[11px] text-slate-400">You haven't submitted any bug reports yet.</p>
    </div>

    <div x-show="!isMyReportsLoading && myReportsList.length > 0" class="space-y-2">
        <template x-for="item in myReportsList" :key="item.id">
            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700 flex items-center justify-between gap-3 hover:border-indigo-400 transition">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-black text-xs text-indigo-600 dark:text-indigo-400" x-text="item.issue_code"></span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                              :class="{
                                  'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300': item.status === 'pending',
                                  'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300': item.status === 'verified',
                                  'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300': item.status === 'in_progress',
                                  'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300': item.status === 'resolved',
                                  'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300': item.status === 'spam'
                              }"
                              x-text="item.status_label"></span>
                    </div>
                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate mt-0.5" x-text="item.title"></div>
                    <div class="text-[10px] text-slate-400 truncate" x-text="item.created_at_human"></div>
                </div>

                <button type="button" 
                        @click="trackCode = item.issue_code; activeTab = 'track'; trackTicket()" 
                        class="px-2.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 text-xs font-bold shrink-0 transition">
                    Track 🔍
                </button>
            </div>
        </template>
    </div>
</div>
