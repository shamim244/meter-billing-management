{{-- Manifest Inspection Modal --}}
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="closeModal()" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-800">
            <div class="flex items-center gap-2">
                <span class="text-xl">🔍</span>
                <h3 class="text-base font-bold text-white">Backup Manifest & Metadata</h3>
            </div>
            <button type="button" @click="closeModal()" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <div x-show="loadingModal" class="py-12 text-center text-slate-400">
            <div class="animate-spin text-2xl mb-2">⚡</div>
            <div class="text-xs">Loading manifest inspection...</div>
        </div>

        <div x-show="!loadingModal && manifestData" class="space-y-4 text-xs font-mono">
            <div class="grid grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-950 border border-slate-800 text-slate-300">
                <div><span class="text-slate-500">Backup Code:</span> <span class="text-white font-bold" x-text="manifestData?.backup_code"></span></div>
                <div><span class="text-slate-500">Type:</span> <span class="text-indigo-400 font-bold" x-text="manifestData?.type"></span></div>
                <div><span class="text-slate-500">Archive Size:</span> <span class="text-emerald-400 font-bold" x-text="manifestData?.size"></span></div>
                <div><span class="text-slate-500">Execution Time:</span> <span class="text-white" x-text="manifestData?.duration_seconds + 's'"></span></div>
                <div class="col-span-2"><span class="text-slate-500">SHA-256 Hash:</span> <span class="text-cyan-300 break-all select-all" x-text="manifestData?.sha256_hash"></span></div>
            </div>

            <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Detailed Component Metadata:</span>
                <pre class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-slate-300 text-[11px] overflow-x-auto max-h-60" x-text="JSON.stringify(manifestData?.meta, null, 2)"></pre>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end">
            <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition">
                Close Inspector
            </button>
        </div>

    </div>
</div>
