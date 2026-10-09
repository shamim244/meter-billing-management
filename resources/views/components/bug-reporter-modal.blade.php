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

            <!-- Tab 1: Report Issue Form -->
            @include('components.bug-reporter.tab-report')

            <!-- Tab 2: Track Ticket by Reference Code -->
            @include('components.bug-reporter.tab-track')

            <!-- Tab 3: My Reports List -->
            @include('components.bug-reporter.tab-my-reports')

        </div>
    </div>
</div>

<script src="{{ asset('js/components/bug-reporter-app.js') }}?v={{ filemtime(public_path('js/components/bug-reporter-app.js')) }}"></script>
