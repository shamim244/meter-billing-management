<!-- Ticket Details Modal -->
<div x-show="detailsModalOpen" 
     x-cloak 
     @keydown.escape.window="detailsModalOpen = false"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6">
    <div @click.away="detailsModalOpen = false" 
         class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
        
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl font-bold">
                    🐞
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono font-black text-sm text-indigo-600 dark:text-indigo-400" x-text="selectedIssue?.issue_code"></span>
                        <button type="button" @click="copyText(selectedIssue?.issue_code)" class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700">📋</button>
                    </div>
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300" x-text="selectedIssue?.category_label"></h3>
                </div>
            </div>
            <button type="button" @click="detailsModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                ✕
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto space-y-5 text-xs">
            
            <!-- Status Banner -->
            <div class="p-4 rounded-2xl flex items-center justify-between"
                 :class="{
                     'bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200': selectedIssue?.status === 'pending',
                     'bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200': selectedIssue?.status === 'verified',
                     'bg-purple-50 dark:bg-purple-950/50 border border-purple-200 dark:border-purple-800 text-purple-800 dark:text-purple-200': selectedIssue?.status === 'in_progress',
                     'bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200': selectedIssue?.status === 'resolved',
                     'bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200': selectedIssue?.status === 'spam'
                 }">
                <div>
                    <div class="font-bold text-sm flex items-center gap-1.5">
                        <span x-show="selectedIssue?.status === 'pending'">⏳ Status: Pending Review</span>
                        <span x-show="selectedIssue?.status === 'verified'">🔍 Status: Verified by Team</span>
                        <span x-show="selectedIssue?.status === 'in_progress'">🛠️ Status: Fix In Progress</span>
                        <span x-show="selectedIssue?.status === 'resolved'">✅ Status: Resolved & Fixed</span>
                        <span x-show="selectedIssue?.status === 'spam'">⚠️ Status: Closed / Discarded</span>
                    </div>
                    <p class="text-[11px] opacity-80 mt-0.5">
                        <span x-show="selectedIssue?.status === 'pending'">We have received your issue report. It is queued for admin verification and diagnostic triage.</span>
                        <span x-show="selectedIssue?.status === 'verified'">The issue has been verified and passed to the AI engineering agent to inspect and resolve.</span>
                        <span x-show="selectedIssue?.status === 'in_progress'">The engineering team or AI agent is actively modifying and testing code fixes.</span>
                        <span x-show="selectedIssue?.status === 'resolved'">The bug has been fixed, tested with automated test suites, and verified in the codebase!</span>
                        <span x-show="selectedIssue?.status === 'spam'">This submission was marked as duplicate or non-reproducible.</span>
                    </p>
                </div>
            </div>

            <!-- Problem Description -->
            <div>
                <h4 class="font-bold text-slate-900 dark:text-white text-sm" x-text="selectedIssue?.title"></h4>
                <div class="mt-2 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-800 text-slate-700 dark:text-slate-300 leading-relaxed font-sans whitespace-pre-wrap" x-text="selectedIssue?.description"></div>
            </div>

            <!-- AI Resolution Notes -->
            <div x-show="selectedIssue?.ai_resolution_notes" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 space-y-1.5">
                <div class="flex items-center gap-2 font-bold text-emerald-800 dark:text-emerald-200 text-xs">
                    <span>🤖</span>
                    <span>AI Agent Fix & Resolution Summary</span>
                </div>
                <div class="text-xs text-emerald-900 dark:text-emerald-300 font-mono leading-relaxed bg-white/70 dark:bg-slate-900/60 p-3 rounded-xl border border-emerald-200/60 dark:border-emerald-800/60" x-text="selectedIssue?.ai_resolution_notes"></div>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 pt-1 flex items-center justify-between" x-show="selectedIssue?.resolved_at">
                    <span>Resolved: <strong x-text="selectedIssue?.resolved_at"></strong></span>
                    <span x-text="selectedIssue?.resolved_at_human"></span>
                </div>
            </div>

            <!-- Contextual Details -->
            <div class="grid grid-cols-2 gap-3 text-[11px] p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/40 border border-slate-100 dark:border-slate-800 font-mono text-slate-600 dark:text-slate-400">
                <div>Submitted: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedIssue?.created_at"></span></div>
                <div>Severity: <span class="font-bold uppercase text-slate-800 dark:text-slate-200" x-text="selectedIssue?.severity"></span></div>
                <div x-show="selectedIssue?.mru_name">MRU: <span class="font-bold text-slate-800 dark:text-slate-200" x-text="selectedIssue?.mru_name"></span></div>
                <div x-show="selectedIssue?.ca_number">CA Number: <span class="font-bold text-indigo-600 dark:text-cyan-400" x-text="selectedIssue?.ca_number"></span></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end">
            <button type="button" @click="detailsModalOpen = false" class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-800 hover:bg-slate-800 dark:hover:bg-slate-700 text-white text-xs font-bold transition">
                Close Details
            </button>
        </div>
    </div>
</div>
