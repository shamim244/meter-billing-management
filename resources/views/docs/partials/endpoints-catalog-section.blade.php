<!-- Language Code Switcher & Endpoints -->
<section id="endpoints" class="space-y-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
        <div>
            <h2 class="text-2xl font-black text-white tracking-tight">Endpoints Catalog</h2>
            <p class="text-xs text-slate-400 mt-1">Select your preferred programming language below to update all code snippets dynamically.</p>
        </div>

        <!-- Global Language Switcher Tabs -->
        <div class="flex items-center p-1 rounded-xl bg-slate-900 border border-slate-800 font-semibold text-xs shrink-0">
            <button @click="activeLang = 'python'" :class="activeLang === 'python' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
                🐍 Python
            </button>
            <button @click="activeLang = 'curl'" :class="activeLang === 'curl' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
                💻 cURL
            </button>
            <button @click="activeLang = 'javascript'" :class="activeLang === 'javascript' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
                🟨 Node.js
            </button>
            <button @click="activeLang = 'dart'" :class="activeLang === 'dart' ? 'bg-brand-600 text-white shadow' : 'text-slate-400 hover:text-white'" class="px-3 py-1.5 rounded-lg transition">
                🎯 Dart
            </button>
        </div>
    </div>

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

    <!-- ENDPOINT 4: GET /automation/queue -->
    <div id="endpoint-queue" class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
        <div class="flex flex-wrap items-center gap-3">
            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30">GET</span>
            <h3 class="text-base font-black text-white font-mono">/automation/queue</h3>
            <span class="text-xs text-slate-400">• High-Speed Worker Queue for ADB Bots</span>
        </div>

        <p class="text-xs text-slate-300 leading-relaxed">
            Lightweight FIFO queue endpoint specifically optimized for Python ADB scripts to fetch the next batch of unsubmitted consumers with pre-calculated suggested readings.
        </p>

        <div class="relative rounded-2xl bg-slate-950 border border-slate-800 p-4 font-mono text-xs overflow-x-auto">
            <pre class="text-slate-200">
# Pull next 25 consumers in queue for MRU 0244
curl -X GET "{{ $baseUrl }}/automation/queue?mru_code=0244&limit=25" \
  -H "Authorization: Bearer {{ $activeKey ?? 'nbp_live_YOUR_KEY' }}"</pre>
        </div>
    </div>
</section>
