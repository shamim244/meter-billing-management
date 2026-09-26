@extends('installer.layout', ['currentStep' => 1, 'title' => 'Server Requirements Audit'])

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-lg font-black text-white flex items-center gap-2">
            <span>🔍</span> Step 1: Server Readiness & Compatibility Audit
        </h2>
        <p class="text-xs text-slate-400 mt-1">
            Checking host environment, required PHP extensions, and directory write permissions.
        </p>
    </div>

    @php
        $isReady = $preflight['server_ready'] ?? $preflight['ready'];
    @endphp

    <!-- Preflight Status Banner -->
    @if($isReady)
        <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-800/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-base">✓</span>
                <div>
                    <h3 class="text-xs font-bold text-emerald-300">Server Environment Ready</h3>
                    <p class="text-[11px] text-emerald-400/80">PHP runtime, mandatory extensions, and storage directories are fully compatible.</p>
                </div>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-black rounded-lg bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 uppercase tracking-wide">
                PASSED
            </span>
        </div>
    @else
        <div class="p-4 rounded-2xl bg-rose-950/40 border border-rose-800/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-base">✗</span>
                <div>
                    <h3 class="text-xs font-bold text-rose-300">Attention Required</h3>
                    <p class="text-[11px] text-rose-400/80">Some mandatory extensions, PHP version (>= 8.4.1), or directory write permissions require configuration.</p>
                </div>
            </div>
            <span class="px-2.5 py-1 text-[10px] font-black rounded-lg bg-rose-500/10 text-rose-300 border border-rose-500/20 uppercase tracking-wide">
                ACTION NEEDED
            </span>
        </div>
    @endif

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

    <!-- Required PHP Extensions -->
    <div class="space-y-2">
        <h4 class="text-xs font-bold text-slate-300">PHP Extensions</h4>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
            @foreach($preflight['extensions'] as $ext => $loaded)
                <div class="p-2.5 rounded-xl border flex items-center justify-between {{ $loaded ? 'bg-slate-950/40 border-slate-800 text-slate-300' : 'bg-rose-950/20 border-rose-800/40 text-rose-300' }}">
                    <span class="font-mono text-[11px] font-semibold">{{ $ext }}</span>
                    <span class="text-xs">{{ $loaded ? '✅' : '❌' }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- PHP Functions & Server Diagnostics -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold text-slate-300">PHP Functions & Server Diagnostics</h4>
            @if(count($preflight['disabled_functions'] ?? []) > 0)
                <span class="text-[10px] font-semibold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-lg border border-amber-500/20">
                    {{ count($preflight['disabled_functions']) }} function(s) disabled in php.ini
                </span>
            @else
                <span class="text-[10px] font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20">
                    All core functions permitted
                </span>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            @foreach(array_merge($preflight['functions']['critical'] ?? [], $preflight['functions']['recommended'] ?? []) as $name => $fn)
                <div class="p-2.5 rounded-xl border flex items-center justify-between {{ $fn['enabled'] ? 'bg-slate-950/40 border-slate-800 text-slate-300' : 'bg-amber-950/20 border-amber-800/40 text-amber-300' }}">
                    <div class="pr-2">
                        <div class="flex items-center gap-1.5">
                            <span class="font-mono text-[11px] font-bold">{{ $fn['name'] }}()</span>
                            <span class="text-[9px] uppercase px-1 rounded {{ ($fn['category'] ?? '') === 'critical' ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-800 text-slate-400' }}">
                                {{ $fn['category'] ?? 'recommended' }}
                            </span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $fn['description'] }}</p>
                    </div>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded shrink-0 {{ $fn['enabled'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400' }}">
                        {{ $fn['enabled'] ? 'AVAILABLE' : 'DISABLED' }}
                    </span>
                </div>
            @endforeach
        </div>

        <!-- Hostinger Remediation Box -->
        @if(count($preflight['disabled_functions'] ?? []) > 0 || !($preflight['opcache']['enabled'] ?? false))
            <div class="p-3.5 rounded-xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-300 space-y-2">
                <div class="font-bold text-slate-200 flex items-center gap-1.5">
                    <span>💡</span> Hostinger / Shared Hosting Remediation Guide
                </div>
                <p class="text-slate-400 leading-relaxed">
                    If any functions show as <span class="text-amber-400 font-semibold">DISABLED</span> or OPcache is inactive, you can easily enable them in your hosting control panel:
                </p>
                <div class="p-2.5 rounded-lg bg-slate-950 font-mono text-[10px] text-indigo-300 border border-slate-800 space-y-1">
                    <div><strong>Hostinger hPanel:</strong> Advanced → PHP Configuration → PHP Functions → uncheck from <em>disable_functions</em> list</div>
                    <div><strong>OPcache:</strong> Advanced → PHP Configuration → PHP Extensions → check <em>opcache</em></div>
                </div>
                <p class="text-slate-500 text-[10px]">
                    ✨ <strong>Built-in Resilience:</strong> Even if <span class="font-mono text-slate-400">symlink()</span> is restricted, our automatic storage route fallback serves public uploads and images seamlessly without throwing errors!
                </p>
            </div>
        @endif
    </div>

    <!-- Directory Write Permissions -->
    <div class="space-y-2">
        <h4 class="text-xs font-bold text-slate-300">Writable Directories</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
            @foreach($preflight['writable_paths'] as $path => $writable)
                <div class="p-2.5 rounded-xl border flex items-center justify-between {{ $writable ? 'bg-slate-950/40 border-slate-800 text-slate-300' : 'bg-rose-950/20 border-rose-800/40 text-rose-300' }}">
                    <span class="font-mono text-[11px] font-semibold">{{ $path }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded {{ $writable ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                        {{ $writable ? 'WRITABLE' : 'READ-ONLY' }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="pt-4 flex items-center justify-between border-t border-slate-800">
        <a href="{{ route('install.step1') }}" class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-800 rounded-xl transition">
            ↻ Re-check Environment
        </a>

        @if($isReady)
            <a href="{{ route('install.step2') }}" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                <span>Next: Database Setup</span>
                <span>→</span>
            </a>
        @else
            <button disabled class="px-6 py-2.5 bg-slate-800 text-slate-500 text-xs font-black rounded-xl cursor-not-allowed">
                Fix Critical Issues to Proceed
            </button>
        @endif
    </div>
</div>
@endsection
