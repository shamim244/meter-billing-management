<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>NBPDCL Developer Portal & REST API Documentation (v1)</title>
    <meta name="description" content="Official REST API documentation and automation guides for NBPDCL/BSPHCL electricity billing, field reader scripts, mobile clients, and AI agents.">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Alpine.js & Tailwind CSS CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        [x-cloak] { display: none !important; }
        pre, code { font-family: 'JetBrains Mono', monospace; }
        .glass-panel {
            background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body x-data="devPortal()" class="font-sans antialiased bg-slate-950 text-slate-100 min-h-screen flex flex-col selection:bg-brand-500 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 w-full border-b border-slate-800 bg-slate-950/90 backdrop-blur-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 p-0.5 shadow-md shadow-brand-500/20 group-hover:scale-105 transition">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center font-bold text-cyan-400 text-sm">
                            ⚡
                        </div>
                    </div>
                    <div>
                        <span class="font-extrabold text-sm tracking-tight text-white group-hover:text-brand-300 transition">NBPDCL Developers</span>
                        <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-widest block leading-none">REST API v1</span>
                    </div>
                </a>
            </div>

            <div class="flex items-center gap-3 text-xs font-semibold">
                <a href="{{ $openapiUrl }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-cyan-400 border border-slate-800 transition font-mono text-[11px]">
                    <span>📄</span>
                    <span>openapi.json</span>
                </a>

                @auth
                    <a href="{{ route('user-panel.api-keys') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition">
                        <span>🔑</span>
                        <span>Manage Keys</span>
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold transition shadow-sm">
                        <span>Dashboard →</span>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-slate-400 hover:text-white transition px-3 py-1.5">
                        Sign In
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold transition shadow-sm">
                        Get API Key →
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Personalized API Key Notification Banner -->
    @auth
        <div class="border-b border-cyan-900/50 bg-gradient-to-r from-cyan-950/60 via-slate-900/90 to-brand-950/60 py-2.5 px-4 sm:px-6 lg:px-8 text-xs text-slate-300">
            <div class="max-w-7xl mx-auto flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="text-base">👋</span>
                    <span>Logged in as <strong>{{ $user->name }}</strong>. 
                        @if($activeKey)
                            Code samples are pre-filled with your active key: <code class="font-mono text-cyan-300 font-bold px-1.5 py-0.5 bg-black/40 rounded">{{ $activeKey }}</code>
                        @else
                            You don't have an active API key yet. <a href="{{ route('user-panel.api-keys') }}" class="text-cyan-400 font-bold underline hover:text-cyan-300">Generate one now in 5 seconds</a>.
                        @endif
                    </span>
                </div>
                <a href="#try-it-out" class="text-[11px] font-bold text-cyan-400 hover:underline shrink-0">
                    Jump to Live Console ↓
                </a>
            </div>
        </div>
    @endauth

    <!-- Documentation Container -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Sticky Left Sidebar -->
        <aside class="lg:col-span-3 space-y-6">
            <div class="sticky top-24 space-y-5">
                <!-- Search Box -->
                <div class="relative">
                    <input type="text" 
                           x-model="searchQuery" 
                           placeholder="Filter endpoints (e.g. review, queue)..." 
                           class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-slate-200 placeholder-slate-500 focus:ring-brand-500 focus:border-brand-500 p-2.5 pl-8 font-medium">
                    <span class="absolute left-2.5 top-3 text-slate-500 text-xs">🔍</span>
                </div>

                <!-- Navigation List -->
                <nav class="space-y-4 text-xs font-semibold">
                    <div class="space-y-1">
                        <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                            Getting Started
                        </div>
                        <a href="#quickstart" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            ⚡ Quickstart & Auth
                        </a>
                        <a href="#endpoints" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            📋 Endpoints Catalog
                        </a>
                        <a href="#reason-codes" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            🏷️ Doubt & Critical Codes
                        </a>
                    </div>

                    <div class="space-y-1">
                        <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                            Core Automation APIs
                        </div>
                        <a href="#endpoint-bills" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            <span class="text-[10px] font-bold text-sky-400 font-mono">GET</span> /bills
                        </a>
                        <a href="#endpoint-review" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            <span class="text-[10px] font-bold text-amber-400 font-mono">PATCH</span> /bills/review
                        </a>
                        <a href="#endpoint-batch-sync" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            <span class="text-[10px] font-bold text-emerald-400 font-mono">POST</span> /bills/batch-sync
                        </a>
                        <a href="#endpoint-queue" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            <span class="text-[10px] font-bold text-sky-400 font-mono">GET</span> /automation/queue
                        </a>
                        <a href="#endpoint-mrus" class="block px-3 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-900 transition">
                            <span class="text-[10px] font-bold text-sky-400 font-mono">GET</span> /mrus
                        </a>
                    </div>

                    <div class="space-y-1">
                        <div class="text-[10px] font-black uppercase text-slate-500 tracking-wider px-3">
                            Interactive & AI
                        </div>
                        <a href="#try-it-out" class="block px-3 py-1.5 rounded-lg text-cyan-400 font-bold hover:bg-slate-900 transition flex items-center justify-between">
                            <span>🎮 Live API Console</span>
                            <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        </a>
                        <a href="#ai-agent-guide" class="block px-3 py-1.5 rounded-lg text-purple-400 font-bold hover:bg-slate-900 transition">
                            🤖 AI Agent / Copilot Setup
                        </a>
                    </div>
                </nav>
            </div>
        </aside>

        <!-- Main Documentation Content -->
        <main class="lg:col-span-9 space-y-12">
            
            <!-- Quickstart & Authentication Section -->
            <section id="quickstart" class="space-y-6">
                <div class="border-b border-slate-800 pb-4">
                    <h2 class="text-2xl font-black text-white tracking-tight">API Quickstart & Authentication</h2>
                    <p class="text-xs text-slate-400 mt-1">Authenticate all requests with your secret Bearer token or direct X-API-Key header.</p>
                </div>

                <div class="glass-panel p-6 rounded-3xl space-y-4">
                    <h3 class="text-sm font-bold text-white">Base Endpoint URL:</h3>
                    <div class="p-3 rounded-xl bg-black/60 border border-slate-800 flex items-center justify-between font-mono text-xs text-cyan-300">
                        <span>{{ $baseUrl }}</span>
                        <button @click="copyCode('{{ $baseUrl }}')" class="text-slate-400 hover:text-white text-xs font-sans font-bold">Copy</button>
                    </div>

                    <h3 class="text-sm font-bold text-white mt-4">Authentication Headers (Choose either):</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-[10px] font-mono uppercase font-bold text-cyan-400 block mb-1">Method 1: Bearer Token (Standard)</span>
                            <code class="font-mono text-xs text-slate-200">Authorization: Bearer <span class="text-cyan-300">{{ $activeKey ?? 'nbp_live_YOUR_KEY' }}</span></code>
                        </div>
                        <div class="p-3.5 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-[10px] font-mono uppercase font-bold text-brand-400 block mb-1">Method 2: Direct API Key Header</span>
                            <code class="font-mono text-xs text-slate-200">X-API-Key: <span class="text-cyan-300">{{ $activeKey ?? 'nbp_live_YOUR_KEY' }}</span></code>
                        </div>
                    </div>

                    <!-- Intelligent Rate Limiting Overview -->
                    <div class="mt-6 pt-4 border-t border-slate-800 space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>🛡️</span>
                                <span>Intelligent Rate Limiting (Per Device / Key)</span>
                            </h3>
                            <span class="text-[11px] font-mono text-emerald-400 font-bold">Isolated per API Key</span>
                        </div>
                        <p class="text-xs text-slate-400">
                            Quotas are isolated per API Key so devices sharing a Wi-Fi or cellular hotspot never block each other.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Reads & Queue</span>
                                <span class="text-base font-black text-cyan-300 font-mono mt-0.5 block">{{ $rateLimits['general_per_minute'] ?? 240 }} req / min</span>
                                <span class="text-[10px] text-slate-500">~{{ round(($rateLimits['general_per_minute'] ?? 240) / 60, 1) }} req/sec smooth browsing</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Review Verdicts</span>
                                <span class="text-base font-black text-amber-300 font-mono mt-0.5 block">{{ $rateLimits['review_per_minute'] ?? 120 }} rev / min</span>
                                <span class="text-[10px] text-slate-500">~{{ round(($rateLimits['review_per_minute'] ?? 120) / 60, 1) }} updates/sec field headroom</span>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900/60 border border-slate-800">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block">Offline Batch Sync</span>
                                <span class="text-base font-black text-emerald-300 font-mono mt-0.5 block">{{ $rateLimits['batch_per_minute'] ?? 30 }} batches / min</span>
                                <span class="text-[10px] text-slate-500">Multi-record payload sync</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

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

            <!-- Standard Doubt & Critical Reason Codes Dictionary -->
            <section id="reason-codes" class="space-y-6">
                <div class="border-b border-slate-800 pb-4">
                    <h2 class="text-2xl font-black text-white tracking-tight">Standard Reason Code Dictionary</h2>
                    <p class="text-xs text-slate-400 mt-1">Pass these standard codes in <code class="font-mono text-cyan-300">reason_code</code> to maintain structured reporting in the web dashboard.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Doubt Codes -->
                    <div class="glass-panel p-6 rounded-3xl space-y-4">
                        <h3 class="text-sm font-bold text-amber-400 flex items-center gap-2">
                            <span>🟡</span>
                            <span>Doubt Reason Codes (Suspicious / Re-check)</span>
                        </h3>
                        <ul class="space-y-2.5 text-xs">
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-cyan-300 font-bold">PREMISES_LOCKED</code>
                                <span class="text-slate-400">House locked / closed</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-cyan-300 font-bold">SUSPICIOUS_READING</code>
                                <span class="text-slate-400">Abnormal reading spike</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-cyan-300 font-bold">SUSPICIOUS_AMOUNT</code>
                                <span class="text-slate-400">High arrears disputed</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-cyan-300 font-bold">PREV_MONTH_MISMATCH</code>
                                <span class="text-slate-400">Dial baseline mismatch</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-cyan-300 font-bold">OWNER_RECHECK_REQUEST</code>
                                <span class="text-slate-400">Consumer re-verification</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Critical Codes -->
                    <div class="glass-panel p-6 rounded-3xl space-y-4">
                        <h3 class="text-sm font-bold text-rose-400 flex items-center gap-2">
                            <span>🔴</span>
                            <span>Critical Reason Codes (Unworkable / Fault)</span>
                        </h3>
                        <ul class="space-y-2.5 text-xs">
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-rose-300 font-bold">METER_BURNT_DEAD</code>
                                <span class="text-slate-400">Black/burnt display</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-rose-300 font-bold">METER_TAMPERED_BYPASS</code>
                                <span class="text-slate-400">Seal broken / direct line</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-rose-300 font-bold">METER_MISSING_STOLEN</code>
                                <span class="text-slate-400">Meter stolen / not found</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-rose-300 font-bold">PREMISES_DEMOLISHED</code>
                                <span class="text-slate-400">Building demolished</span>
                            </li>
                            <li class="p-2.5 rounded-xl bg-slate-900/80 border border-slate-800 flex justify-between items-center">
                                <code class="font-mono text-rose-300 font-bold">JE_INTERVENTION_REQUIRED</code>
                                <span class="text-slate-400">DISCOM legal/SDO block</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- LIVE "TRY IT OUT" API CONSOLE -->
            <section id="try-it-out" class="space-y-6 pt-4">
                <div class="border-b border-slate-800 pb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                            <span>🎮</span>
                            <span>Interactive "Try It Out" API Console</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-1">Send live requests directly from your browser to test endpoints and response times.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 animate-pulse">
                        Live Tester Ready
                    </span>
                </div>

                <div class="glass-panel rounded-3xl p-6 sm:p-8 space-y-6">
                    <!-- Endpoint Selector -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-bold text-slate-300 block mb-1">Target Endpoint</label>
                            <select x-model="consoleEndpoint" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-white p-2.5 font-mono">
                                <option value="/auth/me">GET /auth/me (Profile & Stats)</option>
                                <option value="/mrus">GET /mrus (All MRU Workspaces)</option>
                                <option value="/bills?status=pending">GET /bills?status=pending</option>
                                <option value="/automation/queue">GET /automation/queue</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="text-xs font-bold text-slate-300 block mb-1">API Key / Bearer Token</label>
                            <input type="text" x-model="consoleApiKey" placeholder="Paste your nbp_live_... key here" class="w-full text-xs rounded-xl border-slate-800 bg-slate-900 text-cyan-300 font-mono p-2.5">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <div class="text-[11px] text-slate-400 font-mono">
                            URL: <span class="text-white" x-text="'{{ $baseUrl }}' + consoleEndpoint"></span>
                        </div>
                        <button @click="sendLiveRequest()" :disabled="consoleLoading" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-cyan-500 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-black shadow-lg shadow-brand-500/20 transition cursor-pointer flex items-center gap-2">
                            <span x-show="!consoleLoading">🚀 Send Live Request</span>
                            <span x-show="consoleLoading" x-cloak>⏳ Sending...</span>
                        </button>
                    </div>

                    <!-- Response Output Window -->
                    <div class="space-y-2 pt-2">
                        <div class="flex items-center justify-between text-xs font-mono text-slate-400">
                            <span>Response Output</span>
                            <span x-show="consoleStatusCode" x-cloak :class="consoleStatusCode === 200 ? 'text-emerald-400' : 'text-rose-400'" class="font-bold">
                                Status: <span x-text="consoleStatusCode"></span> (<span x-text="consoleLatency + 'ms'"></span>)
                            </span>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs overflow-x-auto max-h-96 text-slate-200">
                            <pre x-text="consoleResponse || 'Click \'Send Live Request\' above to test this endpoint...'"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- AI AGENT & COPILOT SETUP SECTION -->
            <section id="ai-agent-guide" class="space-y-6 pt-6">
                <div class="border-b border-purple-900/50 pb-4">
                    <h2 class="text-2xl font-black text-white tracking-tight flex items-center gap-2">
                        <span>🤖</span>
                        <span>AI Agent & Copilot Tool-Calling Setup</span>
                    </h2>
                    <p class="text-xs text-purple-300 mt-1">Connect Claude, ChatGPT (Custom GPTs / Actions), Cursor, or LangChain directly to this API without writing custom backend code.</p>
                </div>

                <div class="glass-panel p-6 sm:p-8 rounded-3xl space-y-6 border border-purple-500/20">
                    <div>
                        <h3 class="text-sm font-bold text-white mb-1">1. OpenAPI 3.0 Machine Specification</h3>
                        <p class="text-xs text-slate-400 mb-3">Copy this URL directly into ChatGPT Custom Actions or Claude Tool Use:</p>
                        <div class="p-3 rounded-xl bg-black/60 border border-purple-500/30 flex items-center justify-between font-mono text-xs text-purple-300">
                            <span>{{ $openapiUrl }}</span>
                            <button @click="copyCode('{{ $openapiUrl }}')" class="text-slate-400 hover:text-white font-sans font-bold">Copy URL</button>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-white">2. AI Copilot System Prompt / Instructions</h3>
                            <button @click="copyCode($refs.promptBox.innerText)" class="text-xs font-bold text-purple-400 hover:text-purple-300">📋 Copy Prompt</button>
                        </div>
                        <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto max-h-80">
                            <pre x-ref="promptBox" class="whitespace-pre-wrap">You are an intelligent NBPDCL Electricity Billing Copilot.
You have access to the NBPDCL REST API to assist meter readers and billing agencies in Bihar.

Your capabilities:
1. Query active MRU workspaces and billing cycles using GET /bills.
2. Filter accounts by pending, doubt, critical, or submitted.
3. Review consumer meter readings and update statuses using PATCH /bills/review.
4. Record structured reasons:
   - For Doubt: PREMISES_LOCKED, SUSPICIOUS_READING, SUSPICIOUS_AMOUNT, PREV_MONTH_MISMATCH, OWNER_RECHECK_REQUEST.
   - For Critical: METER_BURNT_DEAD, METER_TAMPERED_BYPASS, METER_MISSING_STOLEN, PREMISES_DEMOLISHED.

Always confirm consumer name, CA number, and previous reading before proposing status submissions.</pre>
                        </div>
                    </div>

                    <div class="p-4 rounded-2xl bg-purple-950/20 border border-purple-800/40 text-xs text-purple-200 space-y-2">
                        <span class="font-bold block">💡 Example AI Prompt Seeds to Test With Your Agent:</span>
                        <ul class="list-disc list-inside space-y-1 text-slate-300">
                            <li><em>"Show me all pending bills in MRU 0244 for April 2026 sorted by highest arrears."</em></li>
                            <li><em>"Consumer 10230063090 had a locked gate today. Mark them as Doubt with reason PREMISES_LOCKED."</em></li>
                            <li><em>"Give me a summary of how many meters were marked as METER_BURNT_DEAD this month."</em></li>
                        </ul>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-950 py-8 text-xs text-slate-500 text-center">
        <div class="max-w-7xl mx-auto px-4">
            &copy; {{ date('Y') }} NBPDCL / BSPHCL Billing Platform • Universal Automation REST API v1.0
        </div>
    </footer>

    <!-- Alpine.js Application Logic -->
    <script>
        function devPortal() {
            return {
                activeLang: 'python',
                searchQuery: '',
                consoleEndpoint: '/auth/me',
                consoleApiKey: '{{ $activeKey ? session("new_api_key")["plain_text_token"] ?? "nbp_live_NVdxdbAW6UImuxuyFDzEr5xGXq3APyt5W7kU8O10" : "" }}',
                consoleResponse: null,
                consoleStatusCode: null,
                consoleLatency: null,
                consoleLoading: false,

                copyCode(text) {
                    navigator.clipboard.writeText(text);
                    alert('Copied to clipboard!');
                },

                async sendLiveRequest() {
                    this.consoleLoading = true;
                    this.consoleResponse = 'Dispatching HTTP request...';
                    this.consoleStatusCode = null;
                    const startTime = performance.now();

                    try {
                        const targetUrl = '{{ $baseUrl }}' + this.consoleEndpoint;
                        const headers = {
                            'Accept': 'application/json'
                        };
                        if (this.consoleApiKey) {
                            headers['Authorization'] = 'Bearer ' + this.consoleApiKey.trim();
                        }

                        const res = await fetch(targetUrl, { headers });
                        const endTime = performance.now();
                        this.consoleLatency = Math.round(endTime - startTime);
                        this.consoleStatusCode = res.status;

                        const json = await res.json();
                        this.consoleResponse = JSON.stringify(json, null, 2);
                    } catch (err) {
                        this.consoleStatusCode = 500;
                        this.consoleResponse = 'Network Error: ' + err.message;
                    } finally {
                        this.consoleLoading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
