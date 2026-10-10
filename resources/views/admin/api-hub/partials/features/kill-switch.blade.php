<!-- Master API Kill Switch -->
<div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg" :class="apiMaster ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'">
            <span x-text="apiMaster ? '🌐' : '🛑'"></span>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-bold text-white">Global API Master Switch</h3>
                <span x-show="apiMaster" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Online</span>
                <span x-show="!apiMaster" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">Offline</span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">
                Master kill-switch. When disabled, all endpoints under <code class="text-rose-400 bg-slate-900 px-1 py-0.5 rounded">/api/v1/*</code> return HTTP 503 Service Unavailable.
            </p>
        </div>
    </div>
    <div>
        <button type="button" @click="apiMaster = !apiMaster" class="relative inline-flex items-center cursor-pointer">
            <div class="w-14 h-7 bg-slate-800 rounded-full transition-colors duration-200" :class="apiMaster ? 'bg-emerald-600' : 'bg-slate-800'">
                <div class="w-6 h-6 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="apiMaster ? 'translate-x-7' : 'translate-x-1'"></div>
            </div>
        </button>
    </div>
</div>
