{{-- Flash Alerts --}}
@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
        <span>✅ {{ session('success') }}</span>
        <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
    </div>
@endif

@if(session('error'))
    <div class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs font-semibold flex items-center justify-between shadow-lg">
        <span>❌ {{ session('error') }}</span>
        <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
    </div>
@endif
