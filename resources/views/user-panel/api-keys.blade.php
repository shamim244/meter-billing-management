<x-user-panel-layout>
    <x-slot name="header">
        API Keys & Field Automation Tokens
    </x-slot>

    <div x-data="{
        createModalOpen: false,
        revokeModalOpen: false,
        revokeKeyId: null,
        revokeKeyName: '',
        copied: false,
        copySecret(text) {
            navigator.clipboard.writeText(text);
            this.copied = true;
            setTimeout(() => this.copied = false, 2500);
        },
        openRevoke(id, name) {
            this.revokeKeyId = id;
            this.revokeKeyName = name;
            this.revokeModalOpen = true;
        }
    }" class="space-y-8">

        <!-- Header Hero & Action Banner -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border border-slate-200/80 dark:border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-3xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-2xl flex items-center justify-center shadow-lg shadow-brand-500/20 font-mono shrink-0">
                        🔑
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">
                                API Keys & Integrations
                            </h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-cyan-100 dark:bg-cyan-950/70 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/80">
                                REST API v1
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-xl">
                            Generate and manage secret bearer keys for your Python ADB field scripts, Android/Flutter readers, and external automation systems.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('docs.api') }}" 
                       target="_blank"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold transition shadow-sm">
                        <span>📖</span>
                        <span>API Docs & Console ↗</span>
                    </a>

                    <button @click="createModalOpen = true" 
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-brand-600 to-cyan-500 hover:from-brand-500 hover:to-cyan-400 text-white text-xs font-bold shadow-lg shadow-brand-500/25 transition-all duration-200 transform active:scale-95 cursor-pointer">
                        <span class="text-base leading-none">＋</span>
                        <span>Generate New Key</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Success & Error Session Alerts -->
        @if (session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-300 text-xs flex items-center gap-3 shadow-sm">
                <span class="text-base">✅</span>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-300 text-xs flex items-center gap-3 shadow-sm">
                <span class="text-base">⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- MARKET STANDARD ONE-TIME SECRET REVEAL BANNER -->
        @if (session('new_api_key'))
            <div class="p-6 sm:p-8 rounded-3xl bg-amber-500/10 border-2 border-amber-500/40 dark:border-amber-500/50 shadow-2xl relative overflow-hidden">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xl shrink-0 font-bold">
                        ⚠️
                    </div>
                    <div class="space-y-4 flex-1">
                        <div>
                            <h3 class="text-base font-extrabold text-amber-900 dark:text-amber-300">
                                Save Your Secret API Key Now!
                            </h3>
                            <p class="text-xs text-amber-800 dark:text-amber-400/90 mt-1">
                                For your security, this secret token will <strong>never be shown again</strong>. Copy and store it immediately in your Python script or password manager.
                            </p>
                        </div>

                        <!-- Key Details & Code Display Box -->
                        <div class="p-4 rounded-2xl bg-white dark:bg-slate-950 border border-amber-500/30 dark:border-slate-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 shadow-inner">
                            <div class="font-mono text-xs font-bold text-slate-900 dark:text-cyan-300 select-all break-all pr-2">
                                {{ session('new_api_key')['plain_text_token'] }}
                            </div>
                            <button @click="copySecret('{{ session('new_api_key')['plain_text_token'] }}')"
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-black shrink-0 transition-colors shadow-sm cursor-pointer">
                                <span x-show="!copied">📋 Copy Key</span>
                                <span x-show="copied" x-cloak class="text-emerald-950 font-black">✓ Copied!</span>
                            </button>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-[11px] text-slate-600 dark:text-slate-400">
                            <div><strong>Name:</strong> {{ session('new_api_key')['name'] }}</div>
                            <div>•</div>
                            <div><strong>Prefix:</strong> <code class="font-mono text-cyan-600 dark:text-cyan-400">{{ session('new_api_key')['key_prefix'] }}...</code></div>
                            <div>•</div>
                            <div><strong>Expires:</strong> {{ session('new_api_key')['expires_at'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Key Usage Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500 block">Total Issued</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white font-mono mt-1">{{ $stats['total'] }}</div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Lifetime generated</span>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 block">Active Keys</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono mt-1">{{ $stats['active'] }}</div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Ready for API requests</span>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-500 dark:text-rose-400 block">Expired Keys</span>
                <div class="text-2xl font-black text-rose-500 dark:text-rose-400 font-mono mt-1">{{ $stats['expired'] }}</div>
                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 block">Past expiration date</span>
            </div>

            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-brand-600 dark:text-cyan-400 block">Last Active</span>
                <div class="text-xs font-bold text-slate-800 dark:text-slate-200 mt-2 truncate">
                    {{ $stats['last_used'] && $stats['last_used']->last_used_at ? $stats['last_used']->last_used_at->diffForHumans() : 'Never used' }}
                </div>
                <span class="text-[10px] font-mono text-slate-400 mt-1 block truncate">
                    {{ $stats['last_used'] && $stats['last_used']->last_ip ? 'IP: '.$stats['last_used']->last_ip : 'Awaiting first call' }}
                </span>
            </div>
        </div>

        <!-- Active API Keys Table -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Active API Keys</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Keys permitted to query, update readings, and batch-sync data on your behalf.</p>
                </div>
                <span class="text-xs font-mono font-bold text-slate-400">
                    {{ $apiKeys->count() }} {{ Str::plural('Key', $apiKeys->count()) }}
                </span>
            </div>

            @if($apiKeys->isEmpty())
                <div class="p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 flex items-center justify-center text-2xl mx-auto">
                        🔑
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">No API Keys Found</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">
                            Generate your first API key to connect your Phone ADB automation script, Flutter field app, or custom client.
                        </p>
                    </div>
                    <button @click="createModalOpen = true" 
                            type="button" 
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition shadow-md cursor-pointer">
                        <span>＋ Generate First Key</span>
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950/60 border-b border-slate-100 dark:border-slate-800 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                            <tr>
                                <th class="py-3.5 px-6">Name / Purpose</th>
                                <th class="py-3.5 px-6">Key Token</th>
                                <th class="py-3.5 px-6">Status</th>
                                <th class="py-3.5 px-6">Expires</th>
                                <th class="py-3.5 px-6">Last Used</th>
                                <th class="py-3.5 px-6">Created</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            @foreach($apiKeys as $key)
                                @php
                                    $isExpired = $key->expires_at && $key->expires_at->isPast();
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors">
                                    <!-- Name -->
                                    <td class="py-4 px-6">
                                        <div class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                            <span>💻</span>
                                            <span>{{ $key->name }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-400 mt-0.5">
                                            {{ in_array('*', $key->abilities ?? ['*'], true) ? 'Full Access (*)' : implode(', ', $key->abilities ?? []) }}
                                        </div>
                                    </td>

                                    <!-- Key Prefix Masked -->
                                    <td class="py-4 px-6">
                                        <code class="font-mono text-xs font-bold text-brand-600 dark:text-cyan-400 bg-brand-50 dark:bg-cyan-950/40 border border-brand-200/50 dark:border-cyan-800/40 px-2 py-0.5 rounded-md">
                                            {{ $key->key_prefix }}••••••••
                                        </code>
                                    </td>

                                    <!-- Status -->
                                    <td class="py-4 px-6">
                                        @if($isExpired)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/50">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Expired
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Active
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Expires -->
                                    <td class="py-4 px-6">
                                        @if($key->expires_at)
                                            <div class="font-medium {{ $isExpired ? 'text-rose-500 font-bold' : 'text-slate-700 dark:text-slate-300' }}">
                                                {{ $key->expires_at->diffForHumans() }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                {{ $key->expires_at->format('M d, Y') }}
                                            </div>
                                        @else
                                            <span class="text-slate-500 dark:text-slate-400 font-semibold">Never Expires</span>
                                        @endif
                                    </td>

                                    <!-- Last Used -->
                                    <td class="py-4 px-6">
                                        @if($key->last_used_at)
                                            <div class="font-medium text-slate-700 dark:text-slate-300">
                                                {{ $key->last_used_at->diffForHumans() }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                                {{ $key->last_ip ? 'IP: '.$key->last_ip : 'Recorded' }}
                                            </div>
                                        @else
                                            <span class="text-slate-400 italic">Never</span>
                                        @endif
                                    </td>

                                    <!-- Created -->
                                    <td class="py-4 px-6 font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ $key->created_at ? $key->created_at->format('M d, Y') : 'N/A' }}
                                    </td>

                                    <!-- Revoke Action -->
                                    <td class="py-4 px-6 text-right">
                                        <button @click="openRevoke({{ $key->id }}, '{{ addslashes($key->name) }}')"
                                                type="button"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 text-rose-600 dark:text-rose-400 font-bold text-xs transition border border-rose-200/60 dark:border-rose-800/40 cursor-pointer">
                                            <span>🗑️ Revoke</span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

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

        <!-- MODAL 1: GENERATE NEW KEY -->
        <div x-show="createModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.away="createModalOpen = false"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl space-y-6 relative">
                
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-brand-500/10 text-brand-600 dark:text-cyan-400 flex items-center justify-center text-lg font-bold">
                            🔑
                        </div>
                        <div>
                            <h2 class="text-base font-black text-slate-900 dark:text-white">Generate Secret API Key</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Set key identity and expiration timeframe.</p>
                        </div>
                    </div>
                    <button @click="createModalOpen = false" type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                        ✕
                    </button>
                </div>

                <form method="POST" action="{{ route('user-panel.api-keys.store') }}" class="space-y-5">
                    @csrf

                    <!-- Name Field -->
                    <div>
                        <label for="name" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                            Key Name / Device Description <span class="text-rose-500">*</span>
                        </label>
                        <input id="name" 
                               name="name" 
                               type="text" 
                               placeholder="e.g. Python ADB Tool, Field Phone A, Office Laptop"
                               required 
                               class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-3 font-medium">
                        <p class="text-[11px] text-slate-400 mt-1">Identifies which tool or phone is using this key.</p>
                    </div>

                    <!-- Expiration Duration -->
                    <div>
                        <label for="duration" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">
                            Expiration Timeframe <span class="text-rose-500">*</span>
                        </label>
                        <select id="duration" 
                                name="duration" 
                                class="w-full text-xs rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-brand-500 p-3 font-semibold cursor-pointer">
                            <option value="1_day">⚡ 1 Day (Quick Field Test / Temporary)</option>
                            <option value="7_days">📅 7 Days (1 Week)</option>
                            <option value="30_days" selected>🗓️ 30 Days (1 Month — Recommended)</option>
                            <option value="90_days">📆 90 Days (Quarterly / 3 Months)</option>
                            <option value="365_days">⏳ 365 Days (1 Full Year)</option>
                            <option value="never">♾️ Never Expires</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Automatic revocation after this date protects your account if a phone is lost.</p>
                    </div>

                    <!-- Abilities preset -->
                    <div>
                        <span class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1">Permission Scope</span>
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/70 dark:border-slate-700/60 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-900 dark:text-white">Full Access (*)</div>
                                <div class="text-[10px] text-slate-400">Can read MRUs, query queue, submit reviews, and batch-sync readings.</div>
                            </div>
                            <span class="text-emerald-500 font-bold text-xs">✓ Included</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button @click="createModalOpen = false" 
                                type="button" 
                                class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-black shadow-lg shadow-brand-500/20 transition cursor-pointer">
                            Generate Secret Key →
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL 2: CONFIRM REVOCATION -->
        <div x-show="revokeModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <div @click.away="revokeModalOpen = false"
                 class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
                
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-500 flex items-center justify-center text-xl font-bold">
                    🗑️
                </div>

                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Revoke API Key?</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Are you sure you want to permanently revoke <strong class="text-slate-900 dark:text-white" x-text="revokeKeyName"></strong>?
                    </p>
                    <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900 text-rose-700 dark:text-rose-300 text-[11px] mt-3">
                        ⚠️ Any Python script, phone tool, or background job currently using this key will immediately be denied access (401 Unauthorized).
                    </div>
                </div>

                <form :action="'{{ url('/user-panel/api-keys') }}/' + revokeKeyId" method="POST" class="flex items-center justify-end gap-3 pt-2">
                    @csrf
                    @method('DELETE')

                    <button @click="revokeModalOpen = false" 
                            type="button" 
                            class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 text-xs font-bold transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-black shadow-lg shadow-rose-600/20 transition cursor-pointer">
                        Yes, Revoke Key
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-user-panel-layout>
