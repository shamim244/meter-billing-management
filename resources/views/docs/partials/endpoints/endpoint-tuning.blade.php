<!-- ENDPOINT 5: POST /consumers/{ca}/average-tuning -->
<div id="endpoint-tuning" class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-500/20 text-purple-400 border border-purple-500/30">POST</span>
        <h3 class="text-base font-black text-white font-mono">/consumers/{ca}/average-tuning</h3>
        <span class="text-xs text-slate-400">• Configure Smart Average Guardrails & Safe Ceilings</span>
    </div>

    <p class="text-xs text-slate-300 leading-relaxed">
        Sets permanent consumer-level smart average guardrails in the database and active billing cycle. Configure a Minimum Floor (<code class="font-mono text-cyan-300">min_avg_units</code>), Maximum Ceiling (<code class="font-mono text-cyan-300">max_avg_units</code>), Fixed Override (<code class="font-mono text-cyan-300">custom_avg_units</code>), or bypass flag (<code class="font-mono text-cyan-300">is_avg_excluded</code>).
        <span class="block mt-1 text-slate-400">💡 <strong>Bidirectional Parity:</strong> Updates made via this API instantly reflect on the Web Dashboard card view, and adjustments made in the browser GUI modal instantly reflect across all API endpoints with zero conflicts.</span>
    </p>

    <!-- Code Snippet Display with Copy Button -->
    <div class="relative rounded-2xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs overflow-x-auto">
        <button @click="copyCode($refs.codeTuning.innerText)" class="absolute right-3 top-3 text-[11px] font-sans font-bold text-slate-400 hover:text-white px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 transition">
            📋 Copy
        </button>
        <pre x-ref="codeTuning" class="text-slate-200">
<template x-if="activeLang === 'python'">
import requests

url = "{{ $baseUrl }}/consumers/10230040377/average-tuning"
headers = {"Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}", "Content-Type": "application/json"}
payload = {
    "min_avg_units": 40,      # Min Safe Floor (kWh)
    "max_avg_units": 100,     # Max Safe Ceiling (kWh)
    "custom_avg_units": 75,   # Fixed Override (optional)
    "is_avg_excluded": False
}

res = requests.post(url, headers=headers, json=payload)
print(res.json())
</template>
<template x-if="activeLang === 'curl'">
curl -X POST "{{ $baseUrl }}/consumers/10230040377/average-tuning" \
  -H "Authorization: Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}" \
  -H "Content-Type: application/json" \
  -d '{
    "min_avg_units": 40,
    "max_avg_units": 100,
    "custom_avg_units": 75,
    "is_avg_excluded": false
  }'
</template>
<template x-if="activeLang === 'javascript'">
const res = await fetch("{{ $baseUrl }}/consumers/10230040377/average-tuning", {
  method: "POST",
  headers: {
    "Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}",
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    min_avg_units: 40,
    max_avg_units: 100,
    custom_avg_units: 75,
    is_avg_excluded: false
  })
});
const data = await res.json();
console.log(data);
</template>
<template x-if="activeLang === 'dart'">
import 'package:http/http.dart' as http;
import 'dart:convert';

final uri = Uri.parse("{{ $baseUrl }}/consumers/10230040377/average-tuning");
final res = await http.post(
  uri,
  headers: {
    "Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}",
    "Content-Type": "application/json"
  },
  body: jsonEncode({
    "min_avg_units": 40,
    "max_avg_units": 100,
    "custom_avg_units": 75,
    "is_avg_excluded": false
  }),
);
final data = jsonDecode(res.body);
</template></pre>
    </div>
</div>
