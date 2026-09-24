<x-admin-layout>
    <x-slot name="header">
        API & Field Automation Control Hub
    </x-slot>

    <div x-data="{
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'analytics',
        apiMaster: {{ $settings['api_master_enabled'] ? 'true' : 'false' }},
        userKeys: {{ $settings['user_keys_enabled'] ? 'true' : 'false' }},
        featureAutomation: {{ $settings['feature_automation_enabled'] ? 'true' : 'false' }},
        featureMobileSync: {{ $settings['feature_mobile_sync_enabled'] ? 'true' : 'false' }},
        featureBatchSync: {{ $settings['feature_batch_sync_enabled'] ? 'true' : 'false' }},
        featureConsumerUpdates: {{ $settings['feature_consumer_updates_enabled'] ? 'true' : 'false' }},
        publicDocs: {{ $settings['public_docs_enabled'] ? 'true' : 'false' }},
        rateLimiting: {{ $settings['rate_limiting_enabled'] ? 'true' : 'false' }},
        allowPermanent: {{ $settings['allow_permanent_keys'] ? 'true' : 'false' }},
        general: {{ (int) $settings['general_per_minute'] }},
        review: {{ (int) $settings['review_per_minute'] }},
        batch: {{ (int) $settings['batch_per_minute'] }},
        login: {{ (int) $settings['login_per_minute'] }},
        openapi: {{ (int) $settings['openapi_per_minute'] }},
        maxKeys: {{ (int) $settings['max_keys_per_user'] }},
        defaultLifetime: {{ (int) $settings['default_key_lifetime_days'] }},
        confirmReset: false,
        confirmClearAnalytics: false,
        revokingKey: null
    }" class="space-y-8">

        <!-- Status Notification Banner -->
        @if (session('status'))
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 text-xs shadow-lg flex items-center justify-between gap-3 animate-fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="text-base">✅</span>
                    <span class="font-semibold">{{ session('status') }}</span>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs shadow-lg space-y-1">
                <div class="font-bold flex items-center gap-2 text-rose-200">
                    <span>⚠️</span> Validation Errors:
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-rose-300/90 pl-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Top Overview & Info Card -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                            Enterprise Control Hub
                        </span>
                        <span class="text-xs text-slate-500">•</span>
                        <span class="text-xs text-slate-400 font-medium">NBPDCL REST API v1</span>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                        <span>⚡</span> API & Automation Control Hub
                    </h1>
                    <p class="text-xs text-slate-400 mt-1 max-w-2xl leading-relaxed">
                        Comprehensive administrative cockpit for API traffic analytics, dynamic rate limiting, granular feature switches, security policies, and client key lifecycle management.
                    </p>
                </div>

                <!-- Top Telemetry Metrics -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Today</div>
                        <div class="text-xl font-black text-cyan-400 font-mono mt-0.5">{{ number_format($requestsToday) }}</div>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">This Month</div>
                        <div class="text-xl font-black text-indigo-400 font-mono mt-0.5">{{ number_format($requestsThisMonth) }}</div>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Keys</div>
                        <div class="text-xl font-black text-emerald-400 font-mono mt-0.5">{{ $stats['active_keys'] }}</div>
                    </div>
                    <div class="bg-slate-900/90 p-3 rounded-2xl border border-slate-800 text-center">
                        <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Avg Latency</div>
                        <div class="text-xl font-black text-white font-mono mt-0.5">{{ $avgLatencyMs }}<span class="text-xs font-normal text-slate-400">ms</span></div>
                    </div>
                </div>
            </div>

            <!-- Tab Switcher Navigation -->
            <div class="flex items-center gap-2 mt-8 pt-6 border-t border-slate-800/80 overflow-x-auto pb-1 text-xs">
                <button type="button" @click="activeTab = 'analytics'"
                    class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'analytics' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
                    <span>📊</span>
                    <span>Traffic Analytics</span>
                </button>

                <button type="button" @click="activeTab = 'features'"
                    class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'features' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
                    <span>🎛️</span>
                    <span>Feature Switches</span>
                </button>

                <button type="button" @click="activeTab = 'ratelimits'"
                    class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'ratelimits' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
                    <span>⏱️</span>
                    <span>Rate Limiting</span>
                </button>

                <button type="button" @click="activeTab = 'policies'"
                    class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'policies' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
                    <span>🔑</span>
                    <span>Security Policies</span>
                </button>

                <button type="button" @click="activeTab = 'keys'"
                    class="px-4 py-2.5 rounded-xl font-bold transition flex items-center gap-2 whitespace-nowrap"
                    :class="activeTab === 'keys' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900'">
                    <span>📋</span>
                    <span>Issued Keys Ledger</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-800 text-slate-300 font-mono">{{ $stats['total_keys'] }}</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: TRAFFIC ANALYTICS                   -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'analytics'" class="space-y-6" x-cloak>
            <!-- 14-Day Timeline Bar Chart -->
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span>📈</span> Daily Request Volume (Last 14 Days)
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Track automated request trends and surge patterns.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-mono text-slate-400">Total Recorded: <strong class="text-white">{{ number_format($totalRequests) }}</strong></span>
                    </div>
                </div>

                @php
                    $maxCount = max(1, max(array_column($dailyTimeline, 'count')));
                @endphp

                <!-- Visual Bar Timeline -->
                <div class="h-44 flex items-end gap-2 pt-6 pb-2 px-2 border-b border-slate-800 overflow-x-auto">
                    @foreach ($dailyTimeline as $day)
                        @php
                            $heightPercent = max(6, (int) round(($day['count'] / $maxCount) * 100));
                        @endphp
                        <div class="flex-1 min-w-[28px] flex flex-col items-center gap-1 group relative">
                            <!-- Tooltip -->
                            <div class="absolute -top-9 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none bg-slate-900 border border-slate-700 text-white text-[10px] font-mono px-2 py-1 rounded-lg shadow-xl whitespace-nowrap z-20">
                                {{ $day['label'] }}: {{ $day['count'] }} requests
                            </div>
                            <!-- Bar -->
                            <div class="w-full rounded-t-lg bg-gradient-to-t from-indigo-600 to-cyan-400 group-hover:from-indigo-500 group-hover:to-cyan-300 transition shadow-sm"
                                style="height: {{ $heightPercent }}%;"></div>
                            <!-- Label -->
                            <span class="text-[9px] font-mono text-slate-500 group-hover:text-slate-300 transition">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Distribution Breakdown Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Endpoint Groups Distribution -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <h4 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>🧭</span> Requests by Endpoint Group
                    </h4>

                    <div class="space-y-3 pt-2">
                        @php
                            $groups = [
                                'reads' => ['label' => 'General Reads & Queue', 'color' => 'bg-cyan-500'],
                                'reviews' => ['label' => 'Review Submissions', 'color' => 'bg-amber-500'],
                                'batch' => ['label' => 'Offline Batch Sync', 'color' => 'bg-purple-500'],
                                'automation' => ['label' => 'Python ADB Tool', 'color' => 'bg-emerald-500'],
                                'sync' => ['label' => 'Flutter Mobile Sync', 'color' => 'bg-blue-500'],
                                'auth' => ['label' => 'Auth & Login', 'color' => 'bg-rose-500'],
                                'docs' => ['label' => 'OpenAPI & Docs', 'color' => 'bg-teal-500'],
                            ];
                        @endphp

                        @foreach ($groups as $key => $meta)
                            @php
                                $cnt = $groupCounts[$key] ?? 0;
                                $pct = $totalRequests > 0 ? round(($cnt / $totalRequests) * 100, 1) : 0;
                            @endphp
                            <div class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-300 font-medium">{{ $meta['label'] }}</span>
                                    <span class="font-mono text-slate-400">{{ number_format($cnt) }} <span class="text-[10px] text-slate-500">({{ $pct }}%)</span></span>
                                </div>
                                <div class="w-full h-2 bg-slate-900 rounded-full overflow-hidden">
                                    <div class="h-full {{ $meta['color'] }} rounded-full" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- HTTP Status Health -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>🚦</span> HTTP Response Status Code Health
                        </h4>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-400">2xx Success</span>
                                <span class="text-base">🟢</span>
                            </div>
                            <div class="text-xl font-black text-white font-mono mt-2">{{ number_format($status2xx) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Processed successfully</div>
                        </div>

                        <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-amber-400">429 Throttled</span>
                                <span class="text-base">⏳</span>
                            </div>
                            <div class="text-xl font-black text-amber-300 font-mono mt-2">{{ number_format($status429) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Rate limit triggered</div>
                        </div>

                        <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-sky-400">4xx Client Errors</span>
                                <span class="text-base">🟡</span>
                            </div>
                            <div class="text-xl font-black text-white font-mono mt-2">{{ number_format($status4xx) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Bad auth / validation</div>
                        </div>

                        <div class="bg-slate-900/80 p-4 rounded-2xl border border-slate-800">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-rose-400">5xx Server Errors</span>
                                <span class="text-base">🔴</span>
                            </div>
                            <div class="text-xl font-black text-rose-400 font-mono mt-2">{{ number_format($status5xx) }}</div>
                            <div class="text-[10px] text-slate-500 mt-1">Internal exceptions</div>
                        </div>
                    </div>

                    <!-- Telemetry Maintenance Button -->
                    <div class="pt-2 flex justify-end">
                        <button type="button" @click="confirmClearAnalytics = true" class="text-xs text-slate-500 hover:text-rose-400 transition">
                            🧹 Purge Historical Logs
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recent Requests Activity Table -->
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                <h4 class="text-sm font-bold text-white flex items-center gap-2">
                    <span>⚡</span> Recent Request Stream (Live Telemetry)
                </h4>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="text-[10px] uppercase font-bold text-slate-500 bg-slate-900/60 rounded-xl">
                            <tr>
                                <th class="p-3 rounded-l-xl">Status</th>
                                <th class="p-3">Method</th>
                                <th class="p-3">Endpoint Path</th>
                                <th class="p-3">User / Key</th>
                                <th class="p-3">Latency</th>
                                <th class="p-3">IP Address</th>
                                <th class="p-3 rounded-r-xl">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            @forelse ($recentLogs as $log)
                                <tr class="hover:bg-slate-900/40 transition">
                                    <td class="p-3">
                                        @if ($log->status_code >= 200 && $log->status_code < 300)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">{{ $log->status_code }}</span>
                                        @elseif ($log->status_code === 429)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/15 text-amber-400 border border-amber-500/30">{{ $log->status_code }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-500/15 text-rose-400 border border-rose-500/30">{{ $log->status_code }}</span>
                                        @endif
                                    </td>
                                    <td class="p-3 font-bold text-slate-200">{{ $log->method }}</td>
                                    <td class="p-3 text-cyan-300 font-medium">{{ $log->path }}</td>
                                    <td class="p-3 text-slate-400 font-sans">
                                        {{ $log->user ? $log->user->name : ($log->apiKey ? $log->apiKey->name : 'Unauthenticated') }}
                                    </td>
                                    <td class="p-3 text-slate-300">{{ $log->duration_ms }}ms</td>
                                    <td class="p-3 text-slate-500">{{ $log->ip_address }}</td>
                                    <td class="p-3 text-slate-500 font-sans">{{ $log->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-6 text-center text-slate-500 font-sans">
                                        No API requests logged yet. Calls made to <code class="text-cyan-400">/api/v1/*</code> will stream here automatically.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SHARED CONFIGURATION FORM                  -->
        <!-- (Handles Tabs 2, 3, 4)                     -->
        <!-- ========================================== -->
        <form action="{{ route('admin.api_hub.settings.update') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="active_tab" :value="activeTab">

            <!-- Preserved values to avoid losing settings when saving from different tabs -->
            <input type="hidden" name="api_master_enabled" :value="apiMaster ? '1' : ''" :disabled="!apiMaster">
            <input type="hidden" name="user_keys_enabled" :value="userKeys ? '1' : ''" :disabled="!userKeys">
            <input type="hidden" name="feature_automation_enabled" :value="featureAutomation ? '1' : ''" :disabled="!featureAutomation">
            <input type="hidden" name="feature_mobile_sync_enabled" :value="featureMobileSync ? '1' : ''" :disabled="!featureMobileSync">
            <input type="hidden" name="feature_batch_sync_enabled" :value="featureBatchSync ? '1' : ''" :disabled="!featureBatchSync">
            <input type="hidden" name="feature_consumer_updates_enabled" :value="featureConsumerUpdates ? '1' : ''" :disabled="!featureConsumerUpdates">
            <input type="hidden" name="public_docs_enabled" :value="publicDocs ? '1' : ''" :disabled="!publicDocs">
            <input type="hidden" name="rate_limiting_enabled" :value="rateLimiting ? '1' : ''" :disabled="!rateLimiting">
            <input type="hidden" name="allow_permanent_keys" :value="allowPermanent ? '1' : ''" :disabled="!allowPermanent">

            <!-- Numeric values always submitted -->
            <input type="hidden" name="general_per_minute" :value="general">
            <input type="hidden" name="review_per_minute" :value="review">
            <input type="hidden" name="batch_per_minute" :value="batch">
            <input type="hidden" name="login_per_minute" :value="login">
            <input type="hidden" name="openapi_per_minute" :value="openapi">
            <input type="hidden" name="max_keys_per_user" :value="maxKeys">
            <input type="hidden" name="default_key_lifetime_days" :value="defaultLifetime">

            <!-- ========================================== -->
            <!-- TAB 2: FEATURE TOGGLES & ENDPOINT SWITCHES -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'features'" class="space-y-6" x-cloak>
                <!-- Master API Kill Switch -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg" :class="apiMaster ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'">
                            <span x-text="apiMaster ? '🌐' : '🛑'"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-white">Global API Master Switch</h3>
                                <span x-show="apiMaster" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Online</span>
                                <span x-show="!apiMaster" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">Offline</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Master kill-switch. When disabled, all endpoints under <code class="text-rose-400 bg-slate-900 px-1 py-0.5 rounded">/api/v1/*</code> return HTTP 503 Service Unavailable.
                            </p>
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="apiMaster = !apiMaster" class="relative inline-flex items-center cursor-pointer">
                            <div class="w-14 h-7 bg-slate-800 rounded-full transition-colors duration-200" :class="apiMaster ? 'bg-emerald-600' : 'bg-slate-800'">
                                <div class="w-6 h-6 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="apiMaster ? 'translate-x-7' : 'translate-x-1'"></div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Granular Capability Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- 1. Python ADB Tool Endpoints -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-400 flex items-center justify-center font-bold text-sm border border-emerald-500/30">🤖</span>
                                <span class="text-[10px] font-mono" :class="featureAutomation ? 'text-emerald-400' : 'text-slate-500'" x-text="featureAutomation ? 'ENABLED' : 'PAUSED'"></span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Python ADB Automation Tool</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Controls external ADB reading agents querying queue and updating verification status.
                            </p>
                            <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                                <div>/api/v1/automation/queue</div>
                                <div>/api/v1/automation/update-status</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Automation Access</span>
                            <button type="button" @click="featureAutomation = !featureAutomation" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureAutomation ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureAutomation ? 'translate-x-5' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Mobile Offline Sync -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-blue-500/15 text-blue-400 flex items-center justify-center font-bold text-sm border border-blue-500/30">📱</span>
                                <span class="text-[10px] font-mono" :class="featureMobileSync ? 'text-emerald-400' : 'text-slate-500'" x-text="featureMobileSync ? 'ENABLED' : 'PAUSED'"></span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Flutter Mobile Offline Sync</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Allows field mobile readers to download MRU packages and batch-sync offline meter records.
                            </p>
                            <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                                <div>/api/v1/sync/mrus/{id}/download</div>
                                <div>/api/v1/sync/readings/batch</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Mobile Sync Access</span>
                            <button type="button" @click="featureMobileSync = !featureMobileSync" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureMobileSync ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureMobileSync ? 'translate-x-5' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 3. Bulk Batch Sync -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center font-bold text-sm border border-purple-500/30">📦</span>
                                <span class="text-[10px] font-mono" :class="featureBatchSync ? 'text-emerald-400' : 'text-slate-500'" x-text="featureBatchSync ? 'ENABLED' : 'PAUSED'"></span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Bulk Batch Uploads</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Controls multi-record mass uploads. Useful to pause during heavy server maintenance.
                            </p>
                            <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                                <div>/api/v1/bills/batch-sync</div>
                                <div>Payloads up to 100 records</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Batch Upload Access</span>
                            <button type="button" @click="featureBatchSync = !featureBatchSync" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureBatchSync ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureBatchSync ? 'translate-x-5' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 4. Direct Consumer Updates -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-sm border border-amber-500/30">✍️</span>
                                <span class="text-[10px] font-mono" :class="featureConsumerUpdates ? 'text-emerald-400' : 'text-slate-500'" x-text="featureConsumerUpdates ? 'ENABLED' : 'PAUSED'"></span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Single Reading Updates</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Direct consumer reading adjustment endpoint from third-party webhook integrations.
                            </p>
                            <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                                <div>POST /api/v1/consumers/reading</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Single Update Access</span>
                            <button type="button" @click="featureConsumerUpdates = !featureConsumerUpdates" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="featureConsumerUpdates ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="featureConsumerUpdates ? 'translate-x-5' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 5. User Self-Service Key Creation -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-bold text-sm border border-indigo-500/30">🔑</span>
                                <span class="text-[10px] font-mono" :class="userKeys ? 'text-emerald-400' : 'text-slate-500'" x-text="userKeys ? 'ALLOWED' : 'LOCKED'"></span>
                            </div>
                            <h4 class="text-sm font-bold text-white">User Panel Key Generation</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                When enabled, standard users and agents can generate API keys directly in their User Control Panel.
                            </p>
                            <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                                <div>/user-panel/api-keys</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Allow Key Creation</span>
                            <button type="button" @click="userKeys = !userKeys" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="userKeys ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="userKeys ? 'translate-x-5' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- 6. Public Docs & OpenAPI Spec -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between hover:border-slate-700 transition">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-400 flex items-center justify-center font-bold text-sm border border-teal-500/30">📖</span>
                                <span class="text-[10px] font-mono" :class="publicDocs ? 'text-emerald-400' : 'text-slate-500'" x-text="publicDocs ? 'PUBLIC' : 'HIDDEN'"></span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Public Developer Portal & OpenAPI</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Controls public availability of the OpenAPI 3.0 schema and interactive Developer Portal.
                            </p>
                            <div class="mt-2 text-[11px] font-mono text-slate-500 bg-slate-900/80 p-2 rounded-xl border border-slate-800/80 space-y-0.5">
                                <div>/docs/api</div>
                                <div>/api/v1/openapi.json</div>
                            </div>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-300">Public Docs Access</span>
                            <button type="button" @click="publicDocs = !publicDocs" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-11 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="publicDocs ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="publicDocs ? 'translate-x-5' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Save Action Bar -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Feature changes take effect across all workers immediately.
                    </div>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
                        Save Feature Switches
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: RATE LIMITING & THROTTLING TIERS   -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'ratelimits'" class="space-y-6" x-cloak>
                <!-- Master Throttle Switch -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-lg" :class="rateLimiting ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'">
                            <span x-text="rateLimiting ? '🛡️' : '⚠️'"></span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-white">Rate Limiting Enforcement</h3>
                                <span x-show="rateLimiting" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Active</span>
                                <span x-show="!rateLimiting" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-400 border border-rose-500/30">Bypassed</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">
                                When active, excessive requests receive <code class="text-rose-400 bg-slate-900 px-1 py-0.5 rounded">HTTP 429 Too Many Requests</code>. Turn off to grant unlimited throughput.
                            </p>
                        </div>
                    </div>
                    <div>
                        <button type="button" @click="rateLimiting = !rateLimiting" class="relative inline-flex items-center cursor-pointer">
                            <div class="w-14 h-7 bg-slate-800 rounded-full transition-colors duration-200" :class="rateLimiting ? 'bg-emerald-600' : 'bg-slate-800'">
                                <div class="w-6 h-6 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="rateLimiting ? 'translate-x-7' : 'translate-x-1'"></div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Tiers Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- General Reads -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-cyan-500/15 text-cyan-400 flex items-center justify-center font-bold text-sm border border-cyan-500/30">🌐</span>
                                <span class="text-[10px] font-mono text-slate-500">api.general</span>
                            </div>
                            <h4 class="text-sm font-bold text-white">General Reads & Lookups</h4>
                            <p class="text-xs text-slate-400 mt-1">Consumer lookups, MRU cycles, and automation queue reads.</p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-300">Requests / Minute</label>
                                <span class="text-[10px] font-mono text-cyan-400" x-text="'~' + (general / 60).toFixed(1) + ' req/sec'"></span>
                            </div>
                            <input type="number" x-model="general" min="1" max="60000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                        </div>
                    </div>

                    <!-- Review Submissions -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-sm border border-amber-500/30">📝</span>
                                <span class="text-[10px] font-mono text-slate-500">api.review</span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Review Submissions</h4>
                            <p class="text-xs text-slate-400 mt-1">Status changes, ledger updates, and doubt/critical flags.</p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-300">Reviews / Minute</label>
                                <span class="text-[10px] font-mono text-amber-400" x-text="'~' + (review / 60).toFixed(1) + ' rev/sec'"></span>
                            </div>
                            <input type="number" x-model="review" min="1" max="30000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                        </div>
                    </div>

                    <!-- Batch Sync -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-purple-500/15 text-purple-400 flex items-center justify-center font-bold text-sm border border-purple-500/30">📦</span>
                                <span class="text-[10px] font-mono text-slate-500">api.batch</span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Batch Uploads</h4>
                            <p class="text-xs text-slate-400 mt-1">Multi-record offline uploads carrying 50–100 bills each.</p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-300">Batches / Minute</label>
                                <span class="text-[10px] font-mono text-purple-400" x-text="batch + ' batches/min'"></span>
                            </div>
                            <input type="number" x-model="batch" min="1" max="5000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                        </div>
                    </div>

                    <!-- Login Attempts -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-sm border border-rose-500/30">🔐</span>
                                <span class="text-[10px] font-mono text-slate-500">api.login</span>
                            </div>
                            <h4 class="text-sm font-bold text-white">Login Brute-Force Shield</h4>
                            <p class="text-xs text-slate-400 mt-1">Prevents credential dictionary attacks per client IP.</p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-300">Attempts / Minute</label>
                                <span class="text-[10px] font-mono text-rose-400" x-text="login + ' attempts/min'"></span>
                            </div>
                            <input type="number" x-model="login" min="1" max="1000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                        </div>
                    </div>

                    <!-- OpenAPI Spec -->
                    <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-400 flex items-center justify-center font-bold text-sm border border-teal-500/30">🤖</span>
                                <span class="text-[10px] font-mono text-slate-500">api.openapi</span>
                            </div>
                            <h4 class="text-sm font-bold text-white">AI Agent OpenAPI Spec</h4>
                            <p class="text-xs text-slate-400 mt-1">Governs machine spec lookups from ChatGPT, Claude, and IDE tools.</p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-slate-800/80">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-300">Requests / Minute</label>
                                <span class="text-[10px] font-mono text-teal-400" x-text="openapi + ' req/min'"></span>
                            </div>
                            <input type="number" x-model="openapi" min="1" max="10000" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                        </div>
                    </div>

                    <!-- Factory Defaults Card -->
                    <div class="bg-gradient-to-br from-slate-950 to-indigo-950/40 p-6 rounded-3xl border border-indigo-500/20 shadow-xl flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 text-indigo-400 font-bold text-xs uppercase tracking-wider mb-2">
                                <span>🔄</span> Quick Preset
                            </div>
                            <h4 class="text-sm font-bold text-white">Factory Presets</h4>
                            <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                                Standard values (240 / 120 / 30 / 15 / 60) provide 4 req/sec smooth capacity while protecting MySQL.
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-indigo-500/20">
                            <button type="button" @click="confirmReset = true" class="w-full py-2 rounded-xl text-xs font-bold text-rose-400 hover:text-rose-300 bg-rose-950/30 hover:bg-rose-950/60 border border-rose-500/30 transition">
                                Reset All to Factory Defaults
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Save Action Bar -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Changes apply across all clients in real-time.
                    </div>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
                        Save Rate Limit Settings
                    </button>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 4: KEY SECURITY POLICIES               -->
            <!-- ========================================== -->
            <div x-show="activeTab === 'policies'" class="space-y-6" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Max Keys Quota -->
                    <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="w-10 h-10 rounded-2xl bg-indigo-500/15 text-indigo-400 flex items-center justify-center font-bold text-lg border border-indigo-500/30">🔢</span>
                            <div>
                                <h4 class="text-sm font-bold text-white">Max Active Keys Per User</h4>
                                <p class="text-xs text-slate-400 mt-0.5">Limit the number of active API keys a single user can create.</p>
                            </div>
                        </div>
                        <div class="pt-2">
                            <label class="text-xs font-semibold text-slate-300 block mb-1.5">Maximum Key Count</label>
                            <input type="number" x-model="maxKeys" min="1" max="50" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white">
                            <span class="text-[10px] text-slate-500 mt-1 block">Default: 5 active keys. Exceeding requests in User Panel are blocked.</span>
                        </div>
                    </div>

                    <!-- Permanent Keys Policy -->
                    <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4 flex flex-col justify-between">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="w-10 h-10 rounded-2xl bg-cyan-500/15 text-cyan-400 flex items-center justify-center font-bold text-lg border border-cyan-500/30">♾️</span>
                                <div>
                                    <h4 class="text-sm font-bold text-white">Allow Non-Expiring Keys</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">Allow users to select "Never Expire" when generating API keys.</p>
                                </div>
                            </div>
                            <button type="button" @click="allowPermanent = !allowPermanent" class="relative inline-flex items-center cursor-pointer">
                                <div class="w-12 h-6 bg-slate-800 rounded-full transition-colors duration-200" :class="allowPermanent ? 'bg-emerald-600' : 'bg-slate-800'">
                                    <div class="w-5 h-5 bg-white rounded-full transition-transform duration-200 transform translate-y-0.5" :class="allowPermanent ? 'translate-x-6' : 'translate-x-0.5'"></div>
                                </div>
                            </button>
                        </div>
                        <div class="text-[10px] text-slate-500 border-t border-slate-800 pt-3">
                            When turned off, users must pick a finite expiration period (1, 7, 30, 90, or 365 days).
                        </div>
                    </div>
                </div>

                <!-- Save Action Bar -->
                <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl flex items-center justify-between">
                    <div class="text-xs text-slate-400">
                        Security policies apply immediately when agents create or renew keys.
                    </div>
                    <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-lg shadow-emerald-500/20 transition">
                        Save Security Policies
                    </button>
                </div>
            </div>
        </form>

        <!-- ========================================== -->
        <!-- TAB 5: ALL ISSUED API KEYS LEDGER          -->
        <!-- ========================================== -->
        <div x-show="activeTab === 'keys'" class="space-y-6" x-cloak>
            <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-white flex items-center gap-2">
                            <span>📋</span> System-Wide API Keys Ledger
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Audit every issued key across all users with 1-click emergency revocation.</p>
                    </div>

                    <!-- Search Input -->
                    <form method="GET" action="{{ route('admin.api_hub.index') }}" class="flex items-center gap-2">
                        <input type="hidden" name="tab" value="keys">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, prefix, user..."
                            class="bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500">
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 transition">
                            Search
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto pt-2">
                    <table class="w-full text-left text-xs text-slate-300">
                        <thead class="text-[10px] uppercase font-bold text-slate-500 bg-slate-900/60 rounded-xl">
                            <tr>
                                <th class="p-3 rounded-l-xl">Owner / User</th>
                                <th class="p-3">Key Label</th>
                                <th class="p-3">Prefix</th>
                                <th class="p-3">Status</th>
                                <th class="p-3">Created</th>
                                <th class="p-3">Last Active</th>
                                <th class="p-3">Last IP</th>
                                <th class="p-3 text-right rounded-r-xl">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 font-mono">
                            @forelse ($issuedKeys as $key)
                                @php
                                    $isExpired = $key->expires_at && $key->expires_at->isPast();
                                @endphp
                                <tr class="hover:bg-slate-900/40 transition font-sans">
                                    <td class="p-3">
                                        <div class="font-bold text-white">{{ $key->user ? $key->user->name : 'Deleted User' }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono">{{ $key->user ? $key->user->email : '-' }}</div>
                                    </td>
                                    <td class="p-3 font-semibold text-cyan-300">{{ $key->name }}</td>
                                    <td class="p-3 font-mono text-slate-400">{{ $key->key_prefix }}••••</td>
                                    <td class="p-3">
                                        @if ($isExpired)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/15 text-rose-400 border border-rose-500/30">Expired</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">Active</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-slate-400 text-[11px]">{{ $key->created_at?->format('M d, Y') }}</td>
                                    <td class="p-3 text-slate-400 text-[11px]">
                                        {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never used' }}
                                    </td>
                                    <td class="p-3 font-mono text-slate-500 text-[11px]">{{ $key->last_ip ?: '—' }}</td>
                                    <td class="p-3 text-right">
                                        <button type="button" @click="revokingKey = {{ Js::from(['id' => $key->id, 'name' => $key->name, 'user' => $key->user ? $key->user->name : 'User']) }}"
                                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-rose-950/40 hover:bg-rose-900/60 text-rose-400 hover:text-rose-300 border border-rose-500/30 transition">
                                            Revoke
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-6 text-center text-slate-500 font-sans">
                                        No API keys found. When users generate keys, they will appear in this ledger.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($issuedKeys->hasPages())
                    <div class="pt-4 border-t border-slate-800">
                        {{ $issuedKeys->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODALS                                     -->
        <!-- ========================================== -->

        <!-- 1. Reset Settings Modal -->
        <div x-show="confirmReset" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="confirmReset = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-xl border border-rose-500/30 mx-auto">
                    🔄
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-white">Reset API Settings?</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        This will restore all rate limits, feature switches, and key policies back to system factory defaults.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="confirmReset = false" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <form action="{{ route('admin.api_hub.settings.reset') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition shadow-lg shadow-rose-600/30">
                            Yes, Restore Defaults
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 2. Purge Analytics Confirmation Modal -->
        <div x-show="confirmClearAnalytics" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="confirmClearAnalytics = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/15 text-amber-400 flex items-center justify-center font-bold text-xl border border-amber-500/30 mx-auto">
                    🧹
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-white">Clear Traffic Analytics?</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        This will delete historical request telemetry logs. Traffic statistics will restart from zero.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="confirmClearAnalytics = false" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <form action="{{ route('admin.api_hub.analytics.clear') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-500 rounded-xl transition shadow-lg shadow-amber-600/30">
                            Clear Analytics
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 3. Key Revocation Modal -->
        <div x-show="revokingKey" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div @click.away="revokingKey = null" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/15 text-rose-400 flex items-center justify-center font-bold text-xl border border-rose-500/30 mx-auto">
                    🛑
                </div>
                <div class="text-center">
                    <h3 class="text-lg font-bold text-white">Emergency Revoke Key?</h3>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                        Are you sure you want to revoke <strong class="text-white" x-text="revokingKey?.name"></strong> belonging to <strong class="text-white" x-text="revokingKey?.user"></strong>? This API key will stop working immediately.
                    </p>
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="revokingKey = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 rounded-xl transition">
                        Cancel
                    </button>
                    <form :action="'{{ url('/admin/api-hub/keys') }}/' + (revokingKey?.id || '')" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 rounded-xl transition shadow-lg shadow-rose-600/30">
                            Yes, Permanently Revoke
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-admin-layout>
