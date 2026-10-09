{{-- Filter Controls, Search Bar, Sorting & View Mode Switcher --}}
<div class="space-y-4">
    <!-- 1. Status Filter Pills Container -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">Review:</span>
            <button @click="filterStatus = 'all'; fetchData(1)" :class="filterStatus === 'all' ? 'bg-slate-900 dark:bg-blue-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
                📋 All (<span x-text="counts.all ?? 0"></span>)
            </button>
            <button @click="filterStatus = 'pending'; fetchData(1)" :class="filterStatus === 'pending' ? 'bg-slate-700 dark:bg-slate-600 text-white shadow-sm font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
                ⏳ Pending (<span x-text="counts.pending ?? 0"></span>)
            </button>
            <button @click="filterStatus = 'submitted'; fetchData(1)" :class="filterStatus === 'submitted' ? 'bg-emerald-600 text-white shadow-sm font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
                ✅ Submitted (<span x-text="counts.submitted ?? 0"></span>)
            </button>
            <button @click="filterStatus = 'critical'; fetchData(1)" :class="filterStatus === 'critical' ? 'bg-rose-600 text-white shadow-sm font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
                ❌ Critical (<span x-text="counts.critical ?? 0"></span>)
            </button>
            <button @click="filterStatus = 'doubt'; fetchData(1)" :class="filterStatus === 'doubt' ? 'bg-amber-600 text-white shadow-sm font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-3.5 py-1.5 rounded-2xl text-xs font-semibold transition cursor-pointer">
                ⚠️ Doubt (<span x-text="counts.doubt ?? 0"></span>)
            </button>
        </div>

        <!-- 1b. Basis Filter Pills (OK, LK, MD, PL, RN) -->
        <div class="flex flex-wrap items-center gap-2 pt-2.5 border-t border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">⚡ Basis:</span>
            <button @click="setBasisFilter('all')" :class="basisFilter === 'all' ? 'bg-blue-600 text-white shadow-xs font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition cursor-pointer">
                All Basis
            </button>
            <button @click="setBasisFilter('OK')" :class="basisFilter === 'OK' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
                <span>🟢 OK (Normal)</span>
                <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_ok !== undefined" x-text="'(' + (counts.basis_ok ?? 0) + ')'"></span>
            </button>
            <button @click="setBasisFilter('LK')" :class="basisFilter === 'LK' ? 'bg-amber-600 text-white shadow-xs font-bold' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
                <span>🟡 LK (Locked)</span>
                <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_lk !== undefined" x-text="'(' + (counts.basis_lk ?? 0) + ')'"></span>
            </button>
            <button @click="setBasisFilter('MD')" :class="basisFilter === 'MD' ? 'bg-rose-600 text-white shadow-xs font-bold' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
                <span>🟠 MD (Defective)</span>
                <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_md !== undefined" x-text="'(' + (counts.basis_md ?? 0) + ')'"></span>
            </button>
            <button @click="setBasisFilter('PL')" :class="basisFilter === 'PL' ? 'bg-indigo-600 text-white shadow-xs font-bold' : 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
                <span>🔵 PL</span>
                <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_pl !== undefined" x-text="'(' + (counts.basis_pl ?? 0) + ')'"></span>
            </button>
            <button @click="setBasisFilter('RN')" :class="basisFilter === 'RN' ? 'bg-purple-600 text-white shadow-xs font-bold' : 'bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900/50'" class="px-3 py-1.5 rounded-xl text-xs font-semibold transition flex items-center gap-1 cursor-pointer">
                <span>⚪ RN</span>
                <span class="text-[10px] opacity-75 font-mono" x-show="counts.basis_rn !== undefined" x-text="'(' + (counts.basis_rn ?? 0) + ')'"></span>
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
            <div class="w-full sm:w-56">
                <select x-model="statusSort" @change="onStatusSortChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                    <option value="default">Priority: Normal (No Grouping)</option>
                    <option value="pdcs">Priority: P-D-C-S</option>
                    <option value="dcps">Priority: D-C-P-S</option>
                    <option value="cdps">Priority: C-D-P-S</option>
                    <option value="spdc">Priority: S-P-D-C</option>
                </select>
            </div>

            <!-- Sort By Field -->
            <div class="w-full sm:w-64">
                <select x-model="sortOption" @change="onSortOptionChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                    <optgroup label="Account & Consumer">
                        <option value="ca_number_asc">Sort: CA Number (0-9 Low-High)</option>
                        <option value="ca_number_desc">Sort: CA Number (9-0 High-Low)</option>
                        <option value="consumer_name_asc">Sort: Consumer Name (A-Z)</option>
                        <option value="consumer_name_desc">Sort: Consumer Name (Z-A)</option>
                        <option value="meter_no_asc">Sort: Meter No (A-Z)</option>
                        <option value="meter_no_desc">Sort: Meter No (Z-A)</option>
                    </optgroup>
                    <optgroup label="Readings & Units">
                        <option value="working_reading_asc">Sort: Working Reading (Low-High)</option>
                        <option value="working_reading_desc">Sort: Working Reading (High-Low)</option>
                        <option value="previous_reading_asc">Sort: Previous Reading (Low-High)</option>
                        <option value="previous_reading_desc">Sort: Previous Reading (High-Low)</option>
                        <option value="current_reading_asc">Sort: PDF Reading (Low-High)</option>
                        <option value="current_reading_desc">Sort: PDF Reading (High-Low)</option>
                        <option value="units_asc">Sort: Units (Low to High)</option>
                        <option value="units_desc">Sort: Units (High to Low)</option>
                        <option value="amount_asc">Sort: Amount (Low to High)</option>
                        <option value="amount_desc">Sort: Amount (High to Low)</option>
                    </optgroup>
                    <optgroup label="Billing Basis & Status">
                        <option value="billing_basis_asc">Sort: Basis (A-Z: LK, MD, OK, PL)</option>
                        <option value="billing_basis_desc">Sort: Basis (Z-A: PL, OK, MD, LK)</option>
                        <option value="basis_priority_asc">Sort: Basis Priority (OK → LK → MD → PL)</option>
                        <option value="basis_priority_desc">Sort: Basis Priority (MD → LK → PL → OK)</option>
                        <option value="review_status_asc">Sort: Status (Pending → Doubt → Critical → Submitted)</option>
                        <option value="review_status_desc">Sort: Status (Submitted → Critical → Doubt → Pending)</option>
                        <option value="bill_month_asc">Sort: Bill Month (A-Z)</option>
                        <option value="bill_month_desc">Sort: Bill Month (Z-A)</option>
                    </optgroup>
                </select>
            </div>

            <!-- Basis Filter Dropdown -->
            <div class="w-full sm:w-48">
                <select x-model="basisFilter" @change="onBasisFilterChange()" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                    <option value="all">⚡ All Basis</option>
                    <option value="OK">🟢 Basis: OK (Normal)</option>
                    <option value="LK">🟡 Basis: LK (Locked)</option>
                    <option value="MD">🟠 Basis: MD (Defective)</option>
                    <option value="PL">🔵 Basis: PL (Power Line)</option>
                    <option value="RN">⚪ Basis: RN (Reading N/A)</option>
                </select>
            </div>

            <!-- Tag Filter -->
            <div class="w-full sm:w-48">
                <select x-model="tagFilter" @change="fetchData(1)" class="w-full text-xs font-medium border-slate-300 dark:border-slate-700 rounded-2xl bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-white py-2.5 px-3.5 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                    <option value="all">🏷️ All Tags</option>
                    <template x-for="t in availableTags" :key="t.code">
                        <option :value="t.code" x-text="'🏷️ ' + (t.short_label || t.label)"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Right: Bulk Mobile & Table / Card View Mode Switcher -->
        <div class="flex items-center gap-2 self-start md:self-auto flex-wrap">
            <button type="button" 
                    @click="openBulkMobileModal()" 
                    class="px-3 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-2xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs cursor-pointer active:scale-95" 
                    title="Bulk Update Consumer Mobile Numbers">
                <span>📱</span>
                <span class="hidden sm:inline">Bulk Mobiles</span>
            </button>

            <div class="inline-flex p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700">
                <button @click="setViewMode('table')" :class="viewMode === 'table' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    Table View
                </button>
                <button @click="setViewMode('card')" :class="viewMode === 'card' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-sm font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white'" class="px-4 py-2 rounded-xl text-xs transition flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    Cards View
                </button>
            </div>
        </div>
    </div>
</div>
