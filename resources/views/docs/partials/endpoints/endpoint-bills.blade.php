<!-- ENDPOINT 1: GET /bills -->
<div id="endpoint-bills" class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30">GET</span>
        <h3 class="text-base font-black text-white font-mono">/bills</h3>
        <span class="text-xs text-slate-400">• Fetch Filtered & Sorted Monthly Bills (Web Parity)</span>
    </div>

    <p class="text-xs text-slate-300 leading-relaxed">
        Pulls the complete list of consumer bills for any cycle, calculating working readings and invariant states. Supports priority sorting (<code class="font-mono text-cyan-300">pdcs</code>), column sorting, and status filtering (<code class="font-mono text-cyan-300">pending</code>, <code class="font-mono text-cyan-300">doubt</code>, <code class="font-mono text-cyan-300">critical</code>).
    </p>

    <!-- Code Snippet Display with Copy Button -->
    <div class="relative rounded-2xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs overflow-x-auto">
        <button @click="copyCode($refs.codeBills.innerText)" class="absolute right-3 top-3 text-[11px] font-sans font-bold text-slate-400 hover:text-white px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 transition">
            📋 Copy
        </button>
        <pre x-ref="codeBills" class="text-slate-200">
<template x-if="activeLang === 'python'">
import requests

url = "{{ $baseUrl }}/bills"
headers = {"Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}", "Accept": "application/json"}
params = {
    "mru_id": "{{ $userMrus->first()?->code ?? '0244' }}",
    "status": "pending",
    "sort_col": "amount",
    "sort_asc": "true",
    "status_sort": "pdcs"
}

res = requests.get(url, headers=headers, params=params)
bills = res.json().get("data", [])
print(f"Loaded {len(bills)} pending consumers.")
</template>
<template x-if="activeLang === 'curl'">
curl -X GET "{{ $baseUrl }}/bills?status=pending&sort_col=amount&sort_asc=true&status_sort=pdcs" \
  -H "Authorization: Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}" \
  -H "Accept: application/json"
</template>
<template x-if="activeLang === 'javascript'">
const res = await fetch("{{ $baseUrl }}/bills?status=pending&sort_col=amount", {
  headers: {
    "Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}",
    "Accept": "application/json"
  }
});
const data = await res.json();
console.log(data.data);
</template>
<template x-if="activeLang === 'dart'">
import 'package:http/http.dart' as http;
import 'dart:convert';

final uri = Uri.parse("{{ $baseUrl }}/bills?status=pending");
final res = await http.get(uri, headers: {
  "Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}",
  "Accept": "application/json"
});
final data = jsonDecode(res.body);
</template></pre>
    </div>
</div>
