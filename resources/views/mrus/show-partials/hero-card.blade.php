<!-- MRU Master Hero Card -->
<div class="bg-white dark:bg-slate-900/90 backdrop-blur-md p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div>
            <div class="flex items-center gap-2.5 flex-wrap mb-2">
                <span class="px-3 py-1 rounded-xl text-xs font-mono font-black bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 tracking-wider">
                    {{ $mru->code }}
                </span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $mru->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $mru->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                    {{ $mru->status }}
                </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                {{ $mru->name }}
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">
                {{ $mru->full_identifier ?: "Permanent Consumer Master Workspace" }}
            </p>
        </div>

        <!-- Hero Quick Stats & Settings -->
        <div class="flex flex-wrap items-center gap-4 lg:gap-6 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 pt-4 lg:pt-0 lg:pl-8">
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Master Consumers</span>
                <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">
                    {{ number_format($consumers->total()) }}
                </div>
            </div>
            <div>
                <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Billing Sessions</span>
                <div class="text-xl sm:text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono mt-0.5">
                    {{ $sessions->count() }}
                </div>
            </div>
            <div class="flex items-center gap-2 pt-1 sm:pt-0">
                @if($mru->status === 'active')
                    <form method="POST" action="{{ route('mrus.lock', $mru) }}" onsubmit="return confirm('Are you sure you want to lock this MRU? Locking frees up subscription quota so you can downgrade or create other MRUs. You can unlock it anytime.');">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-700 dark:text-amber-300 rounded-xl text-xs font-bold border border-amber-200 dark:border-amber-800/60 transition flex items-center gap-1" title="Lock MRU to free up quota">
                            <span>🔒</span>
                            <span>Lock MRU</span>
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('mrus.unlock', $mru) }}" onsubmit="return confirm('Unlock this MRU?');">
                        @csrf
                        <input type="hidden" name="pay_overage" value="1">
                        <button type="submit" class="px-3.5 py-2 bg-emerald-50 dark:bg-emerald-950/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 rounded-xl text-xs font-bold border border-emerald-200 dark:border-emerald-800/60 transition flex items-center gap-1" title="Unlock MRU">
                            <span>🔓</span>
                            <span>Unlock MRU</span>
                        </button>
                    </form>
                @endif
                <button @click="showEditMruModal = true" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition" title="Edit MRU Name or Code">
                    ✏️ Edit
                </button>
                <button @click="showDeleteMruModal = true" class="px-3.5 py-2 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800/60 transition flex items-center gap-1" title="Delete MRU Workspace">
                    <span>🗑️</span>
                    <span>Delete MRU</span>
                </button>
            </div>
        </div>
    </div>
</div>
