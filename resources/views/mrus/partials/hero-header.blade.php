<!-- Hero Header & Stats Banner -->
<div class="bg-white dark:bg-slate-900/90 backdrop-blur-md rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800/60 mb-2">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                Workspace Management Hub
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                MRU Permanent Workspaces
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-2xl leading-relaxed">
                Organize consumers by Meter Reading Units (MRU), maintain master consumer lists, and manage isolated monthly billing cycles.
            </p>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
            <button @click="openCycleModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 hover:shadow-blue-500/30 transition active:scale-[0.98]">
                <span>⚡</span> New Billing Cycle
            </button>
            <button @click="openCreateModal()" class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition active:scale-[0.98]">
                <span>+</span> Create MRU
            </button>
        </div>
    </div>

    <!-- Live Overview Stats Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
        <div class="bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Workspaces</span>
            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                {{ number_format($mrus->count()) }}
            </div>
        </div>
        <div class="bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Master Consumers</span>
            <div class="text-xl sm:text-2xl font-black text-blue-600 dark:text-cyan-400 mt-1 font-mono">
                {{ number_format($mrus->sum('consumer_accounts_count')) }}
            </div>
        </div>
        <div class="col-span-2 sm:col-span-1 bg-slate-50/80 dark:bg-slate-800/50 p-4 rounded-2xl border border-slate-100 dark:border-slate-800/80">
            <span class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Bills Recorded</span>
            <div class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                {{ number_format($mrus->sum('bill_records_count')) }}
            </div>
        </div>
    </div>
</div>
