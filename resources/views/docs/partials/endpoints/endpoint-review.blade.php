<!-- ENDPOINT 2: PATCH /bills/review -->
<div id="endpoint-review" class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
    <div class="flex flex-wrap items-center gap-3">
        <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">PATCH</span>
        <h3 class="text-base font-black text-white font-mono">/bills/review</h3>
        <span class="text-xs text-slate-400">• Submit Human Field Review Decision & Reading</span>
    </div>

    <p class="text-xs text-slate-300 leading-relaxed">
        The primary atomic update endpoint used in the field. Updates review status (<code class="font-mono text-cyan-300">submitted</code>, <code class="font-mono text-cyan-300">doubt</code>, <code class="font-mono text-cyan-300">critical</code>), attaches reason codes and remarks, and updates physical meter reading in one single transaction.
    </p>

    <!-- Code Snippet -->
    <div class="relative rounded-2xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs overflow-x-auto">
        <button @click="copyCode($refs.codeReview.innerText)" class="absolute right-3 top-3 text-[11px] font-sans font-bold text-slate-400 hover:text-white px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-800 transition">
            📋 Copy
        </button>
        <pre x-ref="codeReview" class="text-slate-200">
<template x-if="activeLang === 'python'">
import requests

url = "{{ $baseUrl }}/bills/review"
headers = {"Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}", "Content-Type": "application/json"}
payload = {
    "ca_number": "10230063090",
    "billing_month": {{ now()->month }},
    "billing_year": {{ now()->year }},
    "status": "doubt",
    "reason_code": "PREMISES_LOCKED",
    "remark": "Main gate locked, neighbor says family is out of town",
    "working_reading": "850"
}

res = requests.patch(url, headers=headers, json=payload)
print(res.json())
</template>
<template x-if="activeLang === 'curl'">
curl -X PATCH "{{ $baseUrl }}/bills/review" \
  -H "Authorization: Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}" \
  -H "Content-Type: application/json" \
  -d '{
    "ca_number": "10230063090",
    "billing_month": {{ now()->month }},
    "billing_year": {{ now()->year }},
    "status": "submitted",
    "working_reading": "850",
    "remark": "Photo captured"
  }'
</template>
<template x-if="activeLang === 'javascript'">
const res = await fetch("{{ $baseUrl }}/bills/review", {
  method: "PATCH",
  headers: {
    "Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}",
    "Content-Type": "application/json"
  },
  body: JSON.stringify({
    ca_number: "10230063090",
    billing_month: {{ now()->month }},
    billing_year: {{ now()->year }},
    status: "submitted",
    working_reading: "850"
  })
});
console.log(await res.json());
</template>
<template x-if="activeLang === 'dart'">
final res = await http.patch(
  Uri.parse("{{ $baseUrl }}/bills/review"),
  headers: {"Authorization": "Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}", "Content-Type": "application/json"},
  body: jsonEncode({
    "ca_number": "10230063090",
    "billing_month": {{ now()->month }},
    "billing_year": {{ now()->year }},
    "status": "submitted",
    "working_reading": "850"
  })
);
</template></pre>
    </div>
</div>
