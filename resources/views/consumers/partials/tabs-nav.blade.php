{{-- Consumer Hub Tab Navigation Bar --}}
<div class="border-b border-slate-200 dark:border-slate-800">
    <nav class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold" aria-label="Tabs">
        <button type="button" @click="activeTab = 'overview'" 
                :class="activeTab === 'overview' ? 'border-brand-600 text-brand-600 dark:border-cyan-400 dark:text-cyan-400 bg-brand-50/50 dark:bg-brand-950/30' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="px-3.5 py-2 rounded-xl border transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <span>📊</span>
            <span>Profile & Specs</span>
        </button>

        <button type="button" @click="activeTab = 'gap'" 
                :class="activeTab === 'gap' ? 'border-brand-600 text-brand-600 dark:border-cyan-400 dark:text-cyan-400 bg-brand-50/50 dark:bg-brand-950/30' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="px-3.5 py-2 rounded-xl border transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <span>⚖️</span>
            <span>Reading Gap & Visits</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300" x-text="inspectionsList.length"></span>
        </button>

        <button type="button" @click="activeTab = 'fielddesk'" 
                :class="activeTab === 'fielddesk' ? 'border-brand-600 text-brand-600 dark:border-cyan-400 dark:text-cyan-400 bg-brand-50/50 dark:bg-brand-950/30' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="px-3.5 py-2 rounded-xl border transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <span>📋</span>
            <span>FieldDesk Actions</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $fieldDeskActions->count() }}</span>
        </button>

        <button type="button" @click="activeTab = 'bills'" 
                :class="activeTab === 'bills' ? 'border-brand-600 text-brand-600 dark:border-cyan-400 dark:text-cyan-400 bg-brand-50/50 dark:bg-brand-950/30' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="px-3.5 py-2 rounded-xl border transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <span>📑</span>
            <span>Bill Ledger</span>
            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300">{{ $stats['total_bills'] }}</span>
        </button>

        <button type="button" @click="activeTab = 'matrix'" 
                :class="activeTab === 'matrix' ? 'border-brand-600 text-brand-600 dark:border-cyan-400 dark:text-cyan-400 bg-brand-50/50 dark:bg-brand-950/30' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                class="px-3.5 py-2 rounded-xl border transition flex items-center gap-1.5 whitespace-nowrap cursor-pointer">
            <span>📈</span>
            <span>Monthly Matrix</span>
        </button>
    </nav>
</div>
