{{-- Backups Archives Ledger Table --}}
<div class="p-6 rounded-3xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
    
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
        <div>
            <h2 class="text-base font-bold text-white">🗂️ Backup Archives Ledger</h2>
            <p class="text-xs text-slate-400">All available restore points and historical system dumps.</p>
        </div>

        {{-- Filters --}}
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
                                    {{-- Download Button --}}
                                    <a href="{{ route('admin.backups.download', $b) }}" class="p-1.5 rounded-lg bg-indigo-950 text-indigo-300 hover:bg-indigo-900 border border-indigo-800 transition" title="Download Archive">
                                        ⬇️
                                    </a>

                                    {{-- Manifest Inspector Modal Trigger --}}
                                    <button type="button" @click="inspectManifest({{ $b->id }})" class="p-1.5 rounded-lg bg-slate-800 text-slate-300 hover:bg-slate-700 border border-slate-700 transition" title="Inspect Manifest">
                                        🔍
                                    </button>
                                @endif

                                {{-- Delete Button --}}
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
