{{-- Master Toggle Card --}}
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg" :class="enabled ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'">
            <span x-text="enabled ? '🛡️' : '⚠️'"></span>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-bold text-white">Global Rate Limiting Enforcement</h3>
                <span x-show="enabled" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Active</span>
                <span x-show="!enabled" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">Bypassed</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
                When enabled, requests exceeding configured quotas receive standard <code class="text-rose-400 bg-slate-900 px-1 py-0.5 rounded">HTTP 429 Too Many Requests</code>. When disabled, all endpoints allow unlimited traffic.
            </p>
        </div>
    </div>

    <div class="flex items-center gap-3 self-end sm:self-center">
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="enabled" value="1" x-model="enabled" class="sr-only peer">
            <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-600"></div>
        </label>
    </div>
</div>
