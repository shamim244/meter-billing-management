{{-- Top Flash Alerts & Info Bar --}}
@if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-semibold flex items-center justify-between shadow-lg">
        <span>✅ {{ session('success') }}</span>
        <button @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">✕</button>
    </div>
@endif

<div class="flex items-center justify-between">
    <span class="text-xs text-slate-400">Configure active payment methods, gateway credentials, and business settlement accounts.</span>
    <span class="text-xs text-slate-500">Last updated: {{ now()->format('d M Y, h:i A') }}</span>
</div>
