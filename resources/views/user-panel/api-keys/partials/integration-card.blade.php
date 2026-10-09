<!-- Quick Integration Reference Card -->
<div class="bg-slate-900 dark:bg-slate-950 text-white rounded-3xl p-6 sm:p-8 border border-slate-800 shadow-xl space-y-4">
    <div class="flex items-center gap-3">
        <span class="text-xl">⚡</span>
        <div>
            <h3 class="text-sm font-bold text-white">How to Use in Your Python ADB Tool</h3>
            <p class="text-xs text-slate-400">Include the key in the Authorization header on every request.</p>
        </div>
    </div>

    <div class="p-4 rounded-2xl bg-black/50 border border-white/5 font-mono text-xs overflow-x-auto space-y-2">
        <div class="text-slate-400"># 1. Python Requests Example:</div>
        <div class="text-cyan-300">import requests</div>
        <div class="text-slate-200">headers = {</div>
        <div class="text-emerald-400">    "Authorization": "Bearer nbp_live_YOUR_KEY_HERE",</div>
        <div class="text-slate-200">    "Accept": "application/json"</div>
        <div class="text-slate-200">}</div>
        <div class="text-slate-200">res = requests.get("<span class="text-amber-300">{{ url('/api/v1/bills') }}</span>?status=pending", headers=headers)</div>
        <div class="text-slate-200">pending_bills = res.json()["data"]</div>
    </div>
</div>
