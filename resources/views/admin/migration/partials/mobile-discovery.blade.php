<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
    <div class="flex items-center gap-3 mb-4">
        <span class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 font-bold">📱</span>
        <div>
            <h2 class="text-sm font-black text-white">Flutter Mobile App Domain Discovery Endpoint</h2>
            <p class="text-xs text-slate-400">Mobile apps in the field query this API to dynamically discover server IP / URL changes without app updates</p>
        </div>
    </div>

    <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="text-[11px] text-slate-400 block">Active Discovery URL (GET):</span>
            <code class="font-mono text-cyan-400 text-xs font-bold">{{ url('/api/v1/app/config') }}</code>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ url('/api/v1/app/config') }}" target="_blank" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                <span>🔗</span> Test Response
            </a>
        </div>
    </div>
</div>
