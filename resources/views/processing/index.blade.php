<x-app-layout>
    <div x-data="processingHubApp()" x-init="init()" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Minimal Header Bar -->
            <div class="bg-white dark:bg-slate-900 px-6 py-5 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/80 text-blue-600 dark:text-cyan-400 flex items-center justify-center text-lg font-bold">
                        ⚡
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">
                            Data Processing Center
                        </h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                            Automated Bill Downloader & PDF Extractor Hub
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button @click="showCycleModal = true" class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-cyan-300 rounded-2xl text-xs font-bold border border-blue-200 dark:border-blue-800/80 transition" title="Start a new billing cycle for any MRU">
                        <span>⚡ + New Cycle</span>
                    </button>
                    <a :href="getDashboardUrl()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-2xl text-xs font-bold transition shadow-sm" title="View Month Dashboard">
                        <span>📂 Open Dashboard →</span>
                    </a>
                </div>
            </div>

            <!-- 2. Clean Separated Workspace & Dynamic Billing Period Toolbar -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    
                    <!-- MRU Selector -->
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5">🏘️ MRU Workspace</label>
                        <select x-model="selectedMruId" @change="onMruChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500 shadow-sm">
                            @forelse($mrus as $mru)
                                <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }} ({{ $mru->consumer_accounts_count }} CAs)</option>
                            @empty
                                <option value="">No MRUs Found</option>
                            @endforelse
                        </select>
                    </div>

                    <!-- Billing Cycle Selector (Dynamically loaded from existing sessions) -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">📅 Billing Cycle</label>
                            <template x-if="availablePeriods.length > 0">
                                <span class="text-[10px] text-blue-600 dark:text-cyan-400 font-mono font-bold" x-text="availablePeriods.length + ' cycle(s) found'"></span>
                            </template>
                        </div>

                        <!-- Dropdown if existing cycles exist -->
                        <template x-if="availablePeriods.length > 0">
                            <select x-model="selectedPeriodKey" @change="onPeriodKeyChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500 shadow-sm">
                                <template x-for="p in availablePeriods" :key="p.key">
                                    <option :value="p.key" x-text="p.label"></option>
                                </template>
                                <option value="custom">⚡ + Custom / Other Month...</option>
                            </select>
                        </template>

                        <!-- No cycles alert & quick launch -->
                        <template x-if="availablePeriods.length === 0">
                            <div class="flex items-center gap-1.5">
                                <button @click="openNewCycleForCurrentMru()" class="w-full py-2.5 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-bold flex items-center justify-center gap-1 transition">
                                    <span>⚡ + Create First Cycle</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Month & Year Selector (Visible if custom selected or no cycles) -->
                    <div x-show="selectedPeriodKey === 'custom' || availablePeriods.length === 0" class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Month</label>
                            <select x-model="selectedMonth" @change="onCustomDateChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2 px-2 focus:ring-2 focus:ring-blue-500">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Year</label>
                            <select x-model="selectedYear" @change="onCustomDateChange()" class="w-full text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-2 px-2 focus:ring-2 focus:ring-blue-500">
                                @php
                                    $currYr = (int) date('Y');
                                    $yrs = range($currYr - 3, $currYr + 3);
                                    rsort($yrs);
                                @endphp
                                @foreach($yrs as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Placeholder when custom is not open to keep 4-column layout aligned -->
                    <div x-show="selectedPeriodKey !== 'custom' && availablePeriods.length > 0" class="hidden lg:block">
                        <div class="text-[11px] text-slate-400 dark:text-slate-500 font-medium pb-2">
                            Active Cycle: <strong class="text-slate-800 dark:text-slate-200 font-mono" x-text="getCurrentPeriodLabel()"></strong>
                        </div>
                    </div>

                    <!-- 1-Click Pipeline Button -->
                    <div>
                        <button @click="runFullPipeline()" :disabled="pipelineRunning || downloaderRunning || parserRunning || stats.total_cas === 0" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition active:scale-95 disabled:opacity-40 flex items-center justify-center gap-2">
                            <span x-show="!pipelineRunning">⚡ Run Full Pipeline</span>
                            <span x-show="pipelineRunning" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Running Pipeline...
                            </span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- Two Core Processing Control Cards with Visual Progress Bars -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- CARD 1: Bill Downloader (Network I/O) -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-5 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-lg">
                                    📥
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">1. Bill Downloader</h2>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">BSPHCL API Multi-cURL Network Pipeline</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                                  :class="downloaderRunning ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 animate-pulse' : (stats.missing_downloads === 0 && stats.total_cas > 0 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400')"
                                  x-text="downloaderRunning ? '⏳ Downloading...' : (stats.missing_downloads === 0 && stats.total_cas > 0 ? '✅ Up to date' : 'Idle')">
                            </span>
                        </div>

                        <!-- Visual Progress Bar -->
                        <div class="mt-4 space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-mono font-bold">
                                <span class="text-slate-600 dark:text-slate-300">Download Progress</span>
                                <span class="text-blue-600 dark:text-cyan-400" x-text="(stats.download_percent || 0) + '% (' + stats.downloaded_count + '/' + stats.total_cas + ')'"></span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/60">
                                <div class="bg-gradient-to-r from-blue-600 to-cyan-400 h-2 rounded-full transition-all duration-500 shadow-sm" :style="'width: ' + (stats.download_percent || 0) + '%'"></div>
                            </div>
                        </div>

                        <!-- Live Metrics Grid -->
                        <div class="grid grid-cols-3 gap-3 mt-4">
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total CAs</span>
                                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono" x-text="stats.total_cas">0</div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Downloaded</span>
                                <div class="text-lg font-black text-blue-600 dark:text-cyan-400 mt-0.5 font-mono" x-text="stats.downloaded_count">0</div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Missing</span>
                                <div class="text-lg font-black mt-0.5 font-mono" :class="stats.missing_downloads > 0 ? 'text-amber-500' : 'text-emerald-500'" x-text="stats.missing_downloads">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Downloader Actions -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-2 sm:gap-2.5">
                        <button @click="runDownloader('all')" :disabled="downloaderRunning || parserRunning || stats.total_cas === 0" class="w-full sm:flex-1 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                            <span x-show="!downloaderRunning">⚡ Download All CAs</span>
                            <span x-show="downloaderRunning" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Downloading...
                            </span>
                        </button>
                        <button @click="runDownloader('missing_only')" :disabled="downloaderRunning || parserRunning || stats.missing_downloads === 0" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition text-center" title="Download only missing or pending CAs">
                            🔄 Sync Missing (<span x-text="stats.missing_downloads"></span>)
                        </button>
                    </div>
                </div>

                <!-- CARD 2: Bill Parser & Extractor (Local Data Extraction) -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-sm p-6 space-y-5 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-lg">
                                    ⚙️
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">2. PDF Parser & Extractor</h2>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Local PDF Text Data Extraction Engine</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                                  :class="parserRunning ? 'bg-indigo-100 dark:bg-indigo-950/80 text-indigo-800 dark:text-indigo-300 animate-pulse' : (stats.pending_parse === 0 && stats.pdf_bills_count > 0 ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400')"
                                  x-text="parserRunning ? '⚙️ Parsing...' : (stats.pending_parse === 0 && stats.pdf_bills_count > 0 ? '✅ All Extracted' : 'Idle')">
                            </span>
                        </div>

                        <!-- Visual Progress Bar -->
                        <div class="mt-4 space-y-1.5">
                            <div class="flex items-center justify-between text-xs font-mono font-bold">
                                <span class="text-slate-600 dark:text-slate-300">Extraction Progress</span>
                                <span class="text-indigo-600 dark:text-indigo-400" x-text="(stats.parse_percent || 0) + '% (' + stats.parsed_count + '/' + stats.pdf_bills_count + ')'"></span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden p-0.5 border border-slate-200 dark:border-slate-700/60">
                                <div class="bg-gradient-to-r from-indigo-600 to-purple-400 h-2 rounded-full transition-all duration-500 shadow-sm" :style="'width: ' + (stats.parse_percent || 0) + '%'"></div>
                            </div>
                        </div>

                        <!-- Live Metrics Grid -->
                        <div class="grid grid-cols-3 gap-3 mt-4">
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">PDFs on Disk</span>
                                <div class="text-lg font-black text-slate-900 dark:text-white mt-0.5 font-mono" x-text="stats.pdf_bills_count">0</div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Extracted</span>
                                <div class="text-lg font-black text-indigo-600 dark:text-indigo-400 mt-0.5 font-mono" x-text="stats.parsed_count">0</div>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-2xl border border-slate-100 dark:border-slate-800 text-center">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pending Parse</span>
                                <div class="text-lg font-black mt-0.5 font-mono" :class="stats.pending_parse > 0 ? 'text-amber-500' : 'text-emerald-500'" x-text="stats.pending_parse">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Parser Actions -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center gap-2 sm:gap-2.5">
                        <button @click="runParser('pending_only')" :disabled="parserRunning || downloaderRunning || stats.pending_parse === 0" class="w-full sm:flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-500/20 transition flex items-center justify-center gap-1.5">
                            <span x-show="!parserRunning">⚙️ Extract Pending (<span x-text="stats.pending_parse"></span>)</span>
                            <span x-show="parserRunning" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Extracting Data...
                            </span>
                        </button>
                        <button @click="runParser('all')" :disabled="parserRunning || downloaderRunning || stats.pdf_bills_count === 0" class="w-full sm:w-auto px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-40 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition text-center" title="Force re-extract all PDFs in this cycle">
                            🔄 Re-Parse All
                        </button>
                    </div>
                </div>

            </div>

            <!-- 3. Real-Time Console Terminal Stream -->
            <div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden">
                <!-- Terminal Header Bar -->
                <div class="px-6 py-4 bg-slate-900 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                            <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                        </div>
                        <div class="text-xs font-mono font-bold text-slate-300 flex items-center gap-2">
                            <span>💻 Live Execution Stream</span>
                            <span class="text-[10px] font-mono text-slate-500 hidden sm:inline">(process.log)</span>
                            <span x-show="isAnyTaskRunning()" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-cyan-950 text-cyan-400 border border-cyan-800 animate-pulse">
                                LIVE
                            </span>
                        </div>
                    </div>

                    <!-- Terminal Controls -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <button @click="copyLogs()" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-mono transition">
                            📋 Copy Logs
                        </button>
                        <button @click="clearConsoleScreen()" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-mono transition">
                            🧹 Clear Screen
                        </button>
                        <button @click="clearLogFile()" class="px-3 py-1 bg-rose-950/60 hover:bg-rose-900 text-rose-300 rounded-lg text-xs font-mono border border-rose-800/60 transition">
                            🗑️ Reset Log
                        </button>
                    </div>
                </div>

                <!-- Terminal Body Stream -->
                <div id="consoleTerminalBody" class="p-6 font-mono text-xs text-slate-300 h-80 overflow-y-auto space-y-1 select-text">
                    <template x-if="!filteredLogLines || filteredLogLines.length === 0">
                        <div class="text-slate-600 italic py-12 text-center">
                            <span x-show="!searchQuery && activeLogFilter === 'all'">Console ready. Run Downloader or Parser to stream real-time task logs...</span>
                            <span x-show="searchQuery || activeLogFilter !== 'all'">No log entries match the current filter/search criteria.</span>
                        </div>
                    </template>

                    <template x-for="(line, idx) in filteredLogLines" :key="idx">
                        <div class="leading-relaxed break-all py-0.5" :class="{
                            'text-emerald-400 font-bold bg-emerald-950/20 px-1 rounded': line.includes('✅') || line.includes('Task Completed'),
                            'text-rose-400 font-bold bg-rose-950/30 px-1 rounded border-l-2 border-rose-500': line.includes('❌') || line.includes('ERROR') || line.includes('failed'),
                            'text-amber-300 font-bold bg-amber-950/20 px-1 rounded': line.includes('⚠️') || line.includes('Initiating'),
                            'text-cyan-300 font-bold': line.includes('====='),
                            'text-slate-400': !line.includes('✅') && !line.includes('❌') && !line.includes('⚠️') && !line.includes('=====')
                        }" x-text="line"></div>
                    </template>
                </div>
            </div>

            <!-- MODAL: New Billing Cycle (From Processing Center) -->
            <div x-show="showCycleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!cycleInProgress) showCycleModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">New Billing Cycle</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Initialize cycle session or download bills</p>
                            </div>
                        </div>
                        <button @click="showCycleModal = false" :disabled="cycleInProgress" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <!-- For MRU Select -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Target MRU Workspace</label>
                            <select x-model="modalMruId" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                @foreach($mrus as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }}) — {{ $m->consumer_accounts_count }} consumers</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Billing Month & Year Selectors -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Billing Month -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Month</label>
                                <select x-model="modalMonth" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>

                            <!-- Billing Year -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Year</label>
                                <select x-model="modalYear" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    @php
                                        $currYear = (int) date('Y');
                                        $availableYears = range(max(2020, $currYear - 3), $currYear + 5);
                                        rsort($availableYears);
                                    @endphp
                                    @foreach($availableYears as $yr)
                                        <option value="{{ $yr }}">{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Tip Box -->
                        <div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed">
                            💡 <strong>Tip:</strong> Choose <strong>"Create Cycle Only"</strong> to initialize the billing session workspace immediately with preceding readings, or <strong>"Create & Download All"</strong> to fetch official PDFs right away.
                        </div>

                        <!-- Live Progress Box -->
                        <div x-show="cycleInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
                            <div class="flex items-center gap-2 text-white font-bold">
                                <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span>Launching cycle & processing consumers...</span>
                            </div>
                            <div class="text-slate-400 text-[10px]">Processing consumers concurrently. Please wait...</div>
                        </div>

                        <!-- Result notification -->
                        <div x-show="cycleResult" class="p-3.5 rounded-2xl text-xs font-semibold" :class="cycleResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'" x-text="cycleResult?.message"></div>
                    </div>

                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <button type="button" @click="showCycleModal = false" :disabled="cycleInProgress" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                            Cancel
                        </button>

                        <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="launchBillingCycle('create_only')" :disabled="cycleInProgress || !modalMruId" class="w-full sm:w-auto px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 disabled:opacity-40 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition text-center">
                                ➕ Create Cycle Only
                            </button>
                            <button type="button" @click="launchBillingCycle('download_all')" :disabled="cycleInProgress || !modalMruId" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1">
                                <span>⚡</span> Create & Download All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function processingHubApp() {
            return {
                mruPeriodsMap: @js($mruPeriodsMap),
                selectedMruId: '{{ $selectedMruId }}',
                selectedPeriodKey: '{{ $selectedMonth }}_{{ $selectedYear }}',
                selectedMonth: {{ $selectedMonth }},
                selectedYear: {{ $selectedYear }},
                searchQuery: '',
                activeLogFilter: 'all',
                autoScroll: true,

                // Modal state
                showCycleModal: false,
                modalMruId: '{{ $selectedMruId }}',
                modalMonth: {{ now()->month }},
                modalYear: {{ now()->year }},
                cycleInProgress: false,
                cycleResult: null,

                stats: {
                    total_cas: 0,
                    downloaded_count: 0,
                    missing_downloads: 0,
                    failed_count: 0,
                    pdf_bills_count: 0,
                    parsed_count: 0,
                    pending_parse: 0,
                    download_percent: 0,
                    parse_percent: 0,
                    failed_bills: [],
                },

                downloaderRunning: false,
                parserRunning: false,
                pipelineRunning: false,
                logLines: [],
                logPollInterval: null,

                get availablePeriods() {
                    if (!this.selectedMruId) return [];
                    return this.mruPeriodsMap[this.selectedMruId] || [];
                },

                init() {
                    // Check if current period key matches available periods
                    const periods = this.availablePeriods;
                    if (periods.length > 0) {
                        const matching = periods.find(p => p.key === this.selectedPeriodKey);
                        if (!matching) {
                            this.selectedPeriodKey = periods[0].key;
                            this.selectedMonth = periods[0].month;
                            this.selectedYear = periods[0].year;
                        }
                    } else {
                        this.selectedPeriodKey = 'custom';
                    }

                    // Initial fetch
                    this.fetchStatus();
                    this.fetchLogs();

                    // Auto-pause polling when tab hidden
                    document.addEventListener('visibilitychange', () => {
                        if (document.hidden) {
                            this.stopPolling();
                        } else if (this.isAnyTaskRunning()) {
                            this.startPolling();
                        }
                    });
                },

                isAnyTaskRunning() {
                    return this.downloaderRunning || this.parserRunning || this.pipelineRunning || this.cycleInProgress;
                },

                startPolling() {
                    if (this.logPollInterval) return;
                    this.logPollInterval = setInterval(() => {
                        this.fetchLogs();
                        this.fetchStatus();
                        if (!this.isAnyTaskRunning()) {
                            this.stopPolling();
                        }
                    }, 1500);
                },

                stopPolling() {
                    if (this.logPollInterval) {
                        clearInterval(this.logPollInterval);
                        this.logPollInterval = null;
                    }
                },

                get filteredLogLines() {
                    let lines = this.logLines;
                    if (this.searchQuery.trim()) {
                        const q = this.searchQuery.toLowerCase();
                        lines = lines.filter(l => l.toLowerCase().includes(q));
                    }
                    return lines;
                },

                onMruChange() {
                    this.modalMruId = this.selectedMruId;
                    const periods = this.availablePeriods;
                    if (periods.length > 0) {
                        this.selectedPeriodKey = periods[0].key;
                        this.selectedMonth = periods[0].month;
                        this.selectedYear = periods[0].year;
                    } else {
                        this.selectedPeriodKey = 'custom';
                        this.selectedMonth = {{ now()->month }};
                        this.selectedYear = {{ now()->year }};
                    }
                    this.fetchStatus();
                },

                onPeriodKeyChange() {
                    if (this.selectedPeriodKey === 'custom') {
                        return;
                    }
                    const parts = this.selectedPeriodKey.split('_');
                    if (parts.length === 2) {
                        this.selectedMonth = parseInt(parts[0], 10);
                        this.selectedYear = parseInt(parts[1], 10);
                        this.fetchStatus();
                    }
                },

                onCustomDateChange() {
                    this.selectedPeriodKey = `${this.selectedMonth}_${this.selectedYear}`;
                    this.fetchStatus();
                },

                getCurrentPeriodLabel() {
                    const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    return `${months[this.selectedMonth] || ''} ${this.selectedYear}`;
                },

                getDashboardUrl() {
                    return `/dashboard?mru_id=${this.selectedMruId}&month=${this.selectedMonth}&year=${this.selectedYear}`;
                },

                openNewCycleForCurrentMru() {
                    this.modalMruId = this.selectedMruId;
                    this.modalMonth = this.selectedMonth;
                    this.modalYear = this.selectedYear;
                    this.cycleResult = null;
                    this.showCycleModal = true;
                },

                launchBillingCycle(actionType = 'download_all') {
                    if (!this.modalMruId) return;

                    this.cycleInProgress = true;
                    this.cycleResult = null;

                    fetch('/mrus/billing-cycle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mru_id: this.modalMruId,
                            billing_month: this.modalMonth,
                            billing_year: this.modalYear,
                            action_type: actionType
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Error creating cycle');
                        return data;
                    })
                    .then(json => {
                        this.cycleResult = json;
                        if (json.success) {
                            setTimeout(() => {
                                window.location.href = `/processing?mru_id=${this.modalMruId}&month=${this.modalMonth}&year=${this.modalYear}`;
                            }, 800);
                        }
                    })
                    .catch(err => {
                        this.cycleResult = {
                            success: false,
                            message: err.message || 'An error occurred while creating cycle.'
                        };
                    })
                    .finally(() => {
                        this.cycleInProgress = false;
                    });
                },

                fetchStatus() {
                    const url = new URL('/processing/status', window.location.origin);
                    if (this.selectedMruId) url.searchParams.append('mru_id', this.selectedMruId);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);

                    fetch(url)
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.stats) {
                                this.stats = data.stats;
                            }
                        })
                        .catch(err => console.error(err));
                },

                fetchLogs() {
                    fetch('/processing/logs')
                        .then(r => r.json())
                        .then(data => {
                            if (data.success && data.logs !== undefined) {
                                const raw = data.logs.trim();
                                if (raw) {
                                    this.logLines = raw.split('\n');
                                    if (this.autoScroll) {
                                        this.$nextTick(() => {
                                            const el = document.getElementById('consoleTerminalBody');
                                            if (el) el.scrollTop = el.scrollHeight;
                                        });
                                    }
                                }
                            }
                        })
                        .catch(err => console.error(err));
                },

                runDownloader(mode = 'all', explicitCas = null) {
                    this.downloaderRunning = true;
                    this.startPolling();

                    const modeLabel = explicitCas ? `Specific CAs (${explicitCas.length})` : mode;
                    this.logLines.push(`[${new Date().toLocaleTimeString()}] 🚀 Launching Bill Downloader (Mode: ${modeLabel})...`);

                    return fetch('/processing/downloader', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            mru_id: this.selectedMruId || null,
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mode: mode,
                            ca_numbers: explicitCas
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.downloaderRunning = false;
                        this.stopPolling();
                        this.fetchStatus();
                        this.fetchLogs();
                        return data;
                    })
                    .catch(err => {
                        this.downloaderRunning = false;
                        this.stopPolling();
                        this.logLines.push(`[${new Date().toLocaleTimeString()}] ❌ Downloader request error.`);
                        this.fetchStatus();
                        this.fetchLogs();
                        throw err;
                    });
                },

                runParser(mode = 'pending_only') {
                    this.parserRunning = true;
                    this.startPolling();

                    this.logLines.push(`[${new Date().toLocaleTimeString()}] 🚀 Launching PDF Parser & Extractor (Mode: ${mode})...`);

                    return fetch('/processing/parser', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            mru_id: this.selectedMruId || null,
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mode: mode
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.parserRunning = false;
                        this.stopPolling();
                        this.fetchStatus();
                        this.fetchLogs();
                        return data;
                    })
                    .catch(err => {
                        this.parserRunning = false;
                        this.stopPolling();
                        this.logLines.push(`[${new Date().toLocaleTimeString()}] ❌ Parser request error.`);
                        this.fetchStatus();
                        this.fetchLogs();
                        throw err;
                    });
                },

                // ⚡ 1-Click Pipeline: Downloader -> Parser -> Ledger Sync
                runFullPipeline() {
                    this.pipelineRunning = true;
                    this.startPolling();

                    this.logLines.push(`[${new Date().toLocaleTimeString()}] ⚡ === STARTING FULL AUTO-PIPELINE ===`);

                    this.runDownloader('missing_only')
                        .then(() => {
                            return this.runParser('all');
                        })
                        .then(() => {
                            this.pipelineRunning = false;
                            this.stopPolling();
                            this.logLines.push(`[${new Date().toLocaleTimeString()}] 🏆 === PIPELINE COMPLETED SUCCESSFULLY ===`);
                            this.fetchStatus();
                            this.fetchLogs();
                        })
                        .catch(() => {
                            this.pipelineRunning = false;
                            this.stopPolling();
                            this.logLines.push(`[${new Date().toLocaleTimeString()}] ⚠️ Pipeline interrupted with exceptions.`);
                            this.fetchStatus();
                            this.fetchLogs();
                        });
                },

                copyLogs() {
                    const text = this.filteredLogLines.join('\n');
                    navigator.clipboard.writeText(text).then(() => {
                        alert('Filtered logs copied to clipboard!');
                    });
                },

                clearConsoleScreen() {
                    this.logLines = [];
                },

                clearLogFile() {
                    fetch('/processing/logs/clear', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(() => {
                        this.logLines = [];
                    });
                }
            };
        }
    </script>
</x-app-layout>
