{{-- On-Demand Backup Action Console --}}
<div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-5 border-b border-slate-800">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>⚡ On-Demand Backup Generators</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Choose your target snapshot level. Database dumps are non-blocking with zero table locking.</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
            <span>Disaster Recovery Engine Ready</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-6">
        
        {{-- Option 1: Database Only --}}
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-indigo-500/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-indigo-400">Lightweight & Fast</span>
                    <span class="text-lg">🗄️</span>
                </div>
                <h3 class="text-sm font-bold text-white">Database Snapshot (.sql.gz)</h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Transaction-safe dump of all users, MRUs, consumers, reading ledgers, and wallet transactions.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.backups.store') }}" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="db_only">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                    <span>⚡ Backup Database (~5s)</span>
                </button>
            </form>
        </div>

        {{-- Option 2: PDF Storage Only --}}
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-cyan-500/30 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-cyan-400">Media & Bills</span>
                    <span class="text-lg">📑</span>
                </div>
                <h3 class="text-sm font-bold text-white">PDF Bill Storage (.zip)</h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Chunked ZIP archive of all official BSPHCL/NBPDCL consumer bill PDF files.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.backups.store') }}" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="storage_only">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-slate-950 font-black text-xs shadow-md shadow-cyan-600/30 transition flex items-center justify-center gap-2">
                    <span>📦 Backup PDF Storage</span>
                </button>
            </form>
        </div>

        {{-- Option 3: Full System Snapshot --}}
        <div class="p-5 rounded-2xl bg-slate-950/80 border border-emerald-500/30 flex flex-col justify-between bg-gradient-to-b from-slate-900 to-emerald-950/20">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Complete Disaster Recovery</span>
                    <span class="text-lg">🚀</span>
                </div>
                <h3 class="text-sm font-bold text-white">Full System Archive (.zip)</h3>
                <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                    Bundled database SQL + PDF storage + system manifest.json. Restores entire SaaS in 1 step.
                </p>
            </div>
            <form method="POST" action="{{ route('admin.backups.store') }}" class="mt-4">
                @csrf
                <input type="hidden" name="type" value="full">
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-emerald-500 to-cyan-500 hover:from-emerald-400 hover:to-cyan-400 text-slate-950 font-black text-xs shadow-md shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                    <span>🚀 Full System Snapshot</span>
                </button>
            </form>
        </div>

    </div>
</div>
