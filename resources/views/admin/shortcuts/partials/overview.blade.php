{{-- Top Overview & Info Card --}}
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
    <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                    Global Configuration
                </span>
                <span class="text-xs text-slate-500">•</span>
                <span class="text-xs text-slate-400 font-medium">NBPDCL Billing Engine</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                <span>⌨️</span> Platform Default Keybindings
            </h1>
            <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                These keyboard shortcuts serve as the platform baseline for all operators reviewing consumer cards on the Dashboard. Single keys (<kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">C</kbd>, <kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">R</kbd>) and multi-key combos (<kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">Ctrl+C</kbd>, <kbd class="font-mono text-[10px] px-1 bg-slate-800 rounded">Shift+M</kbd>) are fully supported.
            </p>
        </div>

        <!-- Adoption Stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Users</div>
                <div class="text-xl font-black text-white font-mono mt-0.5">{{ $stats['total_users'] }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">On Defaults</div>
                <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ $stats['default_users'] }}</div>
            </div>
            <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Customized</div>
                <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ $stats['customized_users'] }}</div>
            </div>
        </div>
    </div>
</div>
