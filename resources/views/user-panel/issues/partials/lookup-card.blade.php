<!-- Quick Reference Code Lookup Card -->
<div class="rounded-3xl border border-indigo-200/80 dark:border-indigo-900/60 bg-gradient-to-br from-indigo-50/50 via-white to-purple-50/30 dark:from-indigo-950/30 dark:via-slate-900 dark:to-purple-950/20 p-5 sm:p-6 shadow-sm">
    <div class="max-w-2xl">
        <div class="flex items-center gap-2.5 mb-2">
            <span class="text-lg">🔍</span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Track Ticket by Reference Number</h3>
        </div>
        <p class="text-xs text-slate-600 dark:text-slate-400 mb-4">
            Have a ticket reference code (e.g., <code class="font-mono px-1.5 py-0.5 rounded bg-indigo-100/70 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 font-bold">BUG-20260918-3KS3</code>)? Enter it below to immediately check its status, progress, and AI fix notes.
        </p>

        <form @submit.prevent="lookupTicket()" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <div class="relative flex-1">
                <input type="text" 
                       x-model="lookupCode"
                       placeholder="Enter Reference Code (e.g., BUG-20260918-XXXX)" 
                       required
                       class="w-full text-xs font-mono font-semibold uppercase tracking-wider px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:normal-case placeholder:font-sans placeholder:font-normal focus:ring-2 focus:ring-indigo-500 shadow-xs">
                <button type="button" 
                        x-show="lookupCode" 
                        @click="lookupCode = ''; lookupResult = null; lookupError = null" 
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 text-xs">
                    ✕
                </button>
            </div>
            <button type="submit" 
                    :disabled="isSearching"
                    class="px-6 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md shadow-indigo-600/20 transition active:scale-95 disabled:opacity-60 flex items-center justify-center gap-2 shrink-0">
                <span x-show="isSearching" class="w-3.5 h-3.5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                <span x-text="isSearching ? 'Checking...' : 'Check Status 🚀'"></span>
            </button>
        </form>

        <!-- Lookup Error Message -->
        <div x-show="lookupError" x-cloak class="mt-3 p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-2">
            <span>⚠️</span>
            <span x-text="lookupError"></span>
        </div>

        <!-- Lookup Result Display Box -->
        <div x-show="lookupResult" x-cloak class="mt-4 p-5 rounded-2xl bg-white dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 shadow-lg space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 dark:border-slate-700/80 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="font-mono font-black text-sm text-indigo-600 dark:text-indigo-400" x-text="lookupResult?.issue_code"></span>
                    <button type="button" @click="copyText(lookupResult?.issue_code)" class="text-[11px] px-2 py-0.5 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 transition">
                        📋 Copy
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <!-- Status Badge -->
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1.5"
                          :class="{
                              'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800': lookupResult?.status === 'pending',
                              'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-300 dark:border-blue-800': lookupResult?.status === 'verified',
                              'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-300 dark:border-purple-800': lookupResult?.status === 'in_progress',
                              'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800': lookupResult?.status === 'resolved',
                              'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800': lookupResult?.status === 'spam'
                          }">
                        <span x-show="lookupResult?.status === 'pending'">⏳</span>
                        <span x-show="lookupResult?.status === 'verified'">🔍</span>
                        <span x-show="lookupResult?.status === 'in_progress'">🛠️</span>
                        <span x-show="lookupResult?.status === 'resolved'">✅</span>
                        <span x-show="lookupResult?.status === 'spam'">⚠️</span>
                        <span x-text="lookupResult?.status_label"></span>
                    </span>
                </div>
            </div>

            <div>
                <h4 class="font-bold text-sm text-slate-900 dark:text-white" x-text="lookupResult?.title"></h4>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed" x-text="lookupResult?.description"></p>
            </div>

            <!-- AI Resolution Notes (If Resolved) -->
            <div x-show="lookupResult?.ai_resolution_notes" class="p-3.5 rounded-xl bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 space-y-1">
                <div class="flex items-center gap-1.5 font-bold text-xs">
                    <span>🤖</span>
                    <span>AI Agent Resolution & Fix Notes:</span>
                </div>
                <p class="text-xs leading-relaxed text-emerald-800 dark:text-emerald-300 font-mono" x-text="lookupResult?.ai_resolution_notes"></p>
                <div class="text-[10px] text-emerald-600 dark:text-emerald-400 pt-1 flex items-center gap-1" x-show="lookupResult?.resolved_at">
                    <span>Resolved on:</span>
                    <span x-text="lookupResult?.resolved_at"></span>
                    <span class="text-slate-400">·</span>
                    <span x-text="'(' + lookupResult?.resolved_at_human + ')'"></span>
                </div>
            </div>

            <!-- Metadata Row -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-slate-100 dark:border-slate-700/60 text-[11px] text-slate-500 dark:text-slate-400">
                <div>Category: <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="lookupResult?.category_label"></span></div>
                <div>Severity: <span class="font-semibold uppercase text-slate-700 dark:text-slate-200" x-text="lookupResult?.severity"></span></div>
                <div>Reported: <span class="font-semibold text-slate-700 dark:text-slate-200" x-text="lookupResult?.created_at_human"></span></div>
            </div>
        </div>
    </div>
</div>
