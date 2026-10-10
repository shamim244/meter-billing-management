<!-- TAB 1: Clean PDF Storage (Disk Optimizer) -->
<div x-show="cleanupTab === 'pdfs'" class="space-y-4">
    <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 flex items-center justify-between">
        <div>
            <span class="text-[11px] text-slate-400 font-semibold block">Current Disk Footprint</span>
            <span class="text-sm font-black text-purple-400 font-mono">{{ $storageMetrics['used_mb'] }} MB</span>
            <span class="text-xs text-slate-400">across {{ $user->getPdfCount() }} PDF files</span>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            🛡️ DB Records Preserved
        </span>
    </div>

    <div class="p-3 rounded-xl bg-purple-950/30 border border-purple-500/20 text-xs text-purple-200 leading-relaxed">
        💡 <strong>Disk Space Optimizer:</strong> Deletes physical <code>.pdf</code> files from the server storage disk. All consumer readings, units, amounts, remarks, and review tags in the database remain <strong>100% safe and intact</strong>.
    </div>

    <form method="POST" action="{{ route('admin.users.clean_pdfs', $user) }}" class="space-y-4">
        @csrf
        <div class="space-y-2">
            <label class="block text-xs font-bold text-slate-300">Select PDF Cleanup Scope:</label>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                <label class="p-3 rounded-xl border cursor-pointer transition flex items-start gap-2.5" :class="pdfScope === 'all' ? 'bg-purple-600/15 border-purple-500/50 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white'">
                    <input type="radio" name="scope" value="all" x-model="pdfScope" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                    <div>
                        <span class="font-bold text-white block">⚡ All Stored PDFs</span>
                        <span class="text-[11px] text-slate-400">Full disk reset (frees 100% disk space)</span>
                    </div>
                </label>

                <label class="p-3 rounded-xl border cursor-pointer transition flex items-start gap-2.5" :class="pdfScope === 'older_than_30' ? 'bg-purple-600/15 border-purple-500/50 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white'">
                    <input type="radio" name="scope" value="older_than_30" x-model="pdfScope" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                    <div>
                        <span class="font-bold text-white block">⏳ Older than 30 Days</span>
                        <span class="text-[11px] text-slate-400">Prune older billing downloads</span>
                    </div>
                </label>

                <label class="p-3 rounded-xl border cursor-pointer transition flex items-start gap-2.5" :class="pdfScope === 'older_than_60' ? 'bg-purple-600/15 border-purple-500/50 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white'">
                    <input type="radio" name="scope" value="older_than_60" x-model="pdfScope" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                    <div>
                        <span class="font-bold text-white block">⏳ Older than 60 Days</span>
                        <span class="text-[11px] text-slate-400">Recycle two-month-old files</span>
                    </div>
                </label>

                <label class="p-3 rounded-xl border cursor-pointer transition flex items-start gap-2.5" :class="pdfScope === 'mru' ? 'bg-purple-600/15 border-purple-500/50 text-white' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-white'">
                    <input type="radio" name="scope" value="mru" x-model="pdfScope" class="mt-0.5 text-purple-600 focus:ring-purple-500">
                    <div>
                        <span class="font-bold text-white block">🗂️ By Specific MRU</span>
                        <span class="text-[11px] text-slate-400">Clean PDFs for one MRU</span>
                    </div>
                </label>
            </div>

            <!-- MRU selector if scope === 'mru' -->
            <div x-show="pdfScope === 'mru'" class="pt-2" x-cloak>
                <label class="block text-[11px] font-bold text-slate-400 mb-1">Target MRU:</label>
                <select name="mru_id" x-model="pdfMruId" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-purple-500">
                    @foreach($mrus as $mru)
                        <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }} ({{ $mru->consumer_accounts_count }} consumers)</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
            <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
            <button type="submit" onclick="return confirm('Clean selected PDF files from storage? Database readings will remain preserved.');" class="px-5 py-2 text-xs font-bold rounded-xl bg-purple-600 hover:bg-purple-500 text-white shadow">
                Clean Selected PDF Files
            </button>
        </div>
    </form>
</div>
