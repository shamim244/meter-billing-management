<!-- ENDPOINT 3: POST /bills/batch-sync -->
<div id="endpoint-batch-sync" class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">POST</span>
        <h3 class="text-base font-black text-white font-mono">/bills/batch-sync</h3>
        <span class="text-xs text-slate-400">• Offline-First Rural Route Synchronization</span>
    </div>

    <p class="text-xs text-slate-300 leading-relaxed">
        For rural zones with no cellular connectivity. Collect verdicts locally in SQLite or memory, and upload up to 1,000 records in a single atomic database commit upon returning to data coverage.
    </p>

    <div class="relative rounded-2xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs overflow-x-auto">
        <pre class="text-slate-200">
curl -X POST "{{ $baseUrl }}/bills/batch-sync" \
  -H "Authorization: Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}" \
  -H "Content-Type: application/json" \
  -d '{
    "billing_month": {{ now()->month }},
    "billing_year": {{ now()->year }},
    "reviews": [
      { "ca_number": "10230063090", "status": "submitted", "working_reading": "850" },
      { "ca_number": "10230074463", "status": "doubt", "reason_code": "PREMISES_LOCKED", "remark": "House closed" },
      { "ca_number": "10230058477", "status": "critical", "reason_code": "METER_BURNT_DEAD", "remark": "Display burnt" }
    ]
  }'</pre>
    </div>
</div>
