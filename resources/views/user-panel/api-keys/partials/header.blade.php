<!-- Header Hero & Action Banner -->
<div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div class="flex items-center gap-5">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-3xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-brand-500/20 font-mono shrink-0">
                🔑
            </div>
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                        API Keys & Integrations
                    </h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-cyan-100 dark:bg-cyan-950/70 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/80">
                        REST API v1
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl">
                    Generate and manage secret bearer keys for your Python ADB field scripts, Android/Flutter readers, and external automation systems.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('docs.api') }}" 
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-sm">
                <span>📖</span>
                <span>API Docs & Console ↗</span>
            </a>

            <button @click="createModalOpen = true" 
                    type="button"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-cyan-500 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all duration-200 transform active:scale-95 cursor-pointer">
                <span class="text-base leading-none">＋</span>
                <span>Generate New Key</span>
            </button>
        </div>
    </div>
</div>
