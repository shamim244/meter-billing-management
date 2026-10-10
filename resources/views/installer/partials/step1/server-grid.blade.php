<!-- Server Details Grid -->
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
        <span class="text-slate-400 block text-[11px]">PHP Version</span>
        <span class="text-white font-bold text-sm">{{ $preflight['php_version'] }}</span>
        <span class="text-[10px] block mt-0.5 {{ $preflight['php_satisfies'] ? 'text-emerald-400' : 'text-rose-400 font-bold' }}">
            {{ $preflight['php_satisfies'] ? '✓ Satisfies >= 8.4.1' : '✗ Requires >= 8.4.1' }}
        </span>
    </div>

    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
        <span class="text-slate-400 block text-[11px]">Memory Limit</span>
        <span class="text-white font-bold text-sm">{{ $preflight['memory_limit'] }}</span>
        <span class="text-[10px] text-slate-400 block mt-0.5">Max Upload: {{ $preflight['upload_max_filesize'] }}</span>
    </div>

    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
        <span class="text-slate-400 block text-[11px]">Post Max Size</span>
        <span class="text-white font-bold text-sm">{{ $preflight['post_max_size'] }}</span>
        <span class="text-[10px] text-emerald-400 block mt-0.5">✓ Upload Buffer</span>
    </div>

    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
        <span class="text-slate-400 block text-[11px]">Zend OPcache</span>
        <span class="text-white font-bold text-sm">{{ ($preflight['opcache']['enabled'] ?? false) ? 'Enabled' : (($preflight['opcache']['installed'] ?? false) ? 'Disabled' : 'Missing') }}</span>
        <span class="text-[10px] block mt-0.5 {{ ($preflight['opcache']['enabled'] ?? false) ? 'text-emerald-400' : 'text-amber-400 font-semibold' }}">
            {{ ($preflight['opcache']['enabled'] ?? false) ? '✓ Bytecode Active' : '⚠️ Recommended' }}
        </span>
    </div>
</div>
