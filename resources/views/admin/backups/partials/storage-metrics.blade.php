{{-- System Storage Metrics Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    
    {{-- Metric 1: Backup Storage Used --}}
    <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Backup Archive Size</span>
            <span class="text-xl">🗄️</span>
        </div>
        <div class="text-2xl font-black text-white font-mono mt-2">{{ $stats['total_backup_human'] }}</div>
        <div class="text-[11px] text-slate-400 mt-1">{{ $stats['backup_count'] }} archives stored on <code class="text-indigo-400 font-mono">{{ $stats['backup_disk'] }}</code> disk</div>
    </div>

    {{-- Metric 2: Stored Consumer PDFs --}}
    <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Consumer Bills (PDFs)</span>
            <span class="text-xl">📑</span>
        </div>
        <div class="text-2xl font-black text-cyan-400 font-mono mt-2">{{ $stats['total_bills_count'] }}</div>
        <div class="text-[11px] text-slate-400 mt-1">{{ $stats['total_bills_human'] }} total uncompressed PDF storage</div>
    </div>

    {{-- Metric 3: Server Free Disk Space --}}
    <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-sm relative overflow-hidden">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Free Disk Space</span>
            <span class="text-xl">💾</span>
        </div>
        <div class="text-2xl font-black text-emerald-400 font-mono mt-2">{{ $stats['free_disk_space_human'] }}</div>
        <div class="text-[11px] text-slate-400 mt-1">out of {{ $stats['total_disk_space_human'] }} total server capacity</div>
    </div>

    {{-- Metric 4: Last Completed Backup --}}
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
