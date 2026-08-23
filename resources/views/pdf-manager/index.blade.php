<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-brand-600 to-indigo-600 p-0.5 shadow-lg shadow-brand-500/20 flex items-center justify-center text-xl text-white">
                    📑
                </div>
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">PDF Document Management Center</h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Inspect, batch export, re-parse, upload, and monitor physical electricity bill PDFs</p>
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" 
                        @click="$dispatch('open-health-modal')"
                        class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                    <span>🩺</span>
                    <span>Storage Health Check</span>
                </button>

                <button type="button" 
                        @click="$dispatch('open-upload-modal')"
                        class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-brand-500/20">
                    <span>📤</span>
                    <span>Upload PDFs / ZIP</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div x-data="pdfManager()" class="py-6 min-h-screen text-slate-800 dark:text-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- 1. Live Storage Analytics KPIs -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Total Physical PDFs -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <span>Stored PDF Files</span>
                        <span class="text-brand-500 text-base">📁</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white font-mono mt-2">
                        {{ number_format($metrics['disk_files_count']) }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                        <span>{{ number_format($metrics['downloaded_count']) }} mapped records</span>
                    </div>
                </div>

                <!-- Total Disk Space Used -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <span>Disk Usage</span>
                        <span class="text-indigo-500 text-base">💾</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 font-mono mt-2">
                        {{ $metrics['total_size_formatted'] }}
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                        <span>~{{ $metrics['avg_size_kb'] }} KB avg per file</span>
                    </div>
                </div>

                <!-- Parsed & Synchronized Rate -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <span>Extraction Rate</span>
                        <span class="text-emerald-500 text-base">⚡</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-2">
                        {{ $metrics['parsed_rate'] }}%
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                        <span>{{ number_format($metrics['parsed_count']) }} extracted & verified</span>
                    </div>
                </div>

                <!-- Storage Health & Quota Tier -->
                <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm relative overflow-hidden">
                    <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase tracking-wider">
                        <span>Plan & Quota</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-brand-100 dark:bg-brand-950 text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-800">
                            {{ $metrics['plan_tier'] }}
                        </span>
                    </div>
                    <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono mt-2">
                        {{ $metrics['storage_limit_mb'] }} MB Limit
                    </div>
                    <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
                        <span class="{{ $metrics['is_limit_exceeded'] ? 'text-rose-500 font-bold' : ($metrics['storage_usage_percent'] > 80 ? 'text-amber-500 font-bold' : 'text-emerald-500') }}">
                            {{ $metrics['storage_usage_percent'] }}% used
                        </span>
                    </div>
                </div>
            </div>

            <!-- 1.5. Storage Quota Usage Meter Bar -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">📊</span>
                        <div>
                            <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Subscription Storage Allocation</span>
                            <span class="text-[11px] text-slate-500 ml-1 font-mono">({{ $metrics['total_size_formatted'] }} / {{ $metrics['storage_limit_formatted'] }})</span>
                        </div>
                    </div>
                    <div class="text-xs font-mono font-bold {{ $metrics['is_limit_exceeded'] ? 'text-rose-500' : ($metrics['storage_usage_percent'] > 80 ? 'text-amber-500' : 'text-emerald-500') }}">
                        {{ $metrics['storage_usage_percent'] }}% Quota Used
                    </div>
                </div>

                <!-- Progress Bar Track -->
                <div class="w-full bg-slate-100 dark:bg-slate-800 h-3 rounded-full overflow-hidden p-0.5 border border-slate-200/60 dark:border-slate-700/60">
                    <div class="h-full rounded-full transition-all duration-500 {{ $metrics['is_limit_exceeded'] ? 'bg-gradient-to-r from-rose-500 to-red-600' : ($metrics['storage_usage_percent'] > 80 ? 'bg-gradient-to-r from-amber-400 to-rose-500' : 'bg-gradient-to-r from-brand-500 via-cyan-400 to-emerald-400') }}"
                         style="width: {{ min(100, $metrics['storage_usage_percent']) }}%">
                    </div>
                </div>

                @if($metrics['is_limit_exceeded'])
                    <div class="p-3 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs font-medium flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span>⚠️</span>
                            <span><strong>Storage Limit Exceeded ({{ $metrics['storage_limit_mb'] }} MB).</strong> Further PDF downloads/uploads are blocked. Purge old cycle PDFs below to immediately reclaim space.</span>
                        </div>
                    </div>
                @elseif($metrics['storage_usage_percent'] > 75)
                    <div class="p-3 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/80 text-amber-800 dark:text-amber-300 text-xs font-medium flex items-center gap-2">
                        <span>💡</span>
                        <span><strong>Tip:</strong> Storage is nearing capacity. Purge completed historical cycle PDFs below to free up space while preserving your ledger records.</span>
                    </div>
                @endif
            </div>

            <!-- 1.8. Billing Cycle PDF Cleanup & Storage Recovery -->
            @if(!empty($cycleStats))
                <div class="bg-gradient-to-br from-white via-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-200/60 dark:border-slate-800 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20 flex items-center justify-center text-xl font-bold shrink-0">
                                🧹
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-black text-slate-900 dark:text-white">Billing Cycle PDF Cleanup (Ledger Protected)</h3>
                                <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400">
                                    Delete physical PDF files from completed cycles to free up storage. <strong>Consumer readings, units, dues & remarks remain 100% preserved in database.</strong>
                                </p>
                            </div>
                        </div>

                        <!-- Delete All Older Cycles Button -->
                        <button type="button" 
                                @click="openDeleteOlderModal()"
                                :disabled="actionRunning"
                                class="w-full sm:w-auto px-4 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 text-xs font-bold transition flex items-center justify-center gap-1.5 shrink-0">
                            <span>⚡</span>
                            <span>Delete All Previous Months' PDFs</span>
                        </button>
                    </div>

                    <!-- Cycles Breakdown Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($cycleStats as $cs)
                            <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border {{ $cs['is_current'] ? 'border-brand-500/40 shadow-xs' : 'border-slate-200/70 dark:border-slate-800/80' }} flex flex-col justify-between space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-black text-slate-900 dark:text-white">{{ $cs['label'] }}</span>
                                        @if($cs['is_current'])
                                            <span class="ml-1.5 px-2 py-0.5 rounded text-[10px] font-bold bg-brand-100 dark:bg-brand-950 text-brand-600 dark:text-cyan-400 border border-brand-200 dark:border-brand-800">
                                                Active Cycle
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300">{{ $cs['total_size_formatted'] }}</span>
                                </div>

                                <div class="flex items-center justify-between text-xs text-slate-500 font-mono">
                                    <span>{{ $cs['pdf_count'] }} PDFs on disk</span>
                                    <span>{{ $cs['total_bills'] }} total CAs</span>
                                </div>

                                <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400">Ledger Preserved</span>

                                    @if($cs['pdf_count'] > 0)
                                        <button type="button" 
                                                @click="openDeleteModal({{ $cs['month'] }}, {{ $cs['year'] }}, '{{ $cs['label'] }}', '{{ $cs['total_size_formatted'] }}', {{ $cs['pdf_count'] }}, {{ $cs['total_bills'] }})"
                                                :disabled="actionRunning"
                                                class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-600 dark:text-rose-300 text-xs font-bold border border-rose-200 dark:border-rose-800/80 transition flex items-center gap-1"
                                                title="Delete PDF files from disk while preserving ledger readings and amounts">
                                            <span>🗑️</span>
                                            <span>Delete PDFs</span>
                                        </button>
                                    @else
                                        <span class="text-[11px] font-mono text-emerald-600 dark:text-emerald-400 font-semibold">Clean (0 B)</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- 2. Filter & Search Toolbar (Auto-Apply on Select) -->
            <div class="bg-white dark:bg-slate-900 p-5 sm:p-6 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Filter & Search</span>
                        <span class="text-[10px] text-brand-600 dark:text-brand-400 font-semibold bg-brand-50 dark:bg-brand-950 px-2 py-0.5 rounded-full border border-brand-200 dark:border-brand-800">
                            ⚡ Instant Auto-Filter
                        </span>
                    </div>

                    @if(!empty($mruId) || !empty($month) || !empty($year) || $status !== 'all' || !empty($search))
                        <a href="{{ route('pdf-manager.index', ['mru_id' => '', 'month' => '', 'year' => '', 'status' => 'all', 'search' => '']) }}" 
                           class="text-xs text-rose-500 hover:text-rose-600 font-semibold transition flex items-center gap-1">
                            <span>✕</span>
                            <span>Clear All Filters</span>
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('pdf-manager.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    <input type="hidden" name="view" :value="viewMode">

                    <!-- MRU Selector -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">MRU Workspace</label>
                        <select name="mru_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                            <option value="">All MRU Areas</option>
                            @foreach($mrus as $m)
                                <option value="{{ $m->id }}" {{ $mruId == $m->id ? 'selected' : '' }}>
                                    {{ $m->code }} - {{ $m->name }} @if($m->bill_records_count > 0)({{ $m->bill_records_count }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Month Selector -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Billing Month</label>
                        <select name="month" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                            <option value="">All Months</option>
                            @for($m = 1; $m <= 12; $m++)
                                @php $cnt = $availableMonths[$m] ?? 0; @endphp
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }} @if($cnt > 0)({{ $cnt }})@endif
                                </option>
                            @endfor
                        </select>
                    </div>

                    <!-- Year Selector -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Billing Year</label>
                        <select name="year" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                            <option value="">All Years</option>
                            @php
                                $cYear = (int)date('Y');
                                $yearsList = range(max(2020, $cYear - 4), $cYear + 2);
                                rsort($yearsList);
                            @endphp
                            @foreach($yearsList as $y)
                                @php $ycnt = $availableYears[$y] ?? 0; @endphp
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                    {{ $y }} @if($ycnt > 0)({{ $ycnt }})@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">PDF Status</label>
                        <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                            <option value="downloaded" {{ $status === 'downloaded' ? 'selected' : '' }}>Downloaded Only</option>
                            <option value="missing" {{ $status === 'missing' ? 'selected' : '' }}>Missing on Disk</option>
                            <option value="parsed" {{ $status === 'parsed' ? 'selected' : '' }}>Parsed & Extracted</option>
                            <option value="unparsed" {{ $status === 'unparsed' ? 'selected' : '' }}>Unparsed</option>
                            <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Parse Failed</option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search CA, Name, Meter, File</label>
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   name="search" 
                                   value="{{ $search }}" 
                                   placeholder="Type CA or filename to filter..." 
                                   @keydown.enter="$el.form.submit()"
                                   class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-brand-500">
                            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white rounded-xl text-xs font-bold transition shrink-0 shadow-sm">
                                🔍
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- 3. Multi-Select Sticky Batch Actions Bar -->
            <div class="bg-slate-900 text-white p-4 rounded-2xl shadow-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-slate-800"
                 x-show="selectedIds.length > 0"
                 x-cloak
                 x-transition>
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl bg-brand-500 text-white font-black text-xs flex items-center justify-center font-mono" x-text="selectedIds.length"></span>
                    <span class="text-xs font-bold text-slate-200">PDFs Selected</span>
                    <button type="button" @click="selectedIds = []" class="text-xs text-slate-400 hover:text-white underline">Clear</button>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Batch ZIP Download -->
                    <button type="button" 
                            @click="triggerBatchDownload()" 
                            class="px-3 py-1.5 bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                        <span>📦 Download ZIP</span>
                    </button>

                    <!-- Batch Re-parse -->
                    <button type="button" 
                            @click="triggerBatchReparse()" 
                            :disabled="actionRunning"
                            class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                        <span>⚡ Re-parse</span>
                    </button>

                    <!-- Batch Re-download -->
                    <button type="button" 
                            @click="triggerBatchRedownload()" 
                            :disabled="actionRunning"
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                        <span>🔄 Re-download</span>
                    </button>

                    <!-- Batch Delete -->
                    <button type="button" 
                            @click="triggerBatchDelete()" 
                            :disabled="actionRunning"
                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5">
                        <span>🗑️ Delete PDFs</span>
                    </button>
                </div>
            </div>

            <!-- 4. Table / Grid Header Control -->
            <div class="flex items-center justify-between">
                <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    Showing <span class="font-bold text-slate-800 dark:text-slate-200">{{ $bills->firstItem() ?? 0 }}-{{ $bills->lastItem() ?? 0 }}</span> of <span class="font-bold text-slate-800 dark:text-slate-200">{{ $bills->total() }}</span> bill documents
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="selectAllOnPage()"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 transition">
                        Select Page (<span x-text="pageIds.length"></span>)
                    </button>

                    <div class="flex items-center bg-slate-100 dark:bg-slate-800 p-1 rounded-xl">
                        <button type="button" 
                                @click="viewMode = 'table'" 
                                :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 shadow-sm text-brand-600 dark:text-brand-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-3 py-1 text-xs rounded-lg transition flex items-center gap-1">
                            <span>📋</span>
                            <span class="hidden sm:inline">Table</span>
                        </button>
                        <button type="button" 
                                @click="viewMode = 'grid'" 
                                :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 shadow-sm text-brand-600 dark:text-brand-400 font-bold' : 'text-slate-500 hover:text-slate-700 dark:hover:text-slate-300'"
                                class="px-3 py-1 text-xs rounded-lg transition flex items-center gap-1">
                            <span>🗃️</span>
                            <span class="hidden sm:inline">Cards</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5. Data Table View -->
            <div x-show="viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800 text-[11px] uppercase font-bold text-slate-500 tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 w-10 text-center">
                                    <input type="checkbox" @change="toggleSelectAll($event)" :checked="isAllSelected()" class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                                </th>
                                <th class="py-3.5 px-4">CA Number / Consumer</th>
                                <th class="py-3.5 px-4">MRU Area</th>
                                <th class="py-3.5 px-4 text-center">Period</th>
                                <th class="py-3.5 px-4 text-center">File Size</th>
                                <th class="py-3.5 px-4 text-center">Extraction</th>
                                <th class="py-3.5 px-4 text-right">Amount</th>
                                <th class="py-3.5 px-4 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            @forelse($bills as $bill)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 text-center">
                                        <input type="checkbox" 
                                               value="{{ $bill->id }}" 
                                               x-model="selectedIds" 
                                               class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-mono font-bold text-brand-600 dark:text-brand-400 text-xs sm:text-sm">
                                            {{ $bill->ca_number }}
                                        </div>
                                        <div class="text-xs font-semibold text-slate-900 dark:text-white truncate max-w-[180px]">
                                            {{ $bill->consumer_name ?: '—' }}
                                        </div>
                                        @if($bill->meter_no)
                                            <div class="text-[10px] text-slate-500 font-mono">Meter: {{ $bill->meter_no }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-cyan-600 dark:text-cyan-400 border border-slate-200 dark:border-slate-700">
                                            {{ $bill->mru ? $bill->mru->code : 'GENERAL' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs font-bold text-slate-600 dark:text-slate-400">
                                        {{ sprintf('%02d/%04d', $bill->billing_month, $bill->billing_year) }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs">
                                        @if($bill->file_exists)
                                            <span class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/80 font-bold">
                                                {{ $bill->file_size_formatted }}
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 border border-rose-200 dark:border-rose-800/80 font-bold text-[10px]">
                                                Missing
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        @if($bill->parse_status === 'parsed')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800">
                                                ⚡ Parsed ({{ $bill->units_consumed }} kWh)
                                            </span>
                                        @elseif($bill->parse_status === 'failed')
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-800" title="{{ $bill->error_message }}">
                                                ❌ Parse Failed
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 border border-slate-200 dark:border-slate-700">
                                                Unparsed
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right font-black font-mono text-slate-900 dark:text-white">
                                        ₹{{ number_format($bill->total_amount, 2) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            @if($bill->file_exists)
                                                <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="p-1.5 rounded-lg bg-brand-50 hover:bg-brand-100 dark:bg-brand-950/60 dark:hover:bg-brand-900 text-brand-600 dark:text-brand-300 transition" title="Preview PDF in tab">
                                                    👁️
                                                </a>
                                                <button type="button" @click="reparseSingle({{ $bill->id }})" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900 text-emerald-600 dark:text-emerald-300 transition" title="Re-parse PDF Data">
                                                    ⚡
                                                </button>
                                                <button type="button" @click="deleteSingle({{ $bill->id }}, '{{ $bill->ca_number }}')" class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/60 dark:hover:bg-rose-900 text-rose-600 dark:text-rose-300 transition" title="Delete PDF">
                                                    🗑️
                                                </button>
                                            @else
                                                <button type="button" 
                                                        @click="redownloadSingle({{ $bill->id }})" 
                                                        :disabled="actionRunning || loadingBillId === {{ $bill->id }}"
                                                        class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 disabled:opacity-50 text-indigo-600 dark:text-indigo-300 text-xs font-bold transition flex items-center gap-1">
                                                    <span x-show="loadingBillId !== {{ $bill->id }}">⬇️ Fetch</span>
                                                    <span x-show="loadingBillId === {{ $bill->id }}" class="inline-flex items-center gap-1 text-[11px]">
                                                        <span class="animate-spin text-[10px]">⏳</span> Fetching...
                                                    </span>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs sm:text-sm">
                                        No electricity bill PDFs found matching the selected filter criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 6. Card / Grid View Mode -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" x-cloak>
                @forelse($bills as $bill)
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col justify-between space-y-4 hover:border-brand-500/50 transition">
                        <div>
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-cyan-600 dark:text-cyan-400">
                                    {{ $bill->mru ? $bill->mru->code : 'GENERAL' }}
                                </span>
                                <input type="checkbox" value="{{ $bill->id }}" x-model="selectedIds" class="rounded border-slate-300 dark:border-slate-700 text-brand-600 focus:ring-brand-500">
                            </div>

                            <div class="mt-3 flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-brand-50 dark:bg-brand-950 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl font-bold shrink-0">
                                    📄
                                </div>
                                <div class="truncate">
                                    <div class="font-mono font-bold text-brand-600 dark:text-brand-400 text-sm">
                                        {{ $bill->ca_number }}
                                    </div>
                                    <div class="text-xs font-semibold text-slate-900 dark:text-white truncate">
                                        {{ $bill->consumer_name ?: 'Consumer ' . $bill->ca_number }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 grid grid-cols-2 gap-2 text-xs font-mono">
                                <div>
                                    <span class="text-[10px] text-slate-400 block uppercase">Period</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">{{ sprintf('%02d/%04d', $bill->billing_month, $bill->billing_year) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-[10px] text-slate-400 block uppercase">Amount</span>
                                    <span class="font-bold text-slate-900 dark:text-white">₹{{ number_format($bill->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <span class="text-[11px] font-mono font-bold {{ $bill->file_exists ? 'text-emerald-500' : 'text-rose-500' }}">
                                {{ $bill->file_exists ? $bill->file_size_formatted : 'Missing' }}
                            </span>

                            <div class="flex items-center gap-1.5">
                                @if($bill->file_exists)
                                    <a href="{{ route('bills.pdf', $bill) }}" target="_blank" class="px-2.5 py-1 rounded-lg bg-brand-50 hover:bg-brand-100 dark:bg-brand-950 dark:hover:bg-brand-900 text-brand-600 dark:text-brand-300 text-xs font-bold transition">
                                        View
                                    </a>
                                @endif
                                <button type="button" @click="deleteSingle({{ $bill->id }}, '{{ $bill->ca_number }}')" class="p-1 rounded-lg text-slate-400 hover:text-rose-500 text-xs">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center text-slate-400 dark:text-slate-500 text-sm">
                        No bill documents found in this view.
                    </div>
                @endforelse
            </div>

            <!-- 7. Pagination Controls -->
            @if($bills->hasPages())
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    {{ $bills->links() }}
                </div>
            @endif

            <!-- 8. Storage Health Modal -->
            <div x-show="showHealthModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showHealthModal = false" class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-cyan-100 dark:bg-cyan-950 text-cyan-600 dark:text-cyan-400 flex items-center justify-center text-xl font-bold">
                                🩺
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Storage Health & Integrity Scanner</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Deep check of physical storage disk vs database registry</p>
                            </div>
                        </div>
                        <button type="button" @click="showHealthModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                        <div x-show="healthScanning" class="py-8 text-center space-y-3">
                            <div class="w-8 h-8 border-4 border-cyan-500 border-t-transparent rounded-full animate-spin mx-auto"></div>
                            <p class="text-xs font-semibold text-slate-500">Scanning physical disk clusters and verifying records...</p>
                        </div>

                        <div x-show="!healthScanning && healthData" class="space-y-4">
                            <!-- Overall verdict -->
                            <div class="p-4 rounded-2xl flex items-center gap-3" :class="healthData?.is_healthy ? 'bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800' : 'bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800'">
                                <span class="text-2xl" x-text="healthData?.is_healthy ? '✅' : '⚠️'"></span>
                                <div>
                                    <div class="text-sm font-black" :class="healthData?.is_healthy ? 'text-emerald-800 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-300'" x-text="healthData?.is_healthy ? 'Storage System is 100% Healthy & Synchronized' : 'Discrepancies Detected'"></div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400" x-text="healthData?.is_healthy ? 'All physical PDFs correspond with active database records.' : 'Some records have missing physical files or orphaned PDFs on disk.'"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-3 text-center text-xs">
                                <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                                    <div class="text-slate-400 text-[10px] uppercase font-bold">Missing Files</div>
                                    <div class="text-lg font-black font-mono mt-1" :class="healthData?.missing_count > 0 ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300'" x-text="healthData?.missing_count || 0"></div>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                                    <div class="text-slate-400 text-[10px] uppercase font-bold">Corrupt (<500B)</div>
                                    <div class="text-lg font-black font-mono mt-1" :class="healthData?.corrupted_count > 0 ? 'text-rose-500' : 'text-slate-700 dark:text-slate-300'" x-text="healthData?.corrupted_count || 0"></div>
                                </div>
                                <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-2xl border border-slate-200 dark:border-slate-800">
                                    <div class="text-slate-400 text-[10px] uppercase font-bold">Orphaned Files</div>
                                    <div class="text-lg font-black font-mono mt-1" :class="healthData?.orphaned_count > 0 ? 'text-amber-500' : 'text-slate-700 dark:text-slate-300'" x-text="healthData?.orphaned_count || 0"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
                        <button type="button" @click="showHealthModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                            Close
                        </button>
                        <button type="button" 
                                @click="runStorageSync()" 
                                :disabled="actionRunning"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-cyan-500/20">
                            <span>🔧 Auto-Heal & Sync Storage</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 9. Manual Upload Dropzone Modal -->
            <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showUploadModal = false" class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-brand-100 dark:bg-brand-950 text-brand-600 dark:text-brand-400 flex items-center justify-center text-xl font-bold">
                                📤
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Upload Electricity Bill PDFs</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Import single .pdf files or a bulk .zip package</p>
                            </div>
                        </div>
                        <button type="button" @click="showUploadModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <form @submit.prevent="submitUpload()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Target MRU</label>
                                <select x-model="uploadMruId" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                                    <option value="">General (No MRU)</option>
                                    @foreach($mrus as $m)
                                        <option value="{{ $m->id }}">{{ $m->code }} - {{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Month</label>
                                <select x-model="uploadMonth" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                                    @for($m = 1; $m <= 12; $m++)
                                        <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-1">Year</label>
                                <select x-model="uploadYear" class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-white focus:ring-brand-500">
                                    @foreach($yearsList as $y)
                                        <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Dropzone area -->
                        <div class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-brand-500 rounded-2xl p-6 text-center transition">
                            <input type="file" id="pdfUploadInput" multiple accept=".pdf,.zip" @change="handleFileSelect($event)" class="hidden">
                            <label for="pdfUploadInput" class="cursor-pointer block space-y-2">
                                <div class="text-3xl">📂</div>
                                <div class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                    Click to select or drag & drop files
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    Supports PDF files named with CA (e.g. <span class="font-mono">102300783538.pdf</span>) or ZIP archives
                                </div>
                            </label>
                        </div>

                        <div x-show="uploadFiles.length > 0" class="space-y-1 max-h-36 overflow-y-auto">
                            <template x-for="(f, idx) in uploadFiles" :key="idx">
                                <div class="text-xs font-mono text-slate-600 dark:text-slate-400 flex items-center justify-between p-2 rounded-lg bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                    <span class="truncate" x-text="f.name"></span>
                                    <span class="text-[10px] text-slate-500 font-bold" x-text="(f.size / 1024).toFixed(1) + ' KB'"></span>
                                </div>
                            </template>
                        </div>

                        <div class="pt-3 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showUploadModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                                Cancel
                            </button>
                            <button type="submit" 
                                    :disabled="uploadFiles.length === 0 || uploadRunning"
                                    class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 disabled:opacity-50 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-brand-500/20">
                                <span x-show="!uploadRunning">🚀 Upload & Parse Files</span>
                                <span x-show="uploadRunning">Uploading...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- 10. Cycle PDF Delete Confirmation Modal -->
            <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showDeleteModal = false" class="bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    
                    <!-- Modal Header -->
                    <div class="p-5 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl font-bold">
                                🗑️
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-900 dark:text-white">Delete Physical PDF Files</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Reclaim disk quota while keeping database ledger safe</p>
                            </div>
                        </div>
                        <button type="button" @click="showDeleteModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                        <!-- Summary Info Card -->
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Target Cycle</span>
                                <span class="font-bold text-slate-900 dark:text-white font-mono text-sm" x-text="deleteModalData.label"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Physical PDFs</span>
                                <span class="font-bold text-rose-600 dark:text-rose-400 font-mono" x-text="deleteModalData.pdfCount + ' Files'"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Disk Space to Reclaim</span>
                                <span class="font-black text-emerald-600 dark:text-emerald-400 font-mono text-sm" x-text="deleteModalData.totalSize"></span>
                            </div>
                        </div>

                        <!-- Ledger Protection Reassurance Box -->
                        <div class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-xs text-emerald-800 dark:text-emerald-300 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold">
                                <span>🛡️</span>
                                <span>100% Database Ledger Protection</span>
                            </div>
                            <p class="text-[11px] text-emerald-700 dark:text-emerald-400 leading-relaxed">
                                Deleting physical PDF files only frees up server disk storage. <strong>All consumer names, meter numbers, kWh readings, dues amounts, and audit remarks remain permanently safe in the database.</strong>
                            </p>
                        </div>

                        <!-- Scope Options -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Deletion Scope</label>
                            
                            <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 cursor-pointer hover:border-brand-500 transition">
                                <input type="radio" name="deleteScope" value="all" x-model="deleteModalData.targetScope" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Delete ALL Physical PDFs in this Cycle</div>
                                    <div class="text-[11px] text-slate-500">Reclaims maximum storage space (recommended for completed cycles).</div>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 cursor-pointer hover:border-brand-500 transition">
                                <input type="radio" name="deleteScope" value="parsed" x-model="deleteModalData.targetScope" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Delete ONLY Verified & Parsed Bills</div>
                                    <div class="text-[11px] text-slate-500">Keep PDFs for bills that still need manual checking or re-parsing.</div>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 p-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 cursor-pointer hover:border-brand-500 transition">
                                <input type="radio" name="deleteScope" value="unparsed" x-model="deleteModalData.targetScope" class="mt-0.5 text-brand-600 focus:ring-brand-500">
                                <div>
                                    <div class="text-xs font-bold text-slate-900 dark:text-white">Delete ONLY Unparsed / Corrupt Bills</div>
                                    <div class="text-[11px] text-slate-500">Removes broken or failed PDF downloads while keeping verified ones.</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-5 sm:p-6 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
                        <button type="button" @click="showDeleteModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                            Cancel
                        </button>
                        <button type="button" 
                                @click="confirmCycleDelete()" 
                                :disabled="deleteRunning"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-rose-500/20">
                            <span x-show="!deleteRunning">🗑️ Confirm & Delete PDFs</span>
                            <span x-show="deleteRunning" class="flex items-center gap-1">
                                <span class="animate-spin text-xs">⏳</span> Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function pdfManager() {
            return {
                viewMode: '{{ $viewMode }}',
                selectedIds: [],
                pageIds: {{ Js::from($bills->pluck('id')->toArray()) }},
                actionRunning: false,
                loadingBillId: null,
                showHealthModal: false,
                healthScanning: false,
                healthData: null,
                showUploadModal: false,
                uploadRunning: false,
                showDeleteModal: false,
                deleteRunning: false,
                deleteModalData: {
                    month: null,
                    year: null,
                    label: '',
                    totalSize: '',
                    pdfCount: 0,
                    totalBills: 0,
                    olderThanCurrent: false,
                    targetScope: 'all',
                },
                uploadMruId: '{{ $mruId }}',
                uploadMonth: '{{ $month ?: now()->month }}',
                uploadYear: '{{ $year ?: now()->year }}',
                uploadFiles: [],

                init() {
                    window.addEventListener('open-health-modal', () => {
                        this.showHealthModal = true;
                        this.runHealthScan();
                    });
                    window.addEventListener('open-upload-modal', () => {
                        this.showUploadModal = true;
                    });
                },

                isAllSelected() {
                    return this.pageIds.length > 0 && this.pageIds.every(id => this.selectedIds.includes(id));
                },

                selectAllOnPage() {
                    this.pageIds.forEach(id => {
                        if (!this.selectedIds.includes(id)) {
                            this.selectedIds.push(id);
                        }
                    });
                },

                toggleSelectAll(e) {
                    if (e.target.checked) {
                        this.selectAllOnPage();
                    } else {
                        this.selectedIds = this.selectedIds.filter(id => !this.pageIds.includes(id));
                    }
                },

                triggerBatchDownload() {
                    if (this.selectedIds.length === 0) return;
                    
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '{{ route('pdf-manager.batch-download') }}';

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    this.selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'bill_ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                },

                triggerBatchReparse() {
                    if (this.selectedIds.length === 0) return;
                    this.actionRunning = true;

                    fetch('{{ route('pdf-manager.batch-reparse') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ bill_ids: this.selectedIds })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        alert(data.message || 'Batch re-parse completed!');
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        alert('Failed to re-parse bills: ' + err);
                    });
                },

                triggerBatchRedownload() {
                    if (this.selectedIds.length === 0) return;
                    if (!confirm('Re-download ' + this.selectedIds.length + ' official PDF bills from NBPDCL servers?')) return;

                    this.actionRunning = true;
                    fetch('{{ route('pdf-manager.batch-redownload') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ bill_ids: this.selectedIds })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        alert(data.message || 'Re-download completed!');
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        alert('Failed to re-download bills: ' + err);
                    });
                },

                triggerBatchDelete() {
                    if (this.selectedIds.length === 0) return;
                    if (!confirm('Permanently delete ' + this.selectedIds.length + ' physical PDF files and reset records to Pending?')) return;

                    this.actionRunning = true;
                    fetch('{{ route('pdf-manager.batch-delete') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ bill_ids: this.selectedIds })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        alert(data.message || 'Deleted successfully!');
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        alert('Failed to delete PDFs: ' + err);
                    });
                },

                reparseSingle(id) {
                    this.actionRunning = true;
                    fetch('{{ route('pdf-manager.batch-reparse') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ bill_ids: [id] })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        alert('PDF data re-extracted successfully!');
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        alert('Error re-parsing: ' + err);
                    });
                },

                deleteSingle(id, ca) {
                    if (!confirm(`Delete physical PDF for CA ${ca}?`)) return;
                    this.actionRunning = true;

                    fetch('{{ route('bills.delete-pdf') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ id: id })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        alert('Error deleting PDF: ' + err);
                    });
                },

                redownloadSingle(id) {
                    this.actionRunning = true;
                    this.loadingBillId = id;
                    fetch('{{ route('pdf-manager.batch-redownload') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ bill_ids: [id] })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        this.loadingBillId = null;
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        this.loadingBillId = null;
                        alert('Error downloading: ' + err);
                    });
                },

                runHealthScan() {
                    this.healthScanning = true;
                    fetch('{{ route('pdf-manager.health-check') }}')
                        .then(r => r.json())
                        .then(data => {
                            this.healthScanning = false;
                            this.healthData = data;
                        })
                        .catch(err => {
                            this.healthScanning = false;
                            alert('Health scan failed: ' + err);
                        });
                },

                runStorageSync() {
                    this.actionRunning = true;
                    fetch('{{ route('pdf-manager.sync-storage') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.actionRunning = false;
                        alert(data.message);
                        window.location.reload();
                    })
                    .catch(err => {
                        this.actionRunning = false;
                        alert('Storage sync error: ' + err);
                    });
                },

                openDeleteModal(month, year, label, sizeFormatted, pdfCount, totalBills) {
                    this.deleteModalData = {
                        month: month,
                        year: year,
                        label: label,
                        totalSize: sizeFormatted,
                        pdfCount: pdfCount,
                        totalBills: totalBills,
                        olderThanCurrent: false,
                        targetScope: 'all',
                    };
                    this.showDeleteModal = true;
                },

                openDeleteOlderModal() {
                    this.deleteModalData = {
                        month: null,
                        year: null,
                        label: 'All Previous Billing Months',
                        totalSize: 'All Old Cycles',
                        pdfCount: 'All Historical',
                        totalBills: 'All Previous',
                        olderThanCurrent: true,
                        targetScope: 'all',
                    };
                    this.showDeleteModal = true;
                },

                confirmCycleDelete() {
                    this.deleteRunning = true;
                    const payload = {
                        target_scope: this.deleteModalData.targetScope,
                    };
                    if (this.deleteModalData.olderThanCurrent) {
                        payload.older_than_current = true;
                    } else {
                        payload.month = this.deleteModalData.month;
                        payload.year = this.deleteModalData.year;
                    }

                    fetch('{{ route('pdf-manager.purge-cycle') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.deleteRunning = false;
                        this.showDeleteModal = false;
                        alert(data.message || 'PDF files deleted successfully!');
                        window.location.reload();
                    })
                    .catch(err => {
                        this.deleteRunning = false;
                        alert('Error deleting PDFs: ' + err);
                    });
                },

                handleFileSelect(e) {
                    this.uploadFiles = Array.from(e.target.files);
                },

                submitUpload() {
                    if (this.uploadFiles.length === 0) return;
                    this.uploadRunning = true;

                    const formData = new FormData();
                    formData.append('billing_month', this.uploadMonth);
                    formData.append('billing_year', this.uploadYear);
                    if (this.uploadMruId) {
                        formData.append('mru_id', this.uploadMruId);
                    }

                    this.uploadFiles.forEach(file => {
                        formData.append('files[]', file);
                    });

                    fetch('{{ route('pdf-manager.upload') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.uploadRunning = false;
                        alert(data.message || 'Files uploaded!');
                        window.location.reload();
                    })
                    .catch(err => {
                        this.uploadRunning = false;
                        alert('Upload failed: ' + err);
                    });
                }
            };
        }
    </script>
</x-app-layout>
