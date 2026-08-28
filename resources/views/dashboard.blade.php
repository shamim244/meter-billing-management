<x-app-layout>
    <div x-data="dashboardApp()" x-init="init()" @keydown.window="onKeyNav($event)" class="py-6 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors overflow-x-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Top Header & Action Bar -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <h1 class="text-xl font-black text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>⚡</span> Billing Hub
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-medium">
                        Manage, verify & analyze monthly consumer bills
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Quick Single CA Pull -->
                    <button @click="showQuickPullModal = true; quickPullCa = ''; quickPullResult = null;" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition" title="Instantly download bill for any CA">
                        <span>⚡</span> Quick Pull CA
                    </button>

                    <!-- Export CSV -->
                    <button @click="exportCsv()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition">
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </button>

                    <!-- Export ZIP -->
                    <button @click="exportZip()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition">
                        <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export ZIP
                    </button>

                    <!-- Bulk Auto-Fill Readings Button -->
                    <button @click="bulkAutoProjectAll()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm shadow-blue-500/20 transition active:scale-95" title="Auto-project working readings with Previous + Average for all accounts in this cycle">
                        <span>⚡</span> Auto-Fill (Prev + Avg)
                    </button>

                    <!-- Keyboard Shortcuts Customizer Button -->
                    <button @click="openShortcutsModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition" title="Customize Keyboard Shortcuts">
                        <span>⌨️</span> Shortcuts
                    </button>

                    <!-- PDF Manager Quick Link -->
                    <a href="{{ route('pdf-manager.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition" title="Overall PDF Management Hub">
                        <span>📑</span> PDF Manager
                    </a>

                    <!-- View Switcher -->
                    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700">
                        <button @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            Table
                        </button>
                        <button @click="setViewMode('card')" :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-3 py-1 rounded-lg text-xs transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Cards
                        </button>
                    </div>
                </div>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>✅</span> {{ session('success') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>ℹ️</span> {{ session('info') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>⚠️</span> {{ session('warning') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-amber-600 dark:text-amber-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>❌</span> {{ session('error') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75">✕</button>
                </div>
            @endif

            <!-- Workspace Selection & Billing Period Bar -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:px-6 sm:py-4 rounded-2xl sm:rounded-3xl shadow-sm border border-slate-200/80 dark:border-slate-800 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3.5">
                <div class="flex flex-wrap items-center gap-3 sm:gap-5">
                    <!-- MRU / Area Selector (Specific MRU required + Inline Create) -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">🏘️ MRU / Area:</span>
                        <div class="flex items-center gap-1.5">
                            <select x-model="filterMru" @change="onMruChange()" class="text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-1.5 px-3 focus:ring-blue-500 focus:border-blue-500 shadow-sm max-w-[150px] sm:max-w-[200px] truncate">
                                @forelse($mrus as $mru)
                                    <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }}</option>
                                @empty
                                    <option value="">No MRU Found</option>
                                @endforelse
                            </select>
                            <button @click="openCreateMruModal()" class="px-2.5 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition shrink-0" title="Create New MRU Workspace">
                                + Create MRU
                            </button>
                        </div>
                    </div>

                    <!-- Billing Period Selector (Specific Period + Inline New Cycle + Sync Missing) -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap">📅 Billing Period:</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <select x-model="selectedPeriodKey" @change="onPeriodChange()" class="text-xs font-bold border-slate-300 dark:border-slate-700 rounded-xl bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white py-1.5 px-3 focus:ring-blue-500 focus:border-blue-500 shadow-sm max-w-[130px] sm:max-w-none">
                                <template x-for="p in availablePeriods" :key="p.key">
                                    <option :value="p.key" x-text="p.label" :selected="p.key === selectedPeriodKey"></option>
                                </template>
                                <option value="" disabled x-show="!availablePeriods || availablePeriods.length === 0" :selected="!availablePeriods || availablePeriods.length === 0">No Cycles Under This MRU</option>
                            </select>
                            <button @click="showNewCycleModal = true" class="px-2.5 py-1.5 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 text-blue-700 dark:text-cyan-300 rounded-xl text-xs font-bold border border-blue-200 dark:border-blue-800/80 transition flex items-center gap-1 shrink-0" title="New Billing Cycle">
                                <span>⚡</span> + New Cycle
                            </button>
                            
                            <!-- Smart Sync Missing Action Button -->
                            <template x-if="counts.missing_pdf > 0">
                                <button @click="syncMissingBills()" :disabled="syncingMissing" class="px-3 py-1.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl text-xs font-bold shadow-sm flex items-center gap-1.5 transition active:scale-95 whitespace-nowrap shrink-0" title="Download only missing/failed bills for this MRU & Period">
                                    <span x-show="!syncingMissing">⚡</span>
                                    <svg x-show="syncingMissing" class="w-3.5 h-3.5 animate-spin text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="syncingMissing ? 'Syncing...' : ('Sync Missing (' + counts.missing_pdf + ')')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="pt-1 lg:pt-0">
                    <a :href="filterMru ? ('/mrus/' + filterMru) : '{{ route('mrus.index') }}'" class="text-xs text-blue-600 dark:text-cyan-400 hover:underline font-bold inline-flex items-center gap-1">
                        <span>📂</span> Manage MRU Workspace →
                    </a>
                </div>
            </div>

            <!-- Top KPI Cards Row -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Total Consumers -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Consumers</span>
                        <span class="text-lg">👥</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="formatNumber(counts.total_consumers ?? {{ $totalConsumers }})">{{ number_format($totalConsumers) }}</div>
                    <span class="text-[11px] text-slate-400 font-medium">Active accounts</span>
                </div>

                <!-- Total Billing -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Billing</span>
                        <span class="text-lg">💰</span>
                    </div>
                    <div class="text-2xl font-black text-blue-600 dark:text-cyan-400 mt-1" x-text="'₹' + formatNumber(counts.filtered_amount ?? {{ $totalPeriodAmount }})">
                        ₹{{ number_format($totalPeriodAmount) }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Combined amount</span>
                </div>

                <!-- Total Units -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Units</span>
                        <span class="text-lg">⚡</span>
                    </div>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1" x-text="formatNumber(counts.filtered_units ?? {{ $totalPeriodUnits }})">
                        {{ number_format($totalPeriodUnits) }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">kWh consumed</span>
                </div>

                <!-- Submitted -->
                <div @click="filterStatus = (filterStatus === 'submitted' ? 'all' : 'submitted'); fetchData(1);" class="bg-emerald-50/70 dark:bg-emerald-950/30 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 cursor-pointer p-4 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">Submitted</span>
                        <span>✅</span>
                    </div>
                    <div class="text-2xl font-black text-emerald-800 dark:text-emerald-200 mt-1" x-text="counts.submitted ?? 0">
                        {{ $statusCounts['submitted'] }}
                    </div>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">Bills processed</span>
                </div>

                <!-- Critical -->
                <div @click="filterStatus = (filterStatus === 'critical' ? 'all' : 'critical'); fetchData(1);" class="bg-rose-50/70 dark:bg-rose-950/30 hover:bg-rose-50 dark:hover:bg-rose-950/50 cursor-pointer p-4 rounded-2xl border border-rose-200 dark:border-rose-800/60 shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider">Critical</span>
                        <span>❌</span>
                    </div>
                    <div class="text-2xl font-black text-rose-800 dark:text-rose-200 mt-1" x-text="counts.critical ?? 0">
                        {{ $statusCounts['critical'] }}
                    </div>
                    <span class="text-[11px] text-rose-600 dark:text-rose-400 font-medium">Cannot submit</span>
                </div>

                <!-- Doubt -->
                <div @click="filterStatus = (filterStatus === 'doubt' ? 'all' : 'doubt'); fetchData(1);" class="bg-amber-50/70 dark:bg-amber-950/30 hover:bg-amber-50 dark:hover:bg-amber-950/50 cursor-pointer p-4 rounded-2xl border border-amber-200 dark:border-amber-800/60 shadow-sm transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider">Doubt</span>
                        <span>⚠️</span>
                    </div>
                    <div class="text-2xl font-black text-amber-800 dark:text-amber-200 mt-1" x-text="counts.doubt ?? 0">
                        {{ $statusCounts['doubt'] }}
                    </div>
                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">Review later</span>
                </div>
            </div>

            <!-- Clean & Separated Controls Section -->
            <div class="space-y-4">
                <!-- 1. Status Filter Pills Container -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="flex flex-wrap items-center gap-2">
                        <button @click="filterStatus = 'all'; fetchData(1)" :class="filterStatus === 'all' ? 'bg-slate-900 dark:bg-blue-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2 rounded-2xl text-xs font-semibold transition">
                            📋 All (<span x-text="counts.all ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'pending'; fetchData(1)" :class="filterStatus === 'pending' ? 'bg-slate-700 dark:bg-slate-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-4 py-2 rounded-2xl text-xs font-semibold transition">
                            ⏳ Pending (<span x-text="counts.pending ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'submitted'; fetchData(1)" :class="filterStatus === 'submitted' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-4 py-2 rounded-2xl text-xs font-semibold transition">
                            ✅ Submitted (<span x-text="counts.submitted ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'critical'; fetchData(1)" :class="filterStatus === 'critical' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-4 py-2 rounded-2xl text-xs font-semibold transition">
                            ❌ Critical (<span x-text="counts.critical ?? 0"></span>)
                        </button>
                        <button @click="filterStatus = 'doubt'; fetchData(1)" :class="filterStatus === 'doubt' ? 'bg-amber-600 text-white shadow-sm font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-4 py-2 rounded-2xl text-xs font-semibold transition">
                            ⚠️ Doubt (<span x-text="counts.doubt ?? 0"></span>)
                        </button>
                    </div>
                </div>

                <!-- 2. Search Bar Container -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <div class="relative w-full">
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchData(1)" placeholder="Search CA / Name / Meter..." class="w-full text-xs rounded-2xl border-slate-300 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white pl-10 pr-4 py-3 focus:ring-blue-500 focus:border-blue-500 shadow-inner" />
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                <!-- 3. Status Priority, Sort By Field & Table/Card View Switcher Container -->
                <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Left: Sorting Dropdowns -->
                    <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full md:w-auto">
                        <!-- Status Priority -->
                        <div class="w-full sm:w-64">
                            <select x-model="statusSort" @change="onStatusSortChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="default">Status: Normal (Default)</option>
                                <option value="pdcs">Status Priority: P-D-C-S</option>
                                <option value="dcps">Status Priority: D-C-P-S</option>
                                <option value="cdps">Status Priority: C-D-P-S</option>
                                <option value="spdc">Status Priority: S-P-D-C</option>
                            </select>
                        </div>

                        <!-- Sort By Field -->
                        <div class="w-full sm:w-60">
                            <select x-model="sortOption" @change="onSortOptionChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="ca_number_asc">Sort: Consumer No (A-Z)</option>
                                <option value="ca_number_desc">Sort: Consumer No (Z-A)</option>
                                <option value="current_reading_asc">Sort: Current Reading (Low-High)</option>
                                <option value="current_reading_desc">Sort: Current Reading (High-Low)</option>
                                <option value="previous_reading_asc">Sort: Previous Reading (Low-High)</option>
                                <option value="previous_reading_desc">Sort: Previous Reading (High-Low)</option>
                                <option value="units_asc">Sort: Units (Low to High)</option>
                                <option value="units_desc">Sort: Units (High to Low)</option>
                                <option value="amount_asc">Sort: Amount (Low to High)</option>
                                <option value="amount_desc">Sort: Amount (High to Low)</option>
                                <option value="meter_no_asc">Sort: Meter No (A-Z)</option>
                                <option value="meter_no_desc">Sort: Meter No (Z-A)</option>
                                <option value="bill_month_asc">Sort: Bill Month (A-Z)</option>
                                <option value="bill_month_desc">Sort: Bill Month (Z-A)</option>
                            </select>
                        </div>

                        <!-- Tag Filter -->
                        <div class="w-full sm:w-56">
                            <select x-model="tagFilter" @change="fetchData(1)" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                                <option value="all">🏷️ All Tags</option>
                                <template x-for="t in availableTags" :key="t.code">
                                    <option :value="t.code" x-text="'🏷️ ' + (t.short_label || t.label)"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Right: Table / Card View Mode Switcher -->
                    <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 self-start md:self-auto">
                        <button @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            Table View
                        </button>
                        <button @click="setViewMode('card')" :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            Cards View
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div x-show="loading" class="flex justify-center py-12">
                <div class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 text-sm font-medium">
                    <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Loading bill records...
                </div>
            </div>

            <!-- No Data State -->
            <div x-show="!loading && items.length === 0" class="bg-white dark:bg-slate-900 p-12 text-center rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="w-16 h-16 bg-blue-50 dark:bg-blue-950/40 text-blue-500 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-200">No bills found for this filter</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 mb-6">There are no records matching your current filter. You can switch filter pills or create a new cycle.</p>
                <div class="flex items-center justify-center gap-3">
                    <button @click="filterStatus = 'all'; fetchData(1)" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition">
                        📋 View All Bills (<span x-text="counts.all ?? 0"></span>)
                    </button>
                    <button @click="showNewCycleModal = true" class="inline-flex items-center gap-1.5 px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        <span>⚡</span> New Billing Cycle
                    </button>
                </div>
            </div>

            <!-- TABLE VIEW -->
            <div x-show="!loading && items.length > 0 && viewMode === 'table'" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                            <tr>
                                <th class="py-3.5 px-3">Consumer</th>
                                <th class="py-3.5 px-3 text-center">✍️ Working Reading</th>
                                <th class="py-3.5 px-3 text-center">📅 Prev (DB)</th>
                                <th class="py-3.5 px-3 text-center">📊 Avg (kWh)</th>
                                <th class="py-3.5 px-3 text-center">📄 PDF Read</th>
                                <th class="py-3.5 px-3 text-right">Amount</th>
                                <th class="py-3.5 px-3 text-center">Month</th>
                                <th class="py-3.5 px-3 text-center">Status</th>
                                <th class="py-3.5 px-3 text-center">Tag</th>
                                <th class="py-3.5 px-3">Remark</th>
                                <th class="py-3.5 px-3 text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                            <template x-for="(bill, index) in items" :key="bill.id">
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition duration-150" :class="{
                                    'bg-emerald-50/30 dark:bg-emerald-950/25': bill.review_status === 'submitted',
                                    'bg-rose-50/30 dark:bg-rose-950/25': bill.review_status === 'critical',
                                    'bg-amber-50/30 dark:bg-amber-950/25': bill.review_status === 'doubt'
                                }">
                                    <!-- Consumer CA & Name -->
                                    <td class="py-3 px-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-lg bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 flex items-center justify-center font-bold text-[11px] font-mono shrink-0" x-text="bill.ca_number.slice(-2)"></div>
                                            <div>
                                                <div class="flex items-center gap-1 flex-wrap">
                                                    <a :href="'/bills/history/' + bill.ca_number" class="font-bold text-blue-600 dark:text-cyan-400 hover:underline font-mono text-xs" x-text="bill.ca_number"></a>
                                                    <button @click="copyText(bill.ca_number)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5" title="Copy CA">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                    </button>
                                                    <span x-show="bill.tariff_category" class="px-1 py-0.2 rounded text-[9px] font-bold font-mono bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/50" x-text="bill.tariff_category"></span>
                                                    <span x-show="bill.billing_basis" class="px-1 py-0.2 rounded text-[9px] font-black font-mono" :class="bill.billing_basis === 'OK' ? 'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-800/50' : (bill.billing_basis === 'MD' ? 'bg-amber-50 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200/50 dark:border-amber-800/50' : 'bg-rose-50 dark:bg-rose-950/70 text-rose-700 dark:text-rose-300 border border-rose-200/50 dark:border-rose-800/50')" :title="'Billing Basis: ' + bill.billing_basis" x-text="bill.billing_basis"></span>
                                                    <template x-if="bill.is_consecutive_alert">
                                                        <span class="px-1 py-0.2 rounded text-[9px] font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
                                                              :title="bill.consecutive_count + ' Consecutive Estimated Cycles (' + bill.billing_basis + ')'"
                                                              x-text="'⚠️ ' + bill.consecutive_count + 'x ' + bill.billing_basis">
                                                        </span>
                                                    </template>
                                                </div>
                                                <div class="text-slate-900 dark:text-white font-semibold truncate max-w-[150px] text-xs" x-text="bill.consumer_name || '—'"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- ✍️ Working Reading (Current Month) -->
                                    <td class="py-3 px-2 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <input type="text" 
                                                   x-model="bill.working_reading" 
                                                   @blur="saveWorkingReading(bill)" 
                                                   @keyup.enter="$event.target.blur()"
                                                   class="w-20 text-center font-mono font-bold text-xs rounded-lg border-blue-200 dark:border-blue-800 bg-blue-50/40 dark:bg-slate-800 text-blue-600 dark:text-cyan-400 py-1 px-1 focus:ring-blue-500" />
                                            <button @click="autoFillWorkingReading(bill)" class="text-[9px] px-1 py-0.5 rounded bg-blue-50 dark:bg-blue-900/60 text-blue-600 dark:text-cyan-300 hover:bg-blue-100 font-bold" title="Auto-fill Prev + Avg">⚡</button>
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="'Diff: ' + (bill.working_diff_units ?? 0) + 'k'"></div>
                                    </td>

                                    <!-- 📅 Previous Reading (DB) -->
                                    <td class="py-3 px-2 text-center font-mono text-xs text-slate-700 dark:text-slate-300">
                                        <div class="font-bold" x-text="bill.db_prev_reading ?? '—'"></div>
                                        <div class="text-[9px] text-slate-400 truncate max-w-[80px]" x-text="bill.db_prev_label || ''"></div>
                                    </td>

                                    <!-- 📊 Average Usage (Avg kWh) -->
                                    <td class="py-3 px-2 text-center font-mono text-xs">
                                        <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(bill.smart_avg_units ?? 50) + ' k'"></span>
                                    </td>

                                    <!-- 📄 Official PDF Reading -->
                                    <td class="py-3 px-2 text-center font-mono text-xs">
                                        <template x-if="bill.official_pdf_reading">
                                            <div>
                                                <div class="font-bold text-slate-800 dark:text-white" x-text="bill.official_pdf_reading"></div>
                                                <template x-if="bill.pdf_sync_status === 'ahead'">
                                                    <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1 py-0.2 rounded" x-text="'+' + (bill.pdf_delta ?? 0) + 'k'"></span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'matched'">
                                                    <span class="text-[9px] font-bold text-blue-600 dark:text-cyan-400 bg-blue-50 dark:bg-blue-950/60 px-1 py-0.2 rounded">Match</span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'invalid_behind'">
                                                    <span class="text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950 px-1 py-0.2 rounded animate-pulse" x-text="'🚨 ' + (bill.pdf_delta ?? 0) + 'k'"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!bill.official_pdf_reading">
                                            <span class="text-slate-400 text-[10px]">⏳ Awaiting</span>
                                        </template>
                                    </td>

                                    <!-- Amount -->
                                    <td class="py-3 px-3 text-right font-extrabold" :class="Number(bill.total_amount) < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white'">
                                        <span x-text="formatCurrency(bill.total_amount)"></span>
                                    </td>

                                    <!-- Month -->
                                    <td class="py-3 px-3 text-center font-mono text-slate-600 dark:text-slate-300 font-bold text-xs" x-text="bill.bill_month_label || (bill.billing_month + '/' + bill.billing_year)"></td>

                                    <!-- Status Actions (Instant Update) -->
                                    <td class="py-3 px-3 text-center">
                                        <div class="inline-flex items-center gap-1">
                                            <button @click="updateBillStatus(bill, 'submitted')" :class="bill.review_status === 'submitted' ? 'bg-emerald-600 text-white shadow-sm ring-2 ring-emerald-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 hover:text-emerald-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Submitted">
                                                ✅
                                            </button>
                                            <button @click="updateBillStatus(bill, 'critical')" :class="bill.review_status === 'critical' ? 'bg-rose-600 text-white shadow-sm ring-2 ring-rose-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 hover:text-rose-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Critical">
                                                ❌
                                            </button>
                                            <button @click="updateBillStatus(bill, 'doubt')" :class="bill.review_status === 'doubt' ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-amber-100 dark:hover:bg-amber-900/50 hover:text-amber-700'" class="px-2.5 py-1 rounded-lg text-xs transition active:scale-95" title="Mark Doubt">
                                                ⚠️
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Tag -->
                                    <td class="py-3 px-3 text-center">
                                        <select @change="setBillTag(bill, $event.target.value)" 
                                                class="text-[10px] font-bold rounded-lg border-slate-200 dark:border-slate-700 py-1 px-1.5 bg-slate-50 dark:bg-slate-800 cursor-pointer"
                                                :class="getTagBadgeClass(bill.tag || defaultTag)">
                                            <template x-for="t in availableTags" :key="t.code">
                                                <option :value="t.code" :selected="(bill.tag === t.code || (!bill.tag && t.code === defaultTag))" x-text="t.short_label || t.label"></option>
                                            </template>
                                        </select>
                                    </td>

                                    <!-- Remark -->
                                    <td class="py-3 px-3 max-w-[170px]">
                                        <div class="flex items-center gap-1.5">
                                            <input type="text" 
                                                   x-model="bill.remark" 
                                                   @focus="onRemarkFocus(bill)" 
                                                   @blur="onRemarkBlur(bill)" 
                                                   placeholder="Add note..." 
                                                   class="w-full text-[11px] rounded-lg border-slate-200 dark:border-slate-700 px-2 py-1 focus:ring-blue-500 focus:border-blue-500 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white" />
                                        </div>
                                    </td>

                                    <!-- Actions & PDF Download -->
                                    <td class="py-3 px-3 text-center">
                                        <div class="inline-flex items-center justify-center gap-1.5">
                                            <template x-if="bill.has_pdf">
                                                <div class="inline-flex items-center gap-1">
                                                    <button @click="openPdfModal(bill)" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-600 dark:text-cyan-400 hover:text-blue-800 dark:hover:text-cyan-300 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-900/60 px-2 py-1 rounded-lg transition" title="Preview PDF Bill">
                                                        📄 View
                                                    </button>
                                                    <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="p-1 text-slate-400 hover:text-blue-600 dark:hover:text-cyan-300 transition" title="Re-download / Sync this Bill">
                                                        <span x-show="syncingSingle !== bill.ca_number">⚡</span>
                                                        <svg x-show="syncingSingle === bill.ca_number" class="w-3.5 h-3.5 animate-spin text-blue-600 dark:text-cyan-300" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!bill.has_pdf">
                                                <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-cyan-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 px-2.5 py-1 rounded-lg transition" title="Download Official PDF">
                                                    <span x-show="syncingSingle !== bill.ca_number">⚡ Pull</span>
                                                    <span x-show="syncingSingle === bill.ca_number" class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                        Pulling
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Table Pagination -->
                <div class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between text-xs">
                    <div class="text-slate-500 dark:text-slate-400 font-medium">
                        Showing <span class="font-bold text-slate-800 dark:text-white" x-text="items.length > 0 ? (pagination.from || 1) : 0"></span> to <span class="font-bold text-slate-800 dark:text-white" x-text="items.length > 0 ? Math.min((pagination.to || items.length), items.length) : 0"></span> of <span class="font-bold text-slate-800 dark:text-white" x-text="pagination.total"></span> records
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="fetchData(pagination.current_page - 1)" :disabled="pagination.current_page <= 1" class="px-3 py-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg font-bold text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                            ⟨ Prev
                        </button>
                        <span class="px-3 py-1 font-bold text-slate-800 dark:text-white" x-text="'Page ' + pagination.current_page + ' of ' + pagination.last_page"></span>
                        <button @click="fetchData(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page" class="px-3 py-1 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg font-bold text-slate-700 dark:text-slate-200 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-600 transition">
                            Next ⟩
                        </button>
                    </div>
                </div>
            </div>

            <!-- TRUE SLIDING CARD CAROUSEL VIEW -->
            <div x-show="!loading && items.length > 0 && viewMode === 'card'" class="space-y-6">
                <!-- Slider Window / Track Container with Swipe Gestures -->
                <div class="overflow-hidden w-full max-w-lg mx-auto select-none rounded-3xl touch-pan-y"
                     @touchstart="touchStartX = $event.changedTouches[0].screenX"
                     @touchend="handleTouchEnd($event)">
                    
                    <!-- Dynamic Sliding Track -->
                    <div class="flex transition-transform duration-300 ease-out will-change-transform"
                         :style="'transform: translateX(-' + (currentCardIndex * 100) + '%);'">
                        
                        <template x-for="(bill, index) in items" :key="bill.id">
                            <div class="min-w-full w-full shrink-0 px-1 box-border">
                                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xl overflow-hidden">
                                    <!-- Top Header Bar (Mobile Responsive & Crisp) -->
                                    <div class="px-4 sm:px-5 py-3.5 sm:py-4 relative text-white" :class="{
                                        'bg-gradient-to-r from-emerald-900 to-slate-900': bill.review_status === 'submitted',
                                        'bg-gradient-to-r from-rose-900 to-slate-900': bill.review_status === 'critical',
                                        'bg-gradient-to-r from-amber-900 to-slate-900': bill.review_status === 'doubt',
                                        'bg-gradient-to-r from-slate-950 to-slate-900': bill.review_status === 'pending'
                                    }">
                                        <div class="flex items-start justify-between gap-2.5">
                                            <!-- Left: 2-Digit Avatar + Name + CA + Copy -->
                                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 text-cyan-300 font-black text-sm flex items-center justify-center font-mono shrink-0" x-text="bill.ca_number.slice(-2)"></div>
                                                <div class="min-w-0 flex-1">
                                                    <h2 class="text-sm sm:text-base font-bold text-white tracking-tight truncate" x-text="bill.consumer_name || 'CONSUMER ACCOUNT'"></h2>
                                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                        <span class="font-mono text-cyan-200 text-xs font-semibold" x-text="bill.ca_number"></span>
                                                        <button @click="copyText(bill.ca_number)" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-white/10 hover:bg-white/20 text-cyan-200 text-[10px] font-bold transition border border-white/20" title="Copy CA to clipboard">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                            <span>Copy</span>
                                                            <span class="hidden sm:inline-block text-[8px] opacity-75 font-mono" x-text="'[' + (shortcuts.copy_ca?.toUpperCase() || 'C') + ']'"></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Right: Month & Status Badge -->
                                            <div class="text-right space-y-1 shrink-0 flex flex-col items-end">
                                                <div class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-white/10 text-cyan-300 font-mono inline-block whitespace-nowrap" x-text="bill.bill_month_label || 'MONTH BILL'"></div>
                                                <div>
                                                    <span :class="{
                                                        'bg-emerald-500 text-white': bill.review_status === 'submitted',
                                                        'bg-rose-500 text-white': bill.review_status === 'critical',
                                                        'bg-amber-500 text-white': bill.review_status === 'doubt',
                                                        'bg-slate-700 text-slate-300': bill.review_status === 'pending'
                                                    }" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block whitespace-nowrap" x-text="bill.review_status === 'pending' ? '⏳ PENDING' : (bill.review_status === 'submitted' ? '✅ SUBMITTED' : (bill.review_status === 'critical' ? '❌ CRITICAL' : '⚠️ DOUBT'))"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 3-Column Meta Banner: [Tariff Category] | [Total Amount] | [Billing Basis] -->
                                    <div class="px-4 sm:px-5 py-2.5 bg-slate-100 dark:bg-slate-800/90 border-b border-slate-200/80 dark:border-slate-700 flex items-center justify-between gap-2">
                                        <!-- Left: Tariff Category -->
                                        <div class="flex items-center gap-1 min-w-[65px] sm:min-w-[75px]">
                                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:inline">Tariff:</span>
                                            <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-xs font-black font-mono bg-indigo-100 dark:bg-indigo-950/90 text-indigo-700 dark:text-indigo-300 border border-indigo-300/80 dark:border-indigo-800" x-text="bill.tariff_category || 'GEN'"></span>
                                        </div>

                                        <!-- Center: Total Amount -->
                                        <div class="text-center">
                                            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider block leading-none mb-0.5" 
                                                  :class="Number(bill.total_amount) < 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-400 dark:text-slate-500'" 
                                                  x-text="Number(bill.total_amount) < 0 ? 'Advance / Credit' : 'Total Amount'"></span>
                                            <div class="font-black leading-tight" 
                                                 :class="{
                                                     'text-lg sm:text-xl': amountSize === 'standard',
                                                     'text-xl sm:text-2xl': amountSize === 'large',
                                                     'text-emerald-600 dark:text-emerald-400': Number(bill.total_amount) < 0,
                                                     'text-blue-600 dark:text-cyan-400': Number(bill.total_amount) > 0,
                                                     'text-slate-500 dark:text-slate-400': Number(bill.total_amount) == 0
                                                 }" 
                                                 x-text="formatCurrency(bill.total_amount)"></div>
                                        </div>

                                        <!-- Right: Billing Basis -->
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider hidden sm:inline">Basis:</span>
                                            <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-xs font-black font-mono" :class="bill.billing_basis === 'OK' ? 'bg-emerald-100 dark:bg-emerald-950/90 text-emerald-700 dark:text-emerald-300 border border-emerald-300/80 dark:border-emerald-800' : (bill.billing_basis === 'MD' ? 'bg-amber-100 dark:bg-amber-950/90 text-amber-700 dark:text-amber-300 border border-amber-300/80 dark:border-amber-800' : 'bg-rose-100 dark:bg-rose-950/90 text-rose-700 dark:text-rose-300 border border-rose-300/80 dark:border-rose-800')" :title="'Billing Basis: ' + bill.billing_basis" x-text="bill.billing_basis || 'OK'"></span>
                                            <template x-if="bill.is_consecutive_alert">
                                                <span class="px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg text-xs font-black uppercase bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
                                                      :title="bill.consecutive_count + ' Consecutive Estimated Cycles (' + (bill.billing_basis || 'LK') + ')'"
                                                      x-text="'⚠️ ' + bill.consecutive_count + 'x ' + (bill.billing_basis || 'LK')">
                                                </span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- 2x2 Data Grid: The 4-Box Reading Architecture (Mobile-Optimized Single-Line Labels) -->
                                    <div class="p-3 sm:p-5 grid grid-cols-2 gap-2.5 sm:gap-3 bg-slate-50/50 dark:bg-slate-900/50">
                                        <!-- Box 1: ✍️ Working Reading (Current Month) -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border shadow-sm flex flex-col justify-between" :class="bill.pdf_sync_status === 'invalid_behind' ? 'border-rose-400 dark:border-rose-700 bg-rose-50/20' : 'border-blue-200 dark:border-blue-800/80'">
                                            <!-- Top: Header Label & Shortcut -->
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[10px] font-black uppercase tracking-wider block truncate" :class="bill.pdf_sync_status === 'invalid_behind' ? 'text-rose-600 dark:text-rose-400' : 'text-blue-700 dark:text-cyan-300'">✍️ Working</span>
                                                    <span class="hidden sm:inline-block text-[9px] font-mono bg-blue-100 dark:bg-blue-950 px-1 py-0.2 rounded text-blue-700 dark:text-cyan-300 font-bold" x-text="'[' + (shortcuts.focus_reading?.toUpperCase() || 'R') + ']'"></span>
                                                </div>
                                            </div>

                                            <!-- Middle: Full-width Input -->
                                            <div class="mt-1">
                                                <input type="text" 
                                                       :id="'working-reading-input-' + bill.id"
                                                       x-model="bill.working_reading" 
                                                       @blur="saveWorkingReading(bill)" 
                                                       @keydown.escape="$el.blur()"
                                                       @keyup.enter="saveWorkingReading(bill); const wasFiltered = updateBillStatus(bill, 'submitted'); if (!wasFiltered) nextCard();"
                                                       placeholder="Enter reading" 
                                                       class="w-full text-base sm:text-lg font-black bg-blue-50/40 dark:bg-slate-900/60 border rounded-xl px-2 py-1 font-mono focus:ring-blue-500 focus:border-blue-500 text-center"
                                                       :class="bill.pdf_sync_status === 'invalid_behind' ? 'border-rose-400 text-rose-600 dark:text-rose-400' : 'border-blue-200 dark:border-blue-800 text-blue-600 dark:text-cyan-400'">
                                            </div>

                                            <!-- Bottom / Downline: Left (Diff) | Center (🚨 < PDF!) | Right (Auto-fill) -->
                                            <div class="mt-1 flex items-center justify-between text-[10px] border-t border-slate-100 dark:border-slate-700/60 pt-1 gap-1">
                                                <span class="text-slate-700 dark:text-slate-300 font-mono font-bold shrink-0 truncate" x-text="'Diff: ' + (bill.working_diff_units ?? 0)"></span>
                                                <div class="text-center truncate">
                                                    <template x-if="bill.pdf_sync_status === 'invalid_behind'">
                                                        <span class="text-[8px] sm:text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950/80 px-1 py-0.2 rounded animate-pulse">🚨 &lt; PDF!</span>
                                                    </template>
                                                </div>
                                                <div class="text-right shrink-0 flex items-center gap-1">
                                                    <button @click="autoFillWorkingReading(bill)" class="text-[9px] sm:text-[10px] font-bold text-blue-600 dark:text-cyan-400 hover:text-blue-800 dark:hover:text-cyan-300 hover:underline flex items-center gap-0.5 transition" title="Auto-fill with Prev + Avg (enforcing >= PDF)">
                                                        <span>⚡ Auto</span>
                                                        <span class="hidden sm:inline-block text-[8px] font-mono opacity-75" x-text="'[' + (shortcuts.auto_fill_reading?.toUpperCase() || 'A') + ']'"></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Box 2: 📅 Previous Reading (DB) -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📅 Prev (DB)</span>
                                            <div class="text-base sm:text-lg font-black text-slate-700 dark:text-slate-200 my-0.5 sm:my-1 font-mono text-center" x-text="bill.db_prev_reading ?? '—'"></div>
                                            <div class="text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate" x-text="bill.db_prev_label ? (bill.db_prev_label.startsWith('From ') ? bill.db_prev_label : 'From: ' + bill.db_prev_label) : 'Baseline'"></div>
                                        </div>

                                        <!-- Box 3: 📊 Average Usage (Avg kWh) -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📊 Avg Usage</span>
                                            <div class="text-base sm:text-lg font-black text-slate-800 dark:text-white my-0.5 sm:my-1 font-mono text-center" x-text="(bill.smart_avg_units ?? 50) + ' kWh'"></div>
                                            <div class="text-[9px] sm:text-[10px] text-slate-400 border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate" x-text="bill.smart_avg_label || 'History Avg'"></div>
                                        </div>

                                        <!-- Box 4: 📄 Official PDF Reading -->
                                        <div class="bg-white dark:bg-slate-800 p-2.5 sm:p-3 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-sm flex flex-col justify-between">
                                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate">📄 PDF Read</span>
                                            <div class="text-base sm:text-lg font-black my-0.5 sm:my-1 font-mono text-center" :class="bill.official_pdf_reading ? 'text-slate-800 dark:text-white' : 'text-slate-400'" x-text="bill.official_pdf_reading ?? '—'"></div>
                                            <div class="border-t border-slate-100 dark:border-slate-700/60 pt-1 truncate">
                                                <template x-if="bill.pdf_sync_status === 'ahead'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-1 sm:px-1.5 py-0.2 rounded" x-text="'⚡ +' + (bill.pdf_delta ?? 0) + ' Ahead'"></span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'matched'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-blue-600 dark:text-cyan-400 bg-blue-50 dark:bg-blue-950/60 px-1 sm:px-1.5 py-0.2 rounded">✅ Exact Match</span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'invalid_behind'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-black text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950 px-1 sm:px-1.5 py-0.2 rounded animate-pulse" x-text="'🚨 ' + (bill.pdf_delta ?? 0) + ' Behind!'"></span>
                                                </template>
                                                <template x-if="bill.pdf_sync_status === 'awaiting'">
                                                    <span class="inline-flex items-center gap-0.5 text-[8px] sm:text-[9px] font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 px-1 sm:px-1.5 py-0.2 rounded">⏳ Awaiting</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Action Buttons (Mobile-Optimized Clean Widths) -->
                                    <div class="px-3 sm:px-5 py-2.5 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                                        <button @click="updateBillStatus(bill, 'submitted')" :class="bill.review_status === 'submitted' ? 'bg-emerald-600 text-white shadow-md ring-2 ring-emerald-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 hover:text-emerald-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
                                            <span>✅ Submitted</span>
                                            <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.submit_ok || 'Enter') + ']'"></span>
                                        </button>
                                        <button @click="updateBillStatus(bill, 'critical')" :class="bill.review_status === 'critical' ? 'bg-rose-600 text-white shadow-md ring-2 ring-rose-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-rose-50 dark:hover:bg-rose-950/50 hover:text-rose-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
                                            <span>❌ Critical</span>
                                            <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.mark_critical || '3') + ']'"></span>
                                        </button>
                                        <button @click="updateBillStatus(bill, 'doubt')" :class="bill.review_status === 'doubt' ? 'bg-amber-600 text-white shadow-md ring-2 ring-amber-400 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-amber-50 dark:hover:bg-amber-950/50 hover:text-amber-700'" class="flex-1 py-2 sm:py-2.5 rounded-xl font-bold text-xs transition active:scale-95 flex items-center justify-center gap-1">
                                            <span>⚠️ Doubt</span>
                                            <span class="hidden sm:inline-block text-[9px] font-mono opacity-60 bg-black/10 dark:bg-white/10 px-1 rounded" x-text="'[' + (shortcuts.mark_doubt || '2') + ']'"></span>
                                        </button>
                                    </div>

                                    <!-- Remark / Notes Section (Clean & Compact) -->
                                    <div class="px-3 sm:px-5 py-3 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200/80 dark:border-slate-700 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                                <span>💬</span> Remark / Note:
                                                <span class="hidden sm:inline-block text-[10px] text-slate-400 font-mono" x-text="'[' + (shortcuts.open_remark?.toUpperCase() || 'M') + ']'"></span>
                                            </label>
                                            <div class="flex items-center gap-1">
                                                <button @click="saveBillRemark(bill, true)" class="px-2 py-0.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold shadow-xs transition flex items-center gap-1">
                                                    <span>💾 Save</span>
                                                    <span class="hidden sm:inline-block text-[8px] font-mono opacity-70">[Ctrl+↵]</span>
                                                </button>
                                                <button @click="clearBillRemark(bill)" class="px-1.5 py-0.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg text-[10px] font-semibold transition">
                                                    🗑 Clear
                                                </button>
                                            </div>
                                        </div>

                                        <textarea :id="'remark-input-' + bill.id"
                                                  x-model="bill.remark" 
                                                  @focus="onRemarkFocus(bill)"
                                                  @blur="onRemarkBlur(bill)"
                                                  @keydown.escape="$el.blur()"
                                                  @keydown.ctrl.enter="saveBillRemark(bill, true); $el.blur();"
                                                  @keydown.meta.enter="saveBillRemark(bill, true); $el.blur();"
                                                  rows="2" 
                                                  placeholder="Add observation note..." 
                                                  class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-600 p-2 bg-white dark:bg-slate-900 text-slate-800 dark:text-white focus:ring-blue-500 focus:border-blue-500"></textarea>

                                        <!-- Optional Quick Presets (Only displayed if enabled in preferences) -->
                                        <div x-show="showRemarkPresets" class="flex flex-wrap items-center gap-1.5 pt-0.5" x-cloak>
                                            <button type="button" @click="bill.remark = 'Door Locked'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">🚪 Door Locked</button>
                                            <button type="button" @click="bill.remark = 'Meter Burnt'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">🔥 Meter Burnt</button>
                                            <button type="button" @click="bill.remark = 'Meter Stopped'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">⛔ Meter Stopped</button>
                                            <button type="button" @click="bill.remark = 'High Usage'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">📈 High Usage</button>
                                            <button type="button" @click="bill.remark = 'Verified OK'; saveBillRemark(bill, true);" class="px-2 py-0.5 rounded-lg text-[9px] font-semibold bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 transition">✅ Verified OK</button>
                                        </div>
                                    </div>

                                    <!-- Tag Selection Section (Clean, Focused, Responsive Mobile & Desktop) -->
                                    <div class="px-3 sm:px-5 py-2.5 bg-slate-100/70 dark:bg-slate-800/50 border-t border-slate-200/80 dark:border-slate-700 space-y-1.5">
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold text-slate-600 dark:text-slate-300 flex items-center gap-1.5">
                                                <span>🏷️</span> Tag:
                                            </label>
                                            <!-- Active Tag Indicator Badge -->
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider transition-all"
                                                  :class="getTagBadgeClass(bill.tag || defaultTag)"
                                                  x-text="getTagDisplayLabel(bill.tag || defaultTag)"
                                                  :title="getTagFullLabel(bill.tag || defaultTag)">
                                            </span>
                                        </div>

                                        <!-- Responsive Tag Pills Selection Bar -->
                                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                            <template x-for="tagItem in availableTags" :key="tagItem.code">
                                                <button type="button" 
                                                        @click="setBillTag(bill, tagItem.code)"
                                                        :title="tagItem.label"
                                                        class="px-2.5 py-1 rounded-xl text-[10px] font-bold transition-all flex items-center gap-1 cursor-pointer border shadow-xs"
                                                        :class="(bill.tag === tagItem.code || (!bill.tag && tagItem.code === defaultTag)) 
                                                            ? getActivePillClass(tagItem.color) 
                                                            : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                                                    <span x-show="(bill.tag === tagItem.code || (!bill.tag && tagItem.code === defaultTag))" class="text-[9px]">✓</span>
                                                    <span x-text="tagItem.short_label || tagItem.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Card Footer -->
                                    <div class="px-4 sm:px-5 py-2.5 bg-slate-900 dark:bg-slate-950 text-white flex items-center justify-between text-xs font-medium border-t border-slate-800">
                                        <span class="flex items-center gap-1.5 text-cyan-300 font-bold font-mono text-[11px]">
                                            ⚡ Meter: <span class="text-white" x-text="bill.meter_no || '—'"></span>
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <template x-if="bill.has_pdf">
                                                <div class="flex items-center gap-2">
                                                    <button @click="openPdfModal(bill)" class="text-cyan-400 hover:text-white font-bold flex items-center gap-1 text-[11px] transition">
                                                        📄 View PDF →
                                                    </button>
                                                    <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="p-1 text-slate-400 hover:text-cyan-300 transition" title="Re-download / Refresh Bill">
                                                        <span x-show="syncingSingle !== bill.ca_number">⚡</span>
                                                        <svg x-show="syncingSingle === bill.ca_number" class="w-3.5 h-3.5 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    </button>
                                                </div>
                                            </template>
                                            <template x-if="!bill.has_pdf">
                                                <button @click="downloadSingleBill(bill)" :disabled="syncingSingle === bill.ca_number" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white font-bold text-[10px] rounded-lg transition flex items-center gap-1">
                                                    <span x-show="syncingSingle !== bill.ca_number">⚡ Download</span>
                                                    <span x-show="syncingSingle === bill.ca_number" class="flex items-center gap-1">
                                                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                    </div>
                </div>

                <!-- Navigation Controller (Placed AFTER card info) -->
                <div class="flex items-center justify-between bg-white dark:bg-slate-900 px-6 py-3.5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm max-w-lg mx-auto">
                    <button @click="prevCard()" :disabled="currentCardIndex <= 0 && pagination.current_page <= 1" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:cursor-not-allowed text-slate-700 dark:text-slate-200 rounded-2xl font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Prev Card
                    </button>

                    <div class="text-center">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Slide Counter</div>
                        <div class="text-sm font-black text-slate-900 dark:text-white mt-0.5">
                            <span class="text-blue-600 dark:text-cyan-400 font-mono" x-text="items.length > 0 ? ((pagination.current_page - 1) * pagination.per_page + currentCardIndex + 1) : 0"></span>
                            <span class="text-slate-400">/</span>
                            <span class="text-slate-600 dark:text-slate-300 font-mono" x-text="pagination.total"></span>
                        </div>
                    </div>

                    <button @click="nextCard()" :disabled="currentCardIndex >= items.length - 1 && pagination.current_page >= pagination.last_page" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-30 disabled:cursor-not-allowed text-white rounded-2xl font-bold text-xs transition flex items-center gap-1.5 shadow-md shadow-blue-500/20">
                        Next Card
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>

                <!-- Slide Navigation Dots (up to 30 visible dots) -->
                <div class="flex flex-wrap items-center justify-center gap-1.5 max-w-md mx-auto pt-1">
                    <template x-for="(b, i) in items.slice(0, Math.min(items.length, 30))" :key="b.id">
                        <button @click="currentCardIndex = i"
                                :class="i === currentCardIndex ? 'bg-blue-600 dark:bg-cyan-400 w-6 h-2 rounded-full shadow-sm' : 'bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 w-2 h-2 rounded-full'"
                                class="transition-all duration-200 focus:outline-none"
                                :title="'Go to card ' + (i + 1)">
                        </button>
                    </template>
                </div>
            </div>

            <!-- FLOATING TOAST NOTIFICATION WITH UNDO -->
            <div x-show="toast.show" 
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 x-cloak
                 class="fixed bottom-6 right-6 z-50 max-w-md bg-slate-900/95 dark:bg-slate-800/95 text-white backdrop-blur-md px-5 py-3.5 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xl" x-text="toast.icon"></span>
                    <div>
                        <div class="text-xs font-bold text-white tracking-wide" x-text="toast.message"></div>
                        <div class="text-[10px] text-slate-400 font-medium">Auto-saved to database</div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <template x-if="toast.undoData">
                        <button @click="undoLastAction()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl text-xs font-bold transition flex items-center gap-1 shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                            Undo
                        </button>
                    </template>
                    <button @click="toast.show = false" class="text-slate-400 hover:text-white p-1">
                        ✕
                    </button>
                </div>
            </div>

            <!-- MODAL 1: Create New MRU Workspace -->
            <div x-show="showCreateMruModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!isSubmittingMru) showCreateMruModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                🏘️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Create MRU Workspace</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Define permanent meter reading zone</p>
                            </div>
                        </div>
                        <button @click="showCreateMruModal = false" :disabled="isSubmittingMru" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <form @submit.prevent="submitCreateMru()" class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <div x-show="createMruError" class="p-3 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-xs font-semibold rounded-xl" x-text="createMruError"></div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Code *</label>
                            <input type="text" x-model="newMruCode" placeholder="e.g. 0477, 0473, LAHGARIYA_LALPUR" required class="w-full text-xs font-mono uppercase rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <span class="text-[10px] text-slate-400 mt-1 block">Used as physical directory folder for PDFs.</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Village / Area Name *</label>
                            <input type="text" x-model="newMruName" placeholder="e.g. Gerua, Hala, Lalpur" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Full Identifier / Sub-Division (Optional)</label>
                            <input type="text" x-model="newMruIdentifier" placeholder="e.g. Sub-division 04 / Gerua North" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showCreateMruModal = false" :disabled="isSubmittingMru" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">
                                Cancel
                            </button>
                            <button type="submit" :disabled="isSubmittingMru" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                                <svg x-show="isSubmittingMru" class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="isSubmittingMru ? 'Verifying...' : 'Create Workspace'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- POPUP MODAL: MRU Already Exists Interactive Notice -->
            <div x-show="showExistingMruPopup" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showExistingMruPopup = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-base">
                                ℹ️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">MRU Already Exists</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Workspace is already registered in your account</p>
                            </div>
                        </div>
                        <button @click="showExistingMruPopup = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <div class="p-3.5 bg-amber-50/80 dark:bg-amber-950/40 rounded-2xl border border-amber-200/80 dark:border-amber-800/60">
                            <p class="text-xs text-amber-900 dark:text-amber-200 leading-relaxed">
                                You already have an active workspace registered with MRU code <strong class="font-mono font-bold" x-text="existingMruData?.code"></strong> (<span class="font-semibold" x-text="existingMruData?.name"></span>).
                            </p>
                        </div>

                        <!-- Existing MRU Summary Card -->
                        <div class="bg-slate-50 dark:bg-slate-800/70 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 dark:text-white" x-text="existingMruData?.name"></span>
                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-mono font-bold bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800/60" x-text="existingMruData?.code"></span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-1 border-t border-slate-200/60 dark:border-slate-700/60">
                                <span>Master Consumers Registered:</span>
                                <strong class="font-mono text-slate-900 dark:text-white font-bold" x-text="existingMruData?.consumers_count"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons in Popup -->
                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5">
                        <button type="button" @click="showExistingMruPopup = false; showCreateMruModal = true;" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                            ✏️ Change Code
                        </button>
                        <a :href="existingMruData?.dashboard_url" class="w-full sm:w-auto px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5">
                            <span>📊</span> Dashboard
                        </a>
                        <a :href="existingMruData?.show_url" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                            <span>🚀</span> Open Workspace
                        </a>
                    </div>
                </div>
            </div>

            <!-- MODAL 2: New Billing Cycle for Active MRU -->
            <div x-show="showNewCycleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!cycleInProgress) showNewCycleModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">New Billing Cycle</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Initialize or download a monthly billing period</p>
                            </div>
                        </div>
                        <button @click="showNewCycleModal = false" :disabled="cycleInProgress" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <!-- For MRU Select -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1.5">For MRU</label>
                            <select x-model="filterMru" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500">
                                @foreach($mrus as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }}) — {{ $m->consumer_accounts_count }} consumers</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Billing Month & Year Selectors -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Billing Month -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Billing Month</label>
                                <select x-model="cycleMonth" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500">
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

                            <!-- Billing Year (Dynamic Range) -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 uppercase mb-1">Billing Year</label>
                                <select x-model="cycleYear" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3 focus:ring-blue-500 focus:border-blue-500">
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

                        <!-- Tip -->
                        <div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed">
                            💡 <strong>Tip:</strong> Choose <strong>"Create Cycle Only"</strong> to initialize the billing session workspace without API wait, or <strong>"Create & Download All"</strong> to download PDFs immediately.
                        </div>

                        <!-- Progress Box -->
                        <div x-show="cycleInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
                            <div class="flex items-center gap-2 text-white font-bold">
                                <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="executingAction === 'create_only' ? 'Initializing cycle workspace...' : 'Launching cycle & downloading bills...'"></span>
                            </div>
                            <div class="text-slate-400 text-[10px]">Processing consumers in parallel. Please wait.</div>
                        </div>

                        <!-- Result message -->
                        <div x-show="cycleResult" class="p-3.5 rounded-2xl text-xs font-semibold" :class="cycleResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'" x-text="cycleResult?.message"></div>
                    </div>

                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <button type="button" @click="showNewCycleModal = false" :disabled="cycleInProgress" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                            Cancel
                        </button>

                        <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="launchBillingCycle('create_only')" :disabled="cycleInProgress || !filterMru" class="w-full sm:w-auto px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 disabled:opacity-40 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition text-center">
                                ➕ Create Cycle Only
                            </button>
                            <button type="button" @click="launchBillingCycle('download_all')" :disabled="cycleInProgress || !filterMru" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1">
                                <span>⚡</span> Create & Download All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL 3: In-App Sequential PDF Viewer -->
            <div x-show="showPdfViewerModal" x-cloak class="fixed inset-0 z-50 overflow-hidden bg-slate-950/80 backdrop-blur-md flex flex-col p-2 sm:p-4">
                <div @click.outside="showPdfViewerModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-5xl h-full mx-auto flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <!-- Header -->
                    <div class="p-3.5 sm:px-6 sm:py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/80 flex flex-wrap items-center justify-between gap-3 shrink-0">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-cyan-300 font-black text-xs sm:text-sm flex items-center justify-center font-mono shrink-0" x-text="activePdfBill?.ca_number ? activePdfBill.ca_number.slice(-2) : '📄'"></div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                    <span class="font-bold text-slate-900 dark:text-white text-xs sm:text-sm truncate" x-text="activePdfBill?.consumer_name || 'Consumer Bill'"></span>
                                    <span class="font-mono text-[11px] sm:text-xs text-blue-600 dark:text-cyan-400 font-bold" x-text="'CA: ' + (activePdfBill?.ca_number || '—')"></span>
                                </div>
                                <div class="flex items-center gap-1.5 sm:gap-2 text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400 font-medium">
                                    <span x-text="'Period: ' + (activePdfBill?.bill_month_label || (activePdfBill?.billing_month + '/' + activePdfBill?.billing_year))"></span>
                                    <span>•</span>
                                    <span class="font-bold" :class="Number(activePdfBill?.total_amount) < 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-800 dark:text-slate-200'" x-text="formatCurrency(activePdfBill?.total_amount)"></span>
                                    <span>•</span>
                                    <span x-text="(activePdfBill?.units_consumed ?? 0) + ' kWh'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Controls -->
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <!-- Status Switcher in Modal -->
                            <div class="hidden sm:inline-flex items-center gap-1 bg-white dark:bg-slate-900 p-1 rounded-xl border border-slate-200 dark:border-slate-700">
                                <button @click="updateBillStatus(activePdfBill, 'submitted')" :class="activePdfBill?.review_status === 'submitted' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1 rounded-lg text-xs transition" title="Mark Submitted">✅</button>
                                <button @click="updateBillStatus(activePdfBill, 'critical')" :class="activePdfBill?.review_status === 'critical' ? 'bg-rose-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1 rounded-lg text-xs transition" title="Mark Critical">❌</button>
                                <button @click="updateBillStatus(activePdfBill, 'doubt')" :class="activePdfBill?.review_status === 'doubt' ? 'bg-amber-600 text-white font-bold' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'" class="px-2.5 py-1 rounded-lg text-xs transition" title="Mark Doubt">⚠️</button>
                            </div>

                            <!-- Sequential Navigation: Prev / Next Bill -->
                            <div class="inline-flex items-center bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-1">
                                <button @click="navigatePdfBill(-1)" :disabled="getPdfBillIndex() <= 0" class="px-2 sm:px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-200 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition flex items-center gap-1" title="Previous Bill">
                                    ⟨
                                </button>
                                <span class="px-1.5 sm:px-2 text-[11px] font-mono text-slate-400" x-text="(getPdfBillIndex() + 1) + '/' + items.length"></span>
                                <button @click="navigatePdfBill(1)" :disabled="getPdfBillIndex() >= items.length - 1" class="px-2 sm:px-2.5 py-1 text-xs font-bold text-slate-700 dark:text-slate-200 disabled:opacity-30 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition flex items-center gap-1" title="Next Bill">
                                    ⟩
                                </button>
                            </div>

                            <!-- Print & Re-download & Delete -->
                            <button @click="printPdfIframe()" class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition" title="Print PDF">
                                🖨️
                            </button>
                            <button @click="downloadSingleBill(activePdfBill)" :disabled="syncingSingle === activePdfBill?.ca_number" class="p-2 bg-blue-50 dark:bg-slate-800 hover:bg-blue-100 dark:hover:bg-slate-700 text-blue-600 dark:text-cyan-400 rounded-xl text-xs font-bold border border-blue-200 dark:border-slate-700 transition" title="Re-download / Refresh this Bill">
                                <span x-show="syncingSingle !== activePdfBill?.ca_number">⚡</span>
                                <svg x-show="syncingSingle === activePdfBill?.ca_number" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                            <button @click="deleteBillPdf(activePdfBill)" class="p-2 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800 transition" title="Delete PDF file from storage and reset status">
                                🗑️
                            </button>
                            <a :href="activePdfBill?.id ? ('/bills/pdf/' + activePdfBill.id) : '#'" target="_blank" class="p-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition" title="Open in New Tab">
                                ↗
                            </a>

                            <!-- Close -->
                            <button @click="showPdfViewerModal = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white rounded-xl">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Embedded PDF Iframe -->
                    <div class="flex-1 bg-slate-100 dark:bg-slate-950 relative overflow-hidden">
                        <template x-if="activePdfBill?.id">
                            <iframe id="pdfViewerIframe" :src="'/bills/pdf/' + activePdfBill.id" class="w-full h-full border-0"></iframe>
                        </template>
                    </div>
                </div>
            </div>

            <!-- MODAL 4: Quick Single CA Pull -->
            <div x-show="showQuickPullModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showQuickPullModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Quick Single CA Pull</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Download bill and sync consumer instantly</p>
                            </div>
                        </div>
                        <button @click="showQuickPullModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Enter CA Number</label>
                            <input type="text" x-model="quickPullCa" placeholder="e.g. 10230046961" @keyup.enter="executeQuickPull()" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <p class="text-[11px] text-slate-400 mt-1">Downloads the official PDF, parses consumer details, and registers the account automatically.</p>
                        </div>

                        <!-- Result -->
                        <div x-show="quickPullResult" class="p-3.5 rounded-2xl text-xs font-semibold" :class="quickPullResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'" x-text="quickPullResult?.message"></div>
                    </div>

                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <button type="button" @click="showQuickPullModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                            Close
                        </button>
                        <button type="button" @click="executeQuickPull()" :disabled="quickPullLoading || !quickPullCa" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1.5">
                            <span x-show="!quickPullLoading">⚡ Pull Bill</span>
                            <span x-show="quickPullLoading" class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Downloading...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL 5: Interactive Keyboard Shortcuts Customizer -->
            <div x-show="showShortcutsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!rebindingAction) showShortcutsModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">⌨️</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Keyboard Shortcuts & Combos</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Supports single keys & multi-key combinations (e.g. Ctrl+C)</p>
                            </div>
                        </div>
                        <button @click="showShortcutsModal = false" :disabled="rebindingAction !== null" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <!-- Live Rebinding Listening Banner inside Modal -->
                    <div x-show="rebindingAction" class="p-4 bg-brand-50 dark:bg-brand-950/90 border-b border-brand-200 dark:border-cyan-800 text-center shrink-0" x-cloak>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-brand-700 dark:text-cyan-300 flex items-center justify-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-500 animate-ping"></span>
                            Listening for Input
                        </div>
                        <div class="text-xs font-black text-slate-900 dark:text-white mt-0.5">
                            Assigning: <span class="text-brand-600 dark:text-cyan-400" x-text="shortcutLabels[rebindingAction] || rebindingAction"></span>
                        </div>
                        <div class="mt-1 text-xs font-mono font-bold text-brand-700 dark:text-cyan-300" x-text="rebindDisplay"></div>
                    </div>

                    <!-- Shortcut Action Rows -->
                    <div class="p-4 sm:p-6 space-y-3 overflow-y-auto">
                        <div class="p-3 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed flex items-start gap-2">
                            <span class="text-sm">💡</span>
                            <span><strong>Review Speed Tip:</strong> Single-key and multi-key combos are supported. Press <strong>Escape (Esc)</strong> anytime to exit input boxes. Press <strong>?</strong> to open this shortcuts sheet anytime.</span>
                        </div>

                        <template x-for="(label, actionKey) in shortcutLabels" :key="actionKey">
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800/80">
                                <div>
                                    <div class="text-xs font-bold text-slate-800 dark:text-slate-200" x-text="label"></div>
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5" x-text="'action: ' + actionKey"></div>
                                </div>
                                <div>
                                    <button type="button" 
                                             @click="startRebind(actionKey)" 
                                             :class="rebindingAction === actionKey ? 'bg-amber-500 text-white animate-pulse ring-2 ring-amber-400' : 'bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-100 hover:bg-slate-300 dark:hover:bg-slate-600 border border-slate-300 dark:border-slate-600'" 
                                             class="px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold transition min-w-[80px] text-center shadow-xs flex items-center justify-center">
                                        <template x-if="rebindingAction === actionKey">
                                            <span class="text-[10px] font-bold text-white">Press Key...</span>
                                        </template>
                                        <template x-if="rebindingAction !== actionKey">
                                            <span x-html="renderShortcutBadge(shortcuts[actionKey])"></span>
                                        </template>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <button type="button" @click="resetToDefaults()" class="text-xs text-rose-600 dark:text-rose-400 hover:underline font-bold text-left">
                            🔄 Reset Defaults
                        </button>
                        <div class="flex flex-col-reverse sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="showShortcutsModal = false" class="w-full sm:w-auto px-4 py-2.5 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-xl transition text-center">
                                Close
                            </button>
                            <button type="button" @click="saveCustomShortcuts()" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-md shadow-blue-500/20 transition text-center">
                                💾 Save Shortcuts
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function dashboardApp() {
            return {
                loading: true,
                items: [],
                pagination: {},
                viewMode: localStorage.getItem('dashboard_view_mode') || 'table',
                currentCardIndex: 0,
                touchStartX: 0,

                // Modals
                showCreateMruModal: false,
                showExistingMruPopup: false,
                existingMruData: null,
                newMruCode: '',
                newMruName: '',
                newMruIdentifier: '',
                isSubmittingMru: false,
                createMruError: null,
                showNewCycleModal: false,
                showPdfViewerModal: false,
                activePdfBill: null,
                showQuickPullModal: false,
                quickPullCa: '',
                quickPullLoading: false,
                quickPullResult: null,
                syncingSingle: null,
                syncingMissing: false,

                // Shortcuts State
                showShortcutsModal: false,
                rebindingAction: null,
                rebindDisplay: '',
                rebindSession: null,
                shortcuts: @json(Auth::user()->getShortcutMap()),
                shortcutLabels: @json(Auth::user()->getShortcutLabels()),
                cardDensity: '{{ session('pref_card_density', 'compact') }}',
                amountSize: '{{ session('pref_amount_size', 'standard') }}',
                showRemarkPresets: {{ json_encode(session('pref_remark_presets', false)) }},

                cycleMonth: {{ $selectedMonth }},
                cycleYear: {{ $selectedYear }},
                executingAction: 'download_all',
                cycleInProgress: false,
                cycleResult: null,

                // Toast with Undo
                toast: {
                    show: false,
                    message: '',
                    icon: '✅',
                    undoData: null,
                    timer: null
                },

                // Filters & Sorting (State Persistence & Auto-Validation)
                mruPeriodsMap: @json($mruPeriodsMap ?? []),
                filterMru: (function() {
                    const map = @json($mruPeriodsMap ?? []);
                    const stored = localStorage.getItem('dashboard_mru');
                    if (stored && map[String(stored)]) return String(stored);
                    return '{{ $selectedMruId }}';
                })(),
                availablePeriods: (function() {
                    const map = @json($mruPeriodsMap ?? []);
                    const stored = localStorage.getItem('dashboard_mru');
                    const mruKey = (stored && map[String(stored)]) ? String(stored) : '{{ $selectedMruId }}';
                    return map[mruKey] || [];
                })(),
                selectedPeriodKey: (function() {
                    const map = @json($mruPeriodsMap ?? []);
                    const storedMru = localStorage.getItem('dashboard_mru');
                    const mruKey = (storedMru && map[String(storedMru)]) ? String(storedMru) : '{{ $selectedMruId }}';
                    const list = map[mruKey] || [];
                    const storedPeriod = localStorage.getItem('dashboard_period');
                    if (storedPeriod && list.some(p => p.key === storedPeriod)) return storedPeriod;
                    if (list.length > 0) return list[0].key;
                    return '{{ $selectedMonth }}_{{ $selectedYear }}';
                })(),
                selectedMonth: {{ $selectedMonth }},
                selectedYear: {{ $selectedYear }},
                filterStatus: 'all',
                tagFilter: 'all',
                availableTags: @json($activeTags ?? []),
                defaultTag: '{{ $defaultTag ?? "OK" }}',
                searchQuery: '',
                statusSort: localStorage.getItem('dashboard_status_sort') || 'default',
                sortOption: localStorage.getItem('dashboard_sort_option') || 'ca_number_asc',
                sortCol: 'ca_number',
                sortAsc: true,

                // Dynamic Counts & Stats
                counts: {
                    all: {{ $totalPeriodBills ?? 0 }},
                    pending: {{ $statusCounts['pending'] ?? 0 }},
                    submitted: {{ $statusCounts['submitted'] ?? 0 }},
                    critical: {{ $statusCounts['critical'] ?? 0 }},
                    doubt: {{ $statusCounts['doubt'] ?? 0 }},
                    missing_pdf: {{ $statusCounts['missing_pdf'] ?? 0 }},
                    filtered_units: {{ $totalPeriodUnits ?? 0 }},
                    filtered_amount: {{ $totalPeriodAmount ?? 0 }},
                    total_consumers: {{ $totalConsumers ?? 0 }},
                },

                init() {
                    // Check URL params first to override storage if specified
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('mru_id')) {
                        this.filterMru = urlParams.get('mru_id');
                        localStorage.setItem('dashboard_mru', this.filterMru);
                    }

                    // Populate available periods for currently selected MRU
                    this.updateAvailablePeriods(false);

                    if (urlParams.has('month') && urlParams.has('year')) {
                        this.selectedMonth = parseInt(urlParams.get('month'));
                        this.selectedYear = parseInt(urlParams.get('year'));
                        this.selectedPeriodKey = `${this.selectedMonth}_${this.selectedYear}`;
                        localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                    }

                    this.cycleMonth = this.selectedMonth || new Date().getMonth() + 1;
                    this.cycleYear = this.selectedYear || new Date().getFullYear();

                    this.parseSortOption();
                    this.fetchData(1);
                },

                setViewMode(mode) {
                    this.viewMode = mode;
                    localStorage.setItem('dashboard_view_mode', mode);
                },

                updateAvailablePeriods(triggerFetch = true) {
                    const availableMruKeys = Object.keys(this.mruPeriodsMap);
                    if (availableMruKeys.length > 0 && !availableMruKeys.includes(String(this.filterMru))) {
                        this.filterMru = String('{{ $selectedMruId }}' || availableMruKeys[0]);
                        localStorage.setItem('dashboard_mru', this.filterMru);
                    }

                    const mruKey = String(this.filterMru || '');
                    this.availablePeriods = this.mruPeriodsMap[mruKey] || [];

                    const hasCurrent = this.availablePeriods.some(p => p.key === this.selectedPeriodKey);
                    if (!hasCurrent) {
                        if (this.availablePeriods.length > 0) {
                            const first = this.availablePeriods[0];
                            this.selectedPeriodKey = first.key;
                            this.selectedMonth = first.month;
                            this.selectedYear = first.year;
                        } else {
                            this.selectedPeriodKey = '';
                            this.selectedMonth = new Date().getMonth() + 1;
                            this.selectedYear = new Date().getFullYear();
                        }
                        localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                    } else if (this.selectedPeriodKey) {
                        const parts = this.selectedPeriodKey.split('_');
                        this.selectedMonth = parseInt(parts[0]);
                        this.selectedYear = parseInt(parts[1]);
                    }

                    this.cycleMonth = this.selectedMonth || new Date().getMonth() + 1;
                    this.cycleYear = this.selectedYear || new Date().getFullYear();

                    if (triggerFetch) {
                        this.fetchData(1);
                    }
                },

                onMruChange() {
                    localStorage.setItem('dashboard_mru', this.filterMru);
                    this.updateAvailablePeriods(true);
                },

                onPeriodChange() {
                    if (!this.selectedPeriodKey) return;
                    const parts = this.selectedPeriodKey.split('_');
                    this.selectedMonth = parseInt(parts[0]);
                    this.selectedYear = parseInt(parts[1]);
                    this.cycleMonth = this.selectedMonth;
                    this.cycleYear = this.selectedYear;
                    localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                    this.fetchData(1);
                },

                onStatusSortChange() {
                    localStorage.setItem('dashboard_status_sort', this.statusSort);
                    this.fetchData(1);
                },

                onSortOptionChange() {
                    localStorage.setItem('dashboard_sort_option', this.sortOption);
                    this.parseSortOption();
                    this.fetchData(1);
                },

                parseSortOption() {
                    const parts = this.sortOption.split('_');
                    const dir = parts.pop();
                    this.sortCol = parts.join('_');
                    this.sortAsc = (dir === 'asc');
                },

                fetchData(page = 1) {
                    this.loading = true;
                    const url = new URL('/dashboard/data', window.location.origin);
                    url.searchParams.append('page', page);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);
                    if (this.filterMru) url.searchParams.append('mru_id', this.filterMru);
                    if (this.filterStatus && this.filterStatus !== 'all') url.searchParams.append('filter', this.filterStatus);
                    if (this.tagFilter && this.tagFilter !== 'all') url.searchParams.append('tag_filter', this.tagFilter);
                    if (this.searchQuery) url.searchParams.append('search', this.searchQuery);
                    url.searchParams.append('status_sort', this.statusSort);
                    url.searchParams.append('sort_col', this.sortCol);
                    url.searchParams.append('sort_asc', this.sortAsc ? 'true' : 'false');

                    fetch(url)
                        .then(res => res.json())
                        .then(json => {
                            if (json.success) {
                                this.items = json.data.map(b => {
                                    b._lastSavedRemark = b.remark || '';
                                    return b;
                                });
                                this.pagination = json.pagination;
                                if (json.counts) this.counts = json.counts;
                                if (json.filtered_units !== undefined) this.counts.filtered_units = json.filtered_units;
                                if (json.filtered_amount !== undefined) this.counts.filtered_amount = json.filtered_amount;
                                if (json.available_periods) {
                                    this.availablePeriods = json.available_periods;
                                    const mruKey = String(this.filterMru || '');
                                    this.mruPeriodsMap[mruKey] = json.available_periods;
                                    if (this.availablePeriods.length > 0) {
                                        const hasCurrent = this.availablePeriods.some(p => p.key === this.selectedPeriodKey);
                                        if (!hasCurrent) {
                                            this.selectedPeriodKey = this.availablePeriods[0].key;
                                            this.selectedMonth = this.availablePeriods[0].month;
                                            this.selectedYear = this.availablePeriods[0].year;
                                            this.cycleMonth = this.selectedMonth;
                                            this.cycleYear = this.selectedYear;
                                        }
                                    }
                                }
                            }
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error(err);
                            this.loading = false;
                        });
                },

                // PDF Viewer Modal Methods
                openPdfModal(bill) {
                    this.activePdfBill = bill;
                    this.showPdfViewerModal = true;
                },

                getPdfBillIndex() {
                    if (!this.activePdfBill) return 0;
                    return this.items.findIndex(b => b.ca_number === this.activePdfBill.ca_number);
                },

                navigatePdfBill(direction) {
                    const curIdx = this.getPdfBillIndex();
                    const newIdx = curIdx + direction;
                    if (newIdx >= 0 && newIdx < this.items.length) {
                        this.activePdfBill = this.items[newIdx];
                    }
                },

                printPdfIframe() {
                    const iframe = document.getElementById('pdfViewerIframe');
                    if (iframe && iframe.contentWindow) {
                        iframe.contentWindow.print();
                    }
                },

                deleteBillPdf(bill) {
                    if (!bill || !bill.id) return;
                    if (!confirm(`Delete downloaded PDF for CA ${bill.ca_number} from storage and reset status to Pending?`)) return;
                    fetch('/bills/delete-pdf', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ id: bill.id })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.showToastNotification('🗑️', data.message);
                            bill.has_pdf = false;
                            bill.pdf_path = null;
                            bill.official_pdf_reading = null;
                            bill.pdf_sync_status = 'awaiting';
                            this.showPdfViewerModal = false;
                            this.fetchData(this.pagination.current_page || 1);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        this.showToastNotification('❌', 'Error deleting PDF.');
                    });
                },

                // Single CA Real-Time Download
                downloadSingleBill(bill, explicitCa = null) {
                    const ca = explicitCa || bill?.ca_number;
                    if (!ca) return;
                    this.syncingSingle = ca;

                    fetch('/bills/download-single', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ca_number: ca,
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.syncingSingle = null;
                        if (data.success && data.bill) {
                            const idx = this.items.findIndex(i => i.ca_number === ca);
                            if (idx !== -1) {
                                this.items[idx].has_pdf = true;
                                this.items[idx].id = data.bill.id;
                                this.items[idx].consumer_name = data.bill.consumer_name;
                                this.items[idx].total_amount = data.bill.total_amount;
                                this.items[idx].units_consumed = data.bill.units_consumed;
                                this.items[idx].current_reading = data.bill.current_reading;
                                this.items[idx].previous_reading = data.bill.previous_reading;
                                this.items[idx].meter_no = data.bill.meter_no;
                                this.items[idx].bill_month_label = data.bill.bill_month_label;
                            }
                            if (this.activePdfBill && this.activePdfBill.ca_number === ca) {
                                this.activePdfBill = { ...this.activePdfBill, ...data.bill, has_pdf: true };
                            }
                            this.showToastNotification('✅', `Bill for CA ${ca} downloaded successfully.`);
                            this.fetchData(this.pagination.current_page || 1);
                        } else {
                            this.showToastNotification('❌', data.message || `Failed to download CA ${ca}`);
                        }
                    })
                    .catch(err => {
                        this.syncingSingle = null;
                        this.showToastNotification('❌', `Download error for CA ${ca}`);
                    });
                },

                // Incremental Sync Missing Bills
                syncMissingBills() {
                    this.syncingMissing = true;

                    fetch('/bills/sync-missing', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.syncingMissing = false;
                        if (data.success) {
                            this.showToastNotification('⚡', data.message);
                            this.fetchData(1);
                        } else {
                            this.showToastNotification('❌', data.message || 'Sync failed.');
                        }
                    })
                    .catch(err => {
                        this.syncingMissing = false;
                        this.showToastNotification('❌', 'Error syncing missing bills.');
                    });
                },

                // Quick Pull CA Execution
                executeQuickPull() {
                    if (!this.quickPullCa) return;
                    this.quickPullLoading = true;
                    this.quickPullResult = null;

                    fetch('/bills/download-single', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ca_number: this.quickPullCa.trim(),
                            billing_month: this.selectedMonth,
                            billing_year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        this.quickPullLoading = false;
                        this.quickPullResult = data;
                        if (data.success) {
                            this.showToastNotification('✅', data.message);
                            this.fetchData(1);
                        }
                    })
                    .catch(err => {
                        this.quickPullLoading = false;
                        this.quickPullResult = { success: false, message: 'Server connection error.' };
                    });
                },

                // ✍️ Save Working Reading via AJAX with Invariant Checks
                saveWorkingReading(bill) {
                    if (!bill.id || bill.working_reading === undefined || bill.working_reading === null) return;
                    const prevNum = parseInt(bill.db_prev_reading) || 0;
                    const workNum = parseInt(bill.working_reading) || 0;
                    const pdfNum = parseInt(bill.official_pdf_reading);

                    bill.working_diff_units = Math.max(0, workNum - prevNum);

                    // Recompute live status
                    if (!isNaN(pdfNum) && pdfNum > 0) {
                        if (workNum > pdfNum) {
                            bill.pdf_sync_status = 'ahead';
                            bill.pdf_delta = workNum - pdfNum;
                        } else if (workNum === pdfNum) {
                            bill.pdf_sync_status = 'matched';
                            bill.pdf_delta = 0;
                        } else {
                            bill.pdf_sync_status = 'invalid_behind';
                            bill.pdf_delta = workNum - pdfNum;
                            this.showToastNotification('⚠️', `Warning: Working reading (${workNum}) is less than PDF reading (${pdfNum})!`);
                        }
                    }

                    fetch('/bills/update-working-reading', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            id: bill.id,
                            working_reading: String(bill.working_reading).trim()
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.showToastNotification('💾', 'Working reading saved: ' + bill.working_reading);
                        }
                    })
                    .catch(err => console.error(err));
                },

                // ⚡ Auto-Fill Working Reading with (Previous + Average) ensuring >= PDF Reading
                autoFillWorkingReading(bill) {
                    const prev = parseInt(bill.db_prev_reading) || 0;
                    const avg = parseInt(bill.smart_avg_units) || 50;
                    let target = prev + avg;

                    const pdfNum = parseInt(bill.official_pdf_reading);
                    if (!isNaN(pdfNum) && target < pdfNum) {
                        target = pdfNum; // Guaranteed never < PDF
                    }

                    bill.working_reading = String(target);
                    bill.is_projected = true;
                    this.saveWorkingReading(bill);
                },

                // ⚡ Bulk Auto-Project All Unfilled Readings
                bulkAutoProjectAll() {
                    if (!confirm(`Auto-project working readings (Previous + Avg) for all accounts in this cycle?`)) return;
                    fetch('/bills/bulk-project-readings', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            month: this.selectedMonth,
                            year: this.selectedYear,
                            mru_id: this.filterMru || null
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            this.showToastNotification('⚡', data.message);
                            this.fetchData(this.pagination.current_page || 1);
                        }
                    })
                    .catch(err => console.error(err));
                },

                launchBillingCycle(actionType = 'download_all') {
                    if (!this.filterMru) return;

                    this.executingAction = actionType;
                    this.cycleInProgress = true;
                    this.cycleResult = null;

                    fetch('/mrus/billing-cycle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mru_id: this.filterMru,
                            billing_month: this.cycleMonth,
                            billing_year: this.cycleYear,
                            action_type: actionType
                        })
                    })
                    .then(async res => {
                        const json = await res.json().catch(() => ({}));
                        if (!res.ok && !json.message) {
                            throw new Error('Server returned an error.');
                        }
                        return json;
                    })
                    .then(json => {
                        this.cycleResult = json;
                        if (json.success) {
                            const newKey = `${this.cycleMonth}_${this.cycleYear}`;
                            const mruKey = String(this.filterMru || '');
                            if (!this.mruPeriodsMap[mruKey]) this.mruPeriodsMap[mruKey] = [];
                            const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                            const newLabel = `${monthNames[this.cycleMonth - 1]}, ${this.cycleYear}`;
                            if (!this.mruPeriodsMap[mruKey].some(p => p.key === newKey)) {
                                this.mruPeriodsMap[mruKey].unshift({
                                    key: newKey,
                                    month: this.cycleMonth,
                                    year: this.cycleYear,
                                    label: newLabel
                                });
                            }
                            this.availablePeriods = this.mruPeriodsMap[mruKey];
                            this.selectedMonth = this.cycleMonth;
                            this.selectedYear = this.cycleYear;
                            this.selectedPeriodKey = newKey;
                            localStorage.setItem('dashboard_period', this.selectedPeriodKey);
                            setTimeout(() => {
                                this.showNewCycleModal = false;
                                this.cycleInProgress = false;
                                this.fetchData(1);
                            }, 800);
                        }
                    })
                    .catch(err => {
                        this.cycleResult = {
                            success: false,
                            message: err.message || 'An error occurred while executing billing cycle.'
                        };
                    })
                    .finally(() => {
                        this.cycleInProgress = false;
                    });
                },

                showToastNotification(icon, message, undoData = null) {
                    if (this.toast.timer) clearTimeout(this.toast.timer);
                    this.toast.icon = icon;
                    this.toast.message = message;
                    this.toast.undoData = undoData;
                    this.toast.show = true;

                    this.toast.timer = setTimeout(() => {
                        this.toast.show = false;
                    }, 5000);
                },

                updateBillStatus(bill, status) {
                    const prevStatus = bill.review_status || 'pending';
                    const newStatus = (prevStatus === status) ? 'pending' : status;

                    const statusLabels = {
                        submitted: 'Submitted',
                        critical: 'Critical',
                        doubt: 'Doubt',
                        pending: 'Pending'
                    };
                    const statusIcons = {
                        submitted: '✅',
                        critical: '❌',
                        doubt: '⚠️',
                        pending: '⏳'
                    };

                    // Optimistically update status
                    bill.review_status = newStatus;

                    // Update live global counts immediately
                    if (prevStatus !== newStatus) {
                        if (this.counts[prevStatus] !== undefined) {
                            this.counts[prevStatus] = Math.max(0, this.counts[prevStatus] - 1);
                        }
                        if (this.counts[newStatus] !== undefined) {
                            this.counts[newStatus] = (this.counts[newStatus] || 0) + 1;
                        }
                    }

                    // Handle active filter view removal
                    let removedIndex = -1;
                    const wasFilteredOut = (this.filterStatus !== 'all' && this.filterStatus !== newStatus);
                    if (wasFilteredOut) {
                        removedIndex = this.items.findIndex(x => x.id === bill.id);
                        if (removedIndex !== -1) {
                            this.items.splice(removedIndex, 1);
                            this.pagination.total = Math.max(0, (this.pagination.total || 0) - 1);
                            if (this.currentCardIndex >= this.items.length) {
                                this.currentCardIndex = Math.max(0, this.items.length - 1);
                            }
                        }
                    }

                    // Show toast with Undo option
                    this.showToastNotification(
                        statusIcons[newStatus],
                        `Marked CA ${bill.ca_number} as ${statusLabels[newStatus]}`,
                        {
                            kind: 'status',
                            bill: bill,
                            prevStatus: prevStatus,
                            newStatus: newStatus,
                            wasFilteredOut: wasFilteredOut,
                            removedIndex: removedIndex
                        }
                    );

                    // Send API request
                    fetch('/bills/status', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month,
                            billing_year: bill.billing_year,
                            status: newStatus
                        })
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (!json.success) {
                            // If failed, revert
                            this.undoLastAction();
                        }
                    })
                    .catch(err => {
                        console.error('Failed to update status:', err);
                    });

                    return wasFilteredOut;
                },

                onRemarkFocus(bill) {
                    if (bill._lastSavedRemark === undefined) {
                        bill._lastSavedRemark = bill.remark || '';
                    }
                },

                onRemarkBlur(bill) {
                    const current = (bill.remark || '').trim();
                    const previous = (bill._lastSavedRemark !== undefined ? bill._lastSavedRemark : '').trim();

                    // Auto-save ONLY if the text actually changed upon clicking outside (blur)
                    if (current !== previous) {
                        this.saveBillRemark(bill, false, previous);
                    }
                },

                saveBillRemark(bill, isManual = false, previousRemark = null) {
                    const prev = previousRemark !== null ? previousRemark : (bill._lastSavedRemark || '');
                    const current = bill.remark || '';
                    bill._lastSavedRemark = current;

                    fetch('/bills/remark', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month,
                            billing_year: bill.billing_year,
                            remark: current
                        })
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            this.showToastNotification(
                                '💬',
                                current.trim() ? `Saved note for CA ${bill.ca_number}` : `Cleared note for CA ${bill.ca_number}`,
                                {
                                    kind: 'remark',
                                    bill: bill,
                                    prevRemark: prev,
                                    newRemark: current
                                }
                            );
                        }
                    })
                    .catch(err => console.error(err));
                },

                clearBillRemark(bill) {
                    const prev = bill.remark || '';
                    if (!prev) return;
                    bill.remark = '';
                    this.saveBillRemark(bill, true, prev);
                },

                setBillTag(bill, tagCode) {
                    const prevTag = bill.tag || this.defaultTag || 'OK';
                    bill.tag = tagCode;
                    bill.display_tag = this.getTagDisplayLabel(tagCode);
                    bill.full_tag = this.getTagFullLabel(tagCode);

                    fetch('/bills/tag', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: bill.id,
                            ca_number: bill.ca_number,
                            billing_month: bill.billing_month || this.selectedMonth,
                            billing_year: bill.billing_year || this.selectedYear,
                            tag: tagCode
                        })
                    })
                    .then(res => res.json())
                    .then(json => {
                        if (json.success) {
                            this.showToastNotification(
                                '🏷️',
                                `Tag for CA ${bill.ca_number} set to ${json.display_tag}`,
                                {
                                    kind: 'tag',
                                    bill: bill,
                                    prevTag: prevTag,
                                    newTag: tagCode
                                }
                            );
                        }
                    })
                    .catch(err => {
                        console.error('Failed to save tag:', err);
                        bill.tag = prevTag;
                        bill.display_tag = this.getTagDisplayLabel(prevTag);
                        bill.full_tag = this.getTagFullLabel(prevTag);
                    });
                },

                getTagByCode(code) {
                    if (!code) code = this.defaultTag || 'OK';
                    return this.availableTags.find(t => t.code.toUpperCase() === String(code).toUpperCase()) || null;
                },

                getTagDisplayLabel(code) {
                    const t = this.getTagByCode(code);
                    return t ? (t.short_label || t.label) : (code || 'OK');
                },

                getTagFullLabel(code) {
                    const t = this.getTagByCode(code);
                    return t ? t.label : (code || 'OK');
                },

                getTagBadgeClass(code) {
                    const t = this.getTagByCode(code);
                    const color = t ? (t.color || 'emerald') : 'emerald';
                    return this.getActivePillClass(color);
                },

                getActivePillClass(color) {
                    switch (color) {
                        case 'emerald': return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40 ring-1 ring-emerald-500/30';
                        case 'blue': return 'bg-blue-500/20 text-blue-300 border-blue-500/40 ring-1 ring-blue-500/30';
                        case 'purple': return 'bg-purple-500/20 text-purple-300 border-purple-500/40 ring-1 ring-purple-500/30';
                        case 'amber': return 'bg-amber-500/20 text-amber-300 border-amber-500/40 ring-1 ring-amber-500/30';
                        case 'rose': return 'bg-rose-500/20 text-rose-300 border-rose-500/40 ring-1 ring-rose-500/30';
                        case 'cyan': return 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 ring-1 ring-cyan-500/30';
                        case 'indigo': return 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40 ring-1 ring-indigo-500/30';
                        default: return 'bg-slate-500/20 text-slate-300 border-slate-500/40 ring-1 ring-slate-500/30';
                    }
                },

                undoLastAction() {
                    if (!this.toast.undoData) return;
                    const data = this.toast.undoData;
                    this.toast.show = false;

                    if (data.kind === 'tag') {
                        // Revert tag locally
                        data.bill.tag = data.prevTag;
                        data.bill.display_tag = this.getTagDisplayLabel(data.prevTag);
                        data.bill.full_tag = this.getTagFullLabel(data.prevTag);

                        this.showToastNotification(
                            '↩',
                            `Restored tag to ${data.bill.display_tag} for CA ${data.bill.ca_number}`,
                            null
                        );

                        // Revert tag in DB
                        fetch('/bills/tag', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                id: data.bill.id,
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                tag: data.prevTag
                            })
                        }).catch(err => console.error(err));
                        return;
                    }

                    if (data.kind === 'remark') {
                        // Revert remark locally
                        data.bill.remark = data.prevRemark;
                        data.bill._lastSavedRemark = data.prevRemark;

                        this.showToastNotification(
                            '↩',
                            `Restored previous note for CA ${data.bill.ca_number}`,
                            null
                        );

                        // Revert remark in DB
                        fetch('/bills/remark', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                remark: data.prevRemark
                            })
                        }).catch(err => console.error(err));
                        return;
                    }

                    if (data.kind === 'status') {
                        const statusLabels = {
                            submitted: 'Submitted',
                            critical: 'Critical',
                            doubt: 'Doubt',
                            pending: 'Pending'
                        };

                        // Revert status locally
                        data.bill.review_status = data.prevStatus;

                        // Revert counts
                        if (this.counts[data.newStatus] !== undefined) {
                            this.counts[data.newStatus] = Math.max(0, this.counts[data.newStatus] - 1);
                        }
                        if (this.counts[data.prevStatus] !== undefined) {
                            this.counts[data.prevStatus] = (this.counts[data.prevStatus] || 0) + 1;
                        }

                        // Re-insert if it was filtered out
                        if (data.wasFilteredOut && (this.filterStatus === 'all' || this.filterStatus === data.prevStatus)) {
                            if (data.removedIndex >= 0 && data.removedIndex <= this.items.length) {
                                this.items.splice(data.removedIndex, 0, data.bill);
                            } else {
                                this.items.push(data.bill);
                            }
                            this.pagination.total = (this.pagination.total || 0) + 1;
                        }

                        this.showToastNotification(
                            '↩',
                            `Restored CA ${data.bill.ca_number} back to ${statusLabels[data.prevStatus]}`,
                            null
                        );

                        // API call to restore status in DB
                        fetch('/bills/status', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                ca_number: data.bill.ca_number,
                                billing_month: data.bill.billing_month,
                                billing_year: data.bill.billing_year,
                                status: data.prevStatus
                            })
                        }).catch(err => console.error(err));
                    }
                },

                nextCard() {
                    if (this.currentCardIndex < this.items.length - 1) {
                        this.currentCardIndex++;
                    } else if (this.pagination.current_page < this.pagination.last_page) {
                        this.fetchData(this.pagination.current_page + 1);
                    }
                },

                prevCard() {
                    if (this.currentCardIndex > 0) {
                        this.currentCardIndex--;
                    } else if (this.pagination.current_page > 1) {
                        this.fetchData(this.pagination.current_page - 1);
                    }
                },

                handleTouchEnd(e) {
                    const touchEndX = e.changedTouches[0].screenX;
                    const diff = this.touchStartX - touchEndX;
                    if (Math.abs(diff) > 40) {
                        if (diff > 0) {
                            this.nextCard(); // Swiped Left -> Next Card
                        } else {
                            this.prevCard(); // Swiped Right -> Prev Card
                        }
                    }
                },

                renderShortcutBadge(shortcut) {
                    if (window.KeyboardShortcuts) {
                        return window.KeyboardShortcuts.renderBadgesHtml(shortcut);
                    }
                    return shortcut || 'Unset';
                },

                openShortcutsModal() {
                    fetch('/user/shortcuts')
                        .then(r => r.json())
                        .then(data => {
                            if (data.shortcuts) this.shortcuts = data.shortcuts;
                            if (data.labels) this.shortcutLabels = data.labels;
                        })
                        .catch(err => console.error(err));
                    this.showShortcutsModal = true;
                    this.rebindingAction = null;
                    this.rebindSession = null;
                },

                startRebind(actionKey) {
                    if (this.rebindSession) {
                        this.rebindSession.cancel();
                    }

                    this.rebindingAction = actionKey;
                    this.rebindDisplay = 'Press any key or combo...';

                    if (window.KeyboardShortcuts) {
                        this.rebindSession = window.KeyboardShortcuts.startRebindSession({
                            onUpdate: (data) => {
                                this.rebindDisplay = data.display;
                            },
                            onComplete: (combo) => {
                                this.shortcuts[actionKey] = combo;
                                this.rebindingAction = null;
                                this.rebindSession = null;
                                this.showToastNotification('⌨️', `Key for ${this.shortcutLabels[actionKey] || actionKey} set to: ${combo}`, null);
                            },
                            onCancel: () => {
                                this.rebindingAction = null;
                                this.rebindSession = null;
                            }
                        });
                    }
                },

                cancelRebind() {
                    if (this.rebindSession) {
                        this.rebindSession.cancel();
                    }
                    this.rebindingAction = null;
                    this.rebindSession = null;
                },

                saveCustomShortcuts() {
                    fetch('/user/shortcuts', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ shortcuts: this.shortcuts })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (data.shortcuts) this.shortcuts = data.shortcuts;
                            this.showShortcutsModal = false;
                            this.showToastNotification('✅', 'Custom keyboard shortcuts saved!', null);
                        }
                    })
                    .catch(err => console.error(err));
                },

                resetToDefaults() {
                    fetch('/user/shortcuts/reset', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            if (data.shortcuts) this.shortcuts = data.shortcuts;
                            this.showToastNotification('🔄', 'Shortcuts reset to system defaults', null);
                        }
                    })
                    .catch(err => console.error(err));
                },

                onKeyNav(e) {
                    // Quick cheat-sheet overlay with '?' key (when not inside input)
                    if ((e.key === '?' || (e.shiftKey && e.key === '/')) && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                        if (!this.showCreateMruModal && !this.showExistingMruPopup && !this.showNewCycleModal && !this.showPdfViewerModal && !this.showQuickPullModal) {
                            e.preventDefault();
                            this.openShortcutsModal();
                            return;
                        }
                    }

                    // Do nothing if any modal is open or rebinding
                    if (this.showShortcutsModal || this.showCreateMruModal || this.showExistingMruPopup || this.showNewCycleModal || this.showPdfViewerModal || this.showQuickPullModal || this.rebindingAction) return;

                    // If typing inside an input/textarea
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                        const exitShortcut = this.shortcuts.exit_box || 'Escape';
                        const isExit = window.KeyboardShortcuts ? window.KeyboardShortcuts.matches(e, exitShortcut) : (e.key === 'Escape');

                        // 1. Exit / Blur input on Escape or configured Exit Key
                        if (isExit || e.key === 'Escape') {
                            e.preventDefault();
                            e.target.blur();
                            this.showToastNotification('↩️', 'Exited input field (Keyboard shortcuts active)', null);
                            return;
                        }

                        // 2. For Remark textarea: Ctrl+Enter or Cmd+Enter saves and exits
                        if (e.target.tagName === 'TEXTAREA' && (e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                            e.preventDefault();
                            const currentBill = this.items[this.currentCardIndex];
                            if (currentBill) {
                                this.saveBillRemark(currentBill, true);
                            }
                            e.target.blur();
                            this.showToastNotification('💾', 'Remark saved & exited', null);
                            return;
                        }

                        return;
                    }

                    if (this.viewMode !== 'card' || this.items.length === 0) return;

                    const currentBill = this.items[this.currentCardIndex];
                    if (!currentBill) return;

                    const ks = window.KeyboardShortcuts;

                    // 1. Copy CA Number
                    if (ks ? ks.matches(e, this.shortcuts.copy_ca) : (e.key === this.shortcuts.copy_ca)) {
                        e.preventDefault();
                        this.copyText(currentBill.ca_number);
                        return;
                    }

                    // 2. Submit / OK
                    if (ks ? ks.matches(e, this.shortcuts.submit_ok) : (e.key === this.shortcuts.submit_ok)) {
                        e.preventDefault();
                        const wasFilteredOut = this.updateBillStatus(currentBill, 'submitted');
                        if (!wasFilteredOut) {
                            this.nextCard();
                        }
                        return;
                    }

                    // 3. Mark Doubt
                    if (ks ? ks.matches(e, this.shortcuts.mark_doubt) : (e.key === this.shortcuts.mark_doubt)) {
                        e.preventDefault();
                        const wasFilteredOut = this.updateBillStatus(currentBill, 'doubt');
                        if (!wasFilteredOut && this.filterStatus === 'all') {
                            this.nextCard();
                        }
                        return;
                    }

                    // 4. Mark Critical
                    if (ks ? ks.matches(e, this.shortcuts.mark_critical) : (e.key === this.shortcuts.mark_critical)) {
                        e.preventDefault();
                        const wasFilteredOut = this.updateBillStatus(currentBill, 'critical');
                        if (!wasFilteredOut && this.filterStatus === 'all') {
                            this.nextCard();
                        }
                        return;
                    }

                    // 5. Next Card (Configured shortcut OR un-modified arrow keys)
                    const isNextArrow = !e.ctrlKey && !e.altKey && !e.metaKey && (e.key === 'ArrowRight' || e.key === 'ArrowDown');
                    if ((ks && ks.matches(e, this.shortcuts.next_card)) || isNextArrow) {
                        e.preventDefault();
                        this.nextCard();
                        return;
                    }

                    // 6. Previous Card (Configured shortcut OR un-modified arrow keys)
                    const isPrevArrow = !e.ctrlKey && !e.altKey && !e.metaKey && (e.key === 'ArrowLeft' || e.key === 'ArrowUp');
                    if ((ks && ks.matches(e, this.shortcuts.prev_card)) || isPrevArrow) {
                        e.preventDefault();
                        this.prevCard();
                        return;
                    }

                    // 7. Focus / Edit Working Reading
                    if (ks ? ks.matches(e, this.shortcuts.focus_reading) : (e.key === this.shortcuts.focus_reading)) {
                        e.preventDefault();
                        const el = document.getElementById('working-reading-input-' + currentBill.id);
                        if (el) {
                            el.focus();
                            el.select();
                        }
                        return;
                    }

                    // 8. Auto-Fill Working Reading (Prev + Avg)
                    if (ks ? ks.matches(e, this.shortcuts.auto_fill_reading) : (e.key === this.shortcuts.auto_fill_reading)) {
                        e.preventDefault();
                        this.autoFillWorkingReading(currentBill);
                        return;
                    }

                    // 9. Open / Focus Remark
                    if (ks ? ks.matches(e, this.shortcuts.open_remark) : (e.key === this.shortcuts.open_remark)) {
                        e.preventDefault();
                        const el = document.getElementById('remark-input-' + currentBill.id);
                        if (el) {
                            el.focus();
                            el.select();
                        }
                        return;
                    }
                },

                openCreateMruModal() {
                    this.newMruCode = '';
                    this.newMruName = '';
                    this.newMruIdentifier = '';
                    this.createMruError = null;
                    this.showCreateMruModal = true;
                },

                submitCreateMru() {
                    if (!this.newMruCode.trim() || !this.newMruName.trim()) return;

                    this.isSubmittingMru = true;
                    this.createMruError = null;

                    fetch('/mrus', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            code: this.newMruCode,
                            name: this.newMruName,
                            full_identifier: this.newMruIdentifier
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || 'Server error occurred');
                        }
                        return data;
                    })
                    .then(data => {
                        if (data.already_exists) {
                            this.showCreateMruModal = false;
                            this.existingMruData = data.mru;
                            this.showExistingMruPopup = true;
                        } else if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    })
                    .catch(err => {
                        this.createMruError = err.message || 'Failed to process MRU workspace.';
                    })
                    .finally(() => {
                        this.isSubmittingMru = false;
                    });
                },

                exportCsv() {
                    const url = new URL('/bills/export-csv', window.location.origin);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);
                    if (this.filterMru) url.searchParams.append('mru_id', this.filterMru);
                    if (this.filterStatus && this.filterStatus !== 'all') url.searchParams.append('filter', this.filterStatus);
                    if (this.searchQuery) url.searchParams.append('search', this.searchQuery);

                    window.location.href = url.toString();
                },

                exportZip() {
                    const url = new URL('/bills/export-zip', window.location.origin);
                    url.searchParams.append('month', this.selectedMonth);
                    url.searchParams.append('year', this.selectedYear);
                    if (this.filterMru) url.searchParams.append('mru_id', this.filterMru);
                    if (this.filterStatus && this.filterStatus !== 'all') url.searchParams.append('filter', this.filterStatus);
                    if (this.searchQuery) url.searchParams.append('search', this.searchQuery);

                    window.location.href = url.toString();
                },

                copyText(text) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.showToastNotification('📋', `Copied CA: ${text}`, null);
                    });
                },

                formatNumber(val) {
                    if (!val) return '0';
                    return Number(val).toLocaleString();
                },

                formatCurrency(val) {
                    if (val === null || val === undefined || val === '' || isNaN(Number(val))) return 'N/A';
                    const num = Number(val);
                    if (num < 0) {
                        return '-₹' + Math.abs(num).toFixed(2);
                    }
                    return '₹' + num.toFixed(2);
                }
            };
        }
    </script>
</x-app-layout>
