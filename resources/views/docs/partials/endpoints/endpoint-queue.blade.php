<!-- ENDPOINT 4: GET /automation/queue -->
<div id="endpoint-queue" class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30">GET</span>
        <h3 class="text-base font-black text-white font-mono">/automation/queue</h3>
        <span class="text-xs text-slate-400">• High-Speed Worker Queue for ADB Bots</span>
    </div>

    <p class="text-xs text-slate-300 leading-relaxed">
        Lightweight FIFO queue endpoint specifically optimized for Python ADB scripts to fetch the next batch of unsubmitted consumers with pre-calculated suggested readings. Supports batch tuning parameters: <code class="font-mono text-cyan-300">apply_tuning=true</code>, <code class="font-mono text-cyan-300">min_units_floor</code> (minimum consumption floor, e.g. 125 kWh), <code class="font-mono text-cyan-300">max_units_ceiling</code>, <code class="font-mono text-cyan-300">adjustment_percent</code>, and single-consumer targeting (<code class="font-mono text-cyan-300">ca=...</code>).
    </p>

    <div class="relative rounded-2xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs overflow-x-auto">
        <pre class="text-slate-200">
# Pull next 25 consumers in queue with batch tuning and min floor clamped to 125 kWh
curl -X GET "{{ $baseUrl }}/automation/queue?mru_code=8&limit=25&apply_tuning=true&min_units_floor=125" \
  -H "Authorization: Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}"</pre>
    </div>
</div>
