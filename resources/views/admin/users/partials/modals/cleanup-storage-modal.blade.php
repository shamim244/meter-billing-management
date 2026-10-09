<!-- MODAL 4: Granular Data & Storage Management Console -->
<div x-show="showCleanupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showCleanupModal = false" class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-2xl w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 border border-purple-500/20 text-base">🧹</span>
                <div>
                    <h3 class="text-base font-bold text-white">Data & Storage Management Console</h3>
                    <p class="text-[11px] text-slate-400">Targeted cleanup options for <span class="text-slate-200 font-semibold">{{ $user->name }}</span> (ID: #{{ $user->id }})</p>
                </div>
            </div>
            <button type="button" @click="showCleanupModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-950 rounded-2xl border border-slate-800 text-xs font-bold overflow-x-auto">
            <button type="button" @click="cleanupTab = 'pdfs'" :class="cleanupTab === 'pdfs' ? 'bg-purple-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                <span>📄</span> Clean PDFs
            </button>
            <button type="button" @click="cleanupTab = 'mrus'" :class="cleanupTab === 'mrus' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                <span>🗂️</span> Clean MRUs
            </button>
            <button type="button" @click="cleanupTab = 'bills'" :class="cleanupTab === 'bills' ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                <span>👥</span> Clean Bills
            </button>
            <button type="button" @click="cleanupTab = 'purge'" :class="cleanupTab === 'purge' ? 'bg-rose-600 text-white shadow' : 'text-rose-400 hover:text-rose-300'" class="flex-1 py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                <span>⚠️</span> Full Purge
            </button>
        </div>

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

        <!-- TAB 2: Clean / Remove MRUs -->
        <div x-show="cleanupTab === 'mrus'" class="space-y-4" x-cloak>
            <div class="p-3 rounded-xl bg-amber-950/30 border border-amber-500/20 text-xs text-amber-200 leading-relaxed">
                ⚠️ <strong>MRU Removal:</strong> Removing an MRU permanently deletes the MRU container, its consumer records, and associated PDF files. The user's account, subscription, and remaining MRUs will remain untouched.
            </div>

            @if($mrus->isEmpty())
                <div class="p-6 text-center text-xs text-slate-500 bg-slate-950 rounded-2xl border border-slate-800">
                    This user has no active MRUs.
                </div>
            @else
                <form method="POST" action="{{ route('admin.users.clean_mrus', $user) }}" class="space-y-4">
                    @csrf
                    <div class="flex items-center justify-between pb-1">
                        <label class="text-xs font-bold text-slate-300">Select MRUs to Delete:</label>
                        <label class="text-xs text-slate-400 flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" x-model="selectAllMrus" @change="toggleAllMrus()" class="rounded text-indigo-600 focus:ring-indigo-500 bg-slate-950 border-slate-700">
                            <span>Select All</span>
                        </label>
                    </div>

                    <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
                        @foreach($mrus as $mru)
                            <label class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between text-xs cursor-pointer hover:border-slate-700 transition">
                                <div class="flex items-center gap-2.5">
                                    <input type="checkbox" name="mru_ids[]" value="{{ $mru->id }}" x-model="selectedMruIds" class="rounded text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700">
                                    <div>
                                        <span class="font-bold text-white font-mono">{{ $mru->code }}</span>
                                        <span class="text-slate-300 ml-1">{{ $mru->name }}</span>
                                    </div>
                                </div>
                                <span class="text-[11px] text-slate-400 font-medium">
                                    {{ $mru->consumer_accounts_count }} consumers
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                        <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                        <button type="submit" :disabled="selectedMruIds.length === 0" onclick="return confirm('Permanently delete the selected MRUs and their data?');" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white disabled:opacity-40 disabled:cursor-not-allowed shadow">
                            Delete Selected MRUs (<span x-text="selectedMruIds.length"></span>)
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <!-- TAB 3: Clean Consumer Bill Records -->
        <div x-show="cleanupTab === 'bills'" class="space-y-4" x-cloak>
            <div class="p-3 rounded-xl bg-cyan-950/30 border border-cyan-500/20 text-xs text-cyan-200 leading-relaxed">
                👥 <strong>Consumer Records Flush:</strong> Deletes consumer bill rows for a specific cycle or status. MRU container definitions remain intact for fresh billing cycles.
            </div>

            <form method="POST" action="{{ route('admin.users.clean_bills', $user) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Target MRU (Optional):</label>
                        <select name="mru_id" x-model="billMruId" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                            <option value="">All MRUs</option>
                            @foreach($mrus as $mru)
                                <option value="{{ $mru->id }}">{{ $mru->code }} - {{ $mru->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Review Status Filter:</label>
                        <select name="status_scope" x-model="billStatus" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                            <option value="all">All Bill Records</option>
                            <option value="doubt">Doubt Review Bills Only</option>
                            <option value="critical">Critical Review Bills Only</option>
                            <option value="unparsed">Unparsed / Draft Bills Only</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Billing Month (Optional):</label>
                        <select name="billing_month" x-model="billMonth" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                            <option value="">Any Month</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}">{{ date('F', mktime(0, 0, 0, $m, 1)) }} ({{ sprintf('%02d', $m) }})</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-400 mb-1">Billing Year (Optional):</label>
                        <select name="billing_year" x-model="billYear" class="w-full text-xs bg-slate-950 border-slate-700 rounded-xl text-white py-2 px-3 focus:ring-cyan-500">
                            <option value="">Any Year</option>
                            @for($y = (int) now()->year; $y >= 2024; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                    <button type="submit" onclick="return confirm('Flush consumer bill records matching the selected filters?');" class="px-5 py-2 text-xs font-bold rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white shadow">
                        Flush Filtered Bill Records
                    </button>
                </div>
            </form>
        </div>

        <!-- TAB 4: Full Account Purge -->
        <div x-show="cleanupTab === 'purge'" class="space-y-4" x-cloak>
            <div class="p-3.5 rounded-2xl bg-rose-950/60 border border-rose-600/40 text-xs text-rose-200 space-y-2 leading-relaxed">
                <p class="font-bold text-white flex items-center gap-1.5">
                    <span>⚠️</span> Irreversible Account & Storage Wipe
                </p>
                <p>This action permanently deletes everything for this agent:</p>
                <ul class="list-disc list-inside space-y-0.5 text-rose-300 font-mono text-[11px]">
                    <li>User profile, login credentials & roles</li>
                    <li>All stored PDF files on server disks</li>
                    <li>MRUs, consumer accounts, & billing history</li>
                    <li>Wallet ledger & active subscriptions</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('admin.users.purge', $user) }}" class="space-y-4">
                @csrf
                @method('DELETE')
                
                <div>
                    <label class="block text-xs font-bold text-white mb-1">Type <code class="bg-black/40 px-1.5 py-0.5 rounded text-rose-300">DELETE</code> to confirm permanent account purge:</label>
                    <input type="text" name="confirm_text" x-model="purgeConfirm" placeholder="DELETE" required class="w-full text-xs bg-slate-950 border-rose-500/50 rounded-xl text-white py-2.5 px-3 font-mono">
                </div>

                <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                    <button type="submit" :disabled="purgeConfirm !== 'DELETE'" class="px-5 py-2 text-xs font-black rounded-xl bg-rose-600 hover:bg-rose-500 text-white disabled:opacity-40 disabled:cursor-not-allowed shadow transition">
                        Permanently Purge Entire Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
