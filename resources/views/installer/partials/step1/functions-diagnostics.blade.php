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
