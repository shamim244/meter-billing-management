@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="adminBackupApp()">

    <!-- Header & Breadcrumb -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-slate-400 transition">Admin Dashboard</a>
                <span>/</span>
                <span class="text-indigo-400">Disaster Recovery</span>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-3">
                <span>💾 Disaster Recovery & Backups Cockpit</span>
                <span class="text-xs font-mono font-normal px-2.5 py-1 rounded-full bg-indigo-950 text-indigo-300 border border-indigo-800">
                    Live System Health
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Transaction-safe database streaming, chunked PDF archiving & automated retention.</p>
        </div>

        <!-- Quick Top Actions -->
        <div class="flex items-center gap-2 flex-wrap">
            <!-- Run Retention Cleanup -->
            <form method="POST" action="{{ route('admin.backups.clean') }}" onsubmit="return confirm('Prune expired backups according to retention schedule (7d daily, 4w weekly, 6m monthly)?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-300 bg-slate-800/80 hover:bg-slate-700 border border-slate-700 transition">
                    <span>🧹</span>
                    <span>Run Retention Clean</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-xs font-bold text-emerald-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">✕</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-xs font-bold text-rose-300 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-400 hover:text-rose-200">✕</button>
        </div>
    @endif

    <!-- System Storage Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Metric 1: Backup Storage Used -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Backup Archive Size</span>
                <span class="text-xl">🗄️</span>
            </div>
            <div class="text-2xl font-black text-white font-mono mt-2">{{ $stats['total_backup_human'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ $stats['backup_count'] }} archives stored on <code class="text-indigo-400 font-mono">{{ $stats['backup_disk'] }}</code> disk</div>
        </div>

        <!-- Metric 2: Stored Consumer PDFs -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Consumer Bills (PDFs)</span>
                <span class="text-xl">📑</span>
            </div>
            <div class="text-2xl font-black text-cyan-400 font-mono mt-2">{{ $stats['total_bills_count'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ $stats['total_bills_human'] }} total uncompressed PDF storage</div>
        </div>

        <!-- Metric 3: Server Free Disk Space -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Free Disk Space</span>
                <span class="text-xl">💾</span>
            </div>
            <div class="text-2xl font-black text-emerald-400 font-mono mt-2">{{ $stats['free_disk_space_human'] }}</div>
            <div class="text-[11px] text-slate-400 mt-1">out of {{ $stats['total_disk_space_human'] }} total server capacity</div>
        </div>

        <!-- Metric 4: Last Completed Backup -->
        <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Latest Backup</span>
                <span class="text-xl">⏱️</span>
            </div>
            <div class="text-sm font-bold text-white mt-2 truncate">
                @if($stats['last_backup'])
                    {{ $stats['last_backup']->created_at->diffForHumans() }}
                @else
                    No backups yet
                @endif
            </div>
            <div class="text-[11px] text-indigo-400 mt-1 truncate">
                @if($stats['last_backup'])
                    {{ $stats['last_backup']->filename }}
                @else
                    Ready for first snapshot
                @endif
            </div>
        </div>

    </div>

    <!-- On-Demand Backup Action Console -->
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
            
            <!-- Option 1: Database Only -->
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

            <!-- Option 2: PDF Storage Only -->
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

            <!-- Option 3: Full System Snapshot -->
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

    <!-- Backups Archives Ledger Table -->
    <div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div>
                <h2 class="text-base font-bold text-white">🗂️ Backup Archives Ledger</h2>
                <p class="text-xs text-slate-400">All available restore points and historical system dumps.</p>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('admin.backups.index') }}" class="flex items-center gap-2">
                <select name="type" onchange="this.form.submit()" class="bg-slate-950 border border-slate-700 text-slate-300 text-xs rounded-xl px-3 py-1.5 font-semibold focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Types</option>
                    <option value="db_only" {{ request('type') == 'db_only' ? 'selected' : '' }}>Database Only</option>
                    <option value="storage_only" {{ request('type') == 'storage_only' ? 'selected' : '' }}>PDF Storage</option>
                    <option value="full" {{ request('type') == 'full' ? 'selected' : '' }}>Full Snapshot</option>
                </select>

                <select name="status" onchange="this.form.submit()" class="bg-slate-950 border border-slate-700 text-slate-300 text-xs rounded-xl px-3 py-1.5 font-semibold focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="text-[10px] uppercase font-black tracking-wider text-slate-400 bg-slate-950/60 border-b border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Backup Details</th>
                        <th class="py-3 px-3">Type</th>
                        <th class="py-3 px-3">Size</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Duration</th>
                        <th class="py-3 px-3">Triggered By</th>
                        <th class="py-3 px-3">Created At</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 font-medium">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-white font-mono text-xs">{{ $b->filename }}</div>
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5 flex items-center gap-2">
                                    <span>Code: {{ $b->backup_code }}</span>
                                    @if($b->sha256_hash)
                                        <span>•</span>
                                        <span title="{{ $b->sha256_hash }}">SHA: {{ substr($b->sha256_hash, 0, 10) }}...</span>
                                    @endif
                                </div>
                            </td>

                            <td class="py-3 px-3">
                                @if($b->type === 'db_only')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        🗄️ Database
                                    </span>
                                @elseif($b->type === 'storage_only')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                        📑 Storage
                                    </span>
                                @elseif($b->type === 'full')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        🚀 Full Snapshot
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">
                                        {{ $b->type_label }}
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-3 font-mono font-bold text-white">
                                {{ $b->human_size }}
                            </td>

                            <td class="py-3 px-3">
                                @if($b->status === 'completed')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                        Completed
                                    </span>
                                @elseif($b->status === 'processing')
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30 animate-pulse">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        Processing...
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span>
                                        Failed
                                    </span>
                                @endif
                            </td>

                            <td class="py-3 px-3 font-mono text-slate-400">
                                {{ $b->duration_seconds }}s
                            </td>

                            <td class="py-3 px-3 text-slate-400">
                                {{ $b->triggeredBy?->name ?? 'Automated Schedule' }}
                            </td>

                            <td class="py-3 px-3 text-slate-400 text-[11px]">
                                {{ $b->created_at->format('M d, Y H:i:s') }}
                            </td>

                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    @if($b->status === 'completed')
                                        <!-- Download Button -->
                                        <a href="{{ route('admin.backups.download', $b) }}" class="p-1.5 rounded-lg bg-indigo-950 text-indigo-300 hover:bg-indigo-900 border border-indigo-800 transition" title="Download Archive">
                                            ⬇️
                                        </a>

                                        <!-- Manifest Inspector Modal Trigger -->
                                        <button type="button" @click="inspectManifest({{ $b->id }})" class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700 transition" title="Inspect Manifest">
                                            🔍
                                        </button>
                                    @endif

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ route('admin.backups.destroy', $b) }}" onsubmit="return confirm('Permanently delete backup archive [{{ $b->filename }}]?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg bg-rose-950 text-rose-400 hover:bg-rose-900 border border-rose-900 transition" title="Delete Archive">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">
                                <div class="text-3xl mb-2">💾</div>
                                <div class="font-bold text-slate-400 text-sm">No backup archives generated yet</div>
                                <div class="text-[11px] text-slate-500 mt-1">Use the on-demand buttons above to trigger your first snapshot.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($backups->hasPages())
            <div class="pt-4 border-t border-slate-800">
                {{ $backups->links() }}
            </div>
        @endif

    </div>

    <!-- Manifest Inspection Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div @click.away="showModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-xl">🔍</span>
                    <h3 class="text-base font-bold text-white">Backup Manifest & Metadata</h3>
                </div>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
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
                <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition">
                    Close Inspector
                </button>
            </div>

        </div>
    </div>

</div>

<script>
    function adminBackupApp() {
        return {
            showModal: false,
            loadingModal: false,
            manifestData: null,

            inspectManifest(backupId) {
                this.showModal = true;
                this.loadingModal = true;
                this.manifestData = null;

                fetch(`/admin/backups/${backupId}/manifest`)
                    .then(res => res.json())
                    .then(data => {
                        this.manifestData = data;
                        this.loadingModal = false;
                    })
                    .catch(err => {
                        alert('Failed to load backup manifest: ' + err);
                        this.showModal = false;
                        this.loadingModal = false;
                    });
            }
        }
    }
</script>
@endsection
