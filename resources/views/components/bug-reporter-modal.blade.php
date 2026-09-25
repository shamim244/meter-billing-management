<div x-data="bugReporterComponent()" x-init="init()" class="relative z-50">
    <!-- Floating Trigger Button (Bottom-Left) -->
    <button type="button"
            @click="openModal('report')"
            class="fixed bottom-4 left-4 z-40 inline-flex items-center gap-2 px-3.5 py-2.5 rounded-full bg-slate-900/90 dark:bg-slate-800/90 hover:bg-slate-950 dark:hover:bg-slate-700 text-slate-100 text-xs font-bold shadow-xl border border-slate-700/80 backdrop-blur-md transition-all duration-200 hover:scale-105 active:scale-95 cursor-pointer group"
            title="Report or Track a bug or issue (Ctrl+Shift+B)">
        <span class="text-base group-hover:animate-bounce">🐞</span>
        <span class="hidden sm:inline">Report / Track Bug</span>
    </button>

    <!-- Modal Backdrop & Dialog -->
    <div x-show="isOpen"
         x-cloak
         @keydown.escape.window="closeModal()"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <!-- Modal Panel -->
        <div @click.away="closeModal()"
             class="w-full max-w-xl bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xl font-bold shadow-2xs">
                        🐞
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Bug Resolution & Tracking Hub</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Report problems or track live resolution by the AI Agent.</p>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Tab Navigation Header -->
            <div class="px-6 pt-3 border-b border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 flex items-center gap-2">
                <button type="button" 
                        @click="activeTab = 'report'" 
                        class="pb-2.5 px-3 text-xs font-bold transition flex items-center gap-1.5 border-b-2"
                        :class="activeTab === 'report' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>📝 Report Issue</span>
                </button>

                <button type="button" 
                        @click="activeTab = 'track'" 
                        class="pb-2.5 px-3 text-xs font-bold transition flex items-center gap-1.5 border-b-2"
                        :class="activeTab === 'track' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>🔍 Track by Ticket #</span>
                </button>

                <button type="button" 
                        @click="switchTabToMyReports()" 
                        class="pb-2.5 px-3 text-xs font-bold transition flex items-center gap-1.5 border-b-2"
                        :class="activeTab === 'my_reports' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'">
                    <span>📋 My Reports</span>
                    <span x-show="myReportsCount > 0" class="px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="myReportsCount"></span>
                </button>
            </div>

            <!-- TAB 1: Report Issue Form -->
            <div x-show="activeTab === 'report'" class="p-6 overflow-y-auto space-y-4">
                <!-- Success Message Banner -->
                <div x-show="submittedCode" x-cloak class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 space-y-2">
                    <div class="flex items-center gap-2 font-bold text-sm">
                        <span>✅</span> Issue Report Received!
                    </div>
                    <p class="text-xs">
                        Reference Number: <strong class="font-mono text-emerald-700 dark:text-emerald-300 text-sm font-black" x-text="submittedCode"></strong>.
                        The technical diagnostic context has been captured.
                    </p>
                    <div class="pt-2 flex flex-wrap items-center gap-3">
                        <button type="button" 
                                @click="trackSubmittedTicket()" 
                                class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                            <span>🔍</span>
                            <span>Track This Ticket Now</span>
                        </button>
                        <button type="button" @click="resetForm()" class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 underline">
                            Report another issue
                        </button>
                    </div>
                </div>

                <form x-show="!submittedCode" @submit.prevent="submitReport()" class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Issue Title <span class="text-rose-500">*</span></label>
                        <input type="text"
                               x-model="form.title"
                               required
                               placeholder="e.g., Calculation units did not update, or PDF download failed"
                               class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <!-- Category & Severity Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Category</label>
                            <select x-model="form.category" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                <option value="calculation">⚡ Calculation / Units</option>
                                <option value="bill_download">📑 Bill Download / PDF</option>
                                <option value="mru_sync">🗂️ MRU / Cycles</option>
                                <option value="ui_display">🖥️ UI / Display Error</option>
                                <option value="wallet_payment">👛 Wallet / Billing</option>
                                <option value="other">❓ Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Severity</label>
                            <select x-model="form.severity" class="w-full text-xs font-medium px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                <option value="low">🟢 Low (Cosmetic / Small)</option>
                                <option value="medium" selected>🟡 Medium (Normal)</option>
                                <option value="high">🟠 High (Blocks Workflow)</option>
                                <option value="critical">🔴 Critical (Data Error / Crash)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Description / What Happened? <span class="text-rose-500">*</span></label>
                        <textarea x-model="form.description"
                                  rows="3"
                                  required
                                  placeholder="Describe what you clicked, what you expected, and what actually happened..."
                                  class="w-full text-xs font-medium px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <!-- Auto-Captured Context Accordion -->
                    <div class="rounded-2xl border border-slate-200/80 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 p-3 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between text-[11px] font-bold text-slate-500 dark:text-slate-400">
                            <span class="flex items-center gap-1.5">
                                <span>🤖</span> Auto-Captured Diagnostic Context
                            </span>
                            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono">Ready</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600 dark:text-slate-400 font-mono">
                            <div>URL: <span class="font-semibold text-slate-800 dark:text-slate-200 truncate inline-block max-w-[160px]" x-text="window.location.pathname"></span></div>
                            <div x-show="form.ca_number">CA: <span class="font-bold text-blue-600 dark:text-cyan-400" x-text="form.ca_number"></span></div>
                            <div x-show="form.mru_id">MRU ID: <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="form.mru_id"></span></div>
                            <div x-show="form.billing_month">Period: <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="form.billing_month + '/' + form.billing_year"></span></div>
                        </div>
                    </div>

                    <!-- Error Alert -->
                    <div x-show="errorMessage" x-cloak class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 text-xs font-semibold" x-text="errorMessage"></div>

                    <!-- Modal Actions -->
                    <div class="pt-2 flex items-center justify-end gap-3">
                        <button type="button" @click="closeModal()" class="px-4 py-2 text-xs font-semibold rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                :disabled="isSubmitting"
                                class="inline-flex items-center gap-2 px-5 py-2 text-xs font-bold rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-md shadow-indigo-600/20 transition active:scale-95 disabled:opacity-60 cursor-pointer">
                            <span x-show="isSubmitting" class="w-3 h-3 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                            <span x-text="isSubmitting ? 'Sending...' : 'Submit Report 🚀'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- TAB 2: Track Ticket by Reference Code -->
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

            <!-- TAB 3: My Reports List -->
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
        </div>
    </div>
</div>

<script>
function bugReporterComponent() {
    return {
        isOpen: false,
        activeTab: 'report',
        isSubmitting: false,
        submittedCode: null,
        errorMessage: null,
        recentConsoleErrors: [],

        // Ticket tracking state
        trackCode: '',
        isTrackLoading: false,
        trackResult: null,
        trackError: null,
        recentTickets: [],

        // My reports state
        isMyReportsLoading: false,
        myReportsList: [],
        myReportsCount: 0,

        form: {
            title: '',
            description: '',
            category: 'other',
            severity: 'medium',
            page_url: '',
            route_name: '',
            ca_number: null,
            mru_id: null,
            billing_month: null,
            billing_year: null,
        },

        init() {
            // Load recent ticket codes from localStorage
            try {
                const stored = localStorage.getItem('nbpdcl_recent_tickets');
                if (stored) {
                    this.recentTickets = JSON.parse(stored) || [];
                }
            } catch(e) {}

            // Global Keyboard Shortcut: Ctrl + Shift + B opens Bug Reporter
            window.addEventListener('keydown', (e) => {
                if (e.ctrlKey && e.shiftKey && (e.key === 'B' || e.key === 'b')) {
                    e.preventDefault();
                    this.openModal('report');
                }
            });

            // Capture unhandled console error logs in memory
            const originalConsoleError = console.error;
            console.error = (...args) => {
                try {
                    this.recentConsoleErrors.push({
                        time: new Date().toISOString(),
                        message: args.map(a => typeof a === 'object' ? JSON.stringify(a) : String(a)).join(' ')
                    });
                    if (this.recentConsoleErrors.length > 5) this.recentConsoleErrors.shift();
                } catch(e) {}
                originalConsoleError.apply(console, args);
            };

            // Listen for custom trigger from any card or table row
            window.addEventListener('open-bug-reporter', (event) => {
                this.openModal('report', event.detail || {});
            });
        },

        openModal(tab = 'report', context = {}) {
            this.activeTab = tab;
            this.captureContext(context);
            this.submittedCode = null;
            this.errorMessage = null;
            this.isOpen = true;

            if (tab === 'my_reports') {
                this.loadMyReports();
            }
        },

        closeModal() {
            this.isOpen = false;
        },

        resetForm() {
            this.submittedCode = null;
            this.form.title = '';
            this.form.description = '';
            this.form.category = 'other';
            this.form.severity = 'medium';
            this.captureContext();
        },

        captureContext(override = {}) {
            this.form.page_url = window.location.href;
            
            const urlParams = new URLSearchParams(window.location.search);
            this.form.mru_id = override.mru_id || urlParams.get('mru_id') || localStorage.getItem('dashboard_mru') || null;
            this.form.billing_month = override.billing_month || urlParams.get('month') || null;
            this.form.billing_year = override.billing_year || urlParams.get('year') || null;
            this.form.ca_number = override.ca_number || null;

            if (window.location.pathname.includes('/dashboard')) {
                this.form.category = 'calculation';
            } else if (window.location.pathname.includes('/mrus')) {
                this.form.category = 'mru_sync';
            } else if (window.location.pathname.includes('/wallet') || window.location.pathname.includes('/payments')) {
                this.form.category = 'wallet_payment';
            }
        },

        saveRecentTicket(code) {
            if (!code) return;
            if (!this.recentTickets.includes(code)) {
                this.recentTickets.unshift(code);
                if (this.recentTickets.length > 6) this.recentTickets.pop();
                try {
                    localStorage.setItem('nbpdcl_recent_tickets', JSON.stringify(this.recentTickets));
                } catch(e) {}
            }
        },

        submitReport() {
            this.isSubmitting = true;
            this.errorMessage = null;

            const payload = {
                ...this.form,
                client_context: {
                    user_agent: navigator.userAgent,
                    screen: `${window.screen.width}x${window.screen.height}`,
                    viewport: `${window.innerWidth}x${window.innerHeight}`,
                    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                    online: navigator.onLine,
                    console_errors: this.recentConsoleErrors,
                }
            };

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch('/issues/report', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                this.isSubmitting = false;
                if (data.success) {
                    this.submittedCode = data.issue_code;
                    this.saveRecentTicket(data.issue_code);
                } else {
                    this.errorMessage = data.message || 'Failed to submit issue report.';
                }
            })
            .catch(err => {
                this.isSubmitting = false;
                this.errorMessage = 'Network error while submitting report. Please check your connection.';
            });
        },

        trackSubmittedTicket() {
            if (!this.submittedCode) return;
            this.trackCode = this.submittedCode;
            this.activeTab = 'track';
            this.trackTicket();
        },

        trackTicket() {
            if (!this.trackCode) return;
            this.isTrackLoading = true;
            this.trackError = null;
            this.trackResult = null;

            fetch('/issues/track/' + encodeURIComponent(this.trackCode.trim()), {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.isTrackLoading = false;
                if (data.success && data.issue) {
                    this.trackResult = data.issue;
                    this.saveRecentTicket(data.issue.issue_code);
                } else {
                    this.trackError = data.message || 'No issue found with this reference code.';
                }
            })
            .catch(err => {
                this.isTrackLoading = false;
                this.trackError = 'Network error while checking ticket. Please try again.';
            });
        },

        switchTabToMyReports() {
            this.activeTab = 'my_reports';
            this.loadMyReports();
        },

        loadMyReports() {
            this.isMyReportsLoading = true;
            fetch('/issues/my-reports', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                this.isMyReportsLoading = false;
                if (data.success && data.issues) {
                    this.myReportsList = data.issues;
                    this.myReportsCount = data.issues.length;
                }
            })
            .catch(err => {
                this.isMyReportsLoading = false;
            });
        },

        copyText(text) {
            if (!text) return;
            navigator.clipboard.writeText(text);
        }
    };
}
</script>
