<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Column 1: Export Package -->
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-4">
                <span class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold">📦</span>
                <div>
                    <h2 class="text-base font-black text-white">Export Migration Package</h2>
                    <p class="text-xs text-slate-400">Generate a universal bundle to move to a new cloud server</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-2 text-xs text-slate-300 mb-6">
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Database Dump:</span>
                    <span class="font-bold text-white">Full MySQL Atomic Snapshot (.sql.gz)</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Persistent Media:</span>
                    <span class="font-bold text-white">All Bill PDFs & Attachments (.zip)</span>
                </div>
                <div class="flex items-center justify-between py-1 border-b border-slate-800">
                    <span class="text-slate-400">Verification Audit:</span>
                    <span class="font-bold text-indigo-400">SHA-256 Hashes & Exact Row Manifest</span>
                </div>
                <div class="flex items-center justify-between py-1">
                    <span class="text-slate-400">Compatibility:</span>
                    <span class="font-bold text-emerald-400">Universal (Shared Hosting, Docker, VPS)</span>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.server_migration.export') }}" method="POST" class="space-y-4">
            @csrf
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <input type="checkbox" id="skip_storage_export" name="skip_storage" value="1" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <label for="skip_storage_export">Skip bill PDFs / storage (Database only export for rapid testing)</label>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-black shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-2">
                <span>📥</span>
                <span>Generate & Download Migration Package</span>
            </button>
        </form>
    </div>

    <!-- Column 2: Import Package -->
    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
        <div>
            <div class="flex items-center gap-3 mb-4">
                <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold">📥</span>
                <div>
                    <h2 class="text-base font-black text-white">Import & Rehydrate Server</h2>
                    <p class="text-xs text-slate-400">Restore a bundle and execute post-flight verification audit</p>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-amber-950/20 border border-amber-800/40 text-xs text-amber-200/90 mb-6 space-y-2">
                <p class="font-bold flex items-center gap-1.5 text-amber-300">
                    <span>⚠️</span> Caution: Server Overwrite Protection
                </p>
                <p>
                    Restoring an archive replaces current tables with the imported snapshot and synchronizes all bill files. The post-flight auditor verifies that 100% of rows match the manifest before completing.
                </p>
            </div>
        </div>

        <form action="{{ route('admin.server_migration.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4" onsubmit="return confirm('Restore this migration bundle? This will import the database and media assets into this server.');">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-2">Select Migration Package (.zip)</label>
                <input type="file" name="bundle" accept=".zip" required class="block w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-slate-900 file:text-indigo-400 hover:file:bg-slate-800 cursor-pointer border border-slate-800 rounded-xl bg-slate-900/60 p-1">
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-400">
                <input type="checkbox" id="skip_storage_import" name="skip_storage" value="1" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <label for="skip_storage_import">Database only (do not unpack storage files)</label>
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                <span>⚡</span>
                <span>Restore & Execute Verification Audit</span>
            </button>
        </form>
    </div>
</div>
