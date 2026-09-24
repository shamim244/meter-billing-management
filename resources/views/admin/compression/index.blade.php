<x-admin-layout>
    <x-slot name="header">
        Adaptive Compression & Edge Optimization
    </x-slot>

    <div class="space-y-8" x-data="compressionDashboard()">
        <!-- Top Toolbar & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl">
            <div>
                <h1 class="text-xl font-black text-white flex items-center gap-3">
                    <span class="p-2 rounded-xl bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 text-lg">🗜️</span>
                    Adaptive Hybrid Compression Monitor
                </h1>
                <p class="text-xs text-slate-400 mt-1">
                    Multi-tier content-aware response compression. Automatically negotiates between <strong>Zstandard</strong> (ultra-low latency), <strong>Brotli</strong> (maximum ratio), and <strong>Gzip</strong> (universal fallback).
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.api_hub.index') }}" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-slate-300 rounded-xl text-xs font-bold border border-slate-700/60 transition flex items-center gap-2">
                    <span>📡</span>
                    <span>API Hub</span>
                </a>
                <form action="{{ route('admin.compression.reset') }}" method="POST" onsubmit="return confirm('Reset all compression settings to factory defaults?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 rounded-xl text-xs font-bold border border-rose-800/40 transition">
                        Reset Defaults
                    </button>
                </form>
            </div>
        </div>

        @if(session('status'))
            <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-800/60 text-emerald-300 text-xs font-semibold flex items-center gap-3">
                <span class="text-base">✅</span>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Extension Status Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Zstandard Card -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 font-bold text-sm">⚡</span>
                        <div>
                            <h3 class="text-sm font-black text-white">Zstandard (Zstd)</h3>
                            <p class="text-[11px] text-slate-400">High-throughput real-time engine</p>
                        </div>
                    </div>
                    @if($status['capabilities']['zstd'])
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Loaded
                        </span>
                    @else
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            Missing
                        </span>
                    @endif
                </div>
                <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-400">
                        <span>Installed Version:</span>
                        <span class="font-mono text-slate-200">{{ $status['extension_versions']['zstd'] }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Optimal Target:</span>
                        <span class="text-cyan-400 font-semibold">JSON APIs & CSV Logs</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Speed Advantage:</span>
                        <span class="text-slate-300">3x – 5x faster than Gzip</span>
                    </div>
                </div>
            </div>

            <!-- Brotli Card -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 font-bold text-sm">🌐</span>
                        <div>
                            <h3 class="text-sm font-black text-white">Brotli (br)</h3>
                            <p class="text-[11px] text-slate-400">Maximum compression density</p>
                        </div>
                    </div>
                    @if($status['capabilities']['br'])
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Loaded
                        </span>
                    @else
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                            Missing
                        </span>
                    @endif
                </div>
                <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-400">
                        <span>Installed Version:</span>
                        <span class="font-mono text-slate-200">{{ $status['extension_versions']['brotli'] }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Optimal Target:</span>
                        <span class="text-indigo-400 font-semibold">HTML, CSS, XML</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Ratio Advantage:</span>
                        <span class="text-slate-300">~20% smaller than Gzip</span>
                    </div>
                </div>
            </div>

            <!-- Gzip / Deflate Card -->
            <div class="bg-slate-950 p-6 rounded-3xl border border-slate-800 shadow-xl relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-sm">📦</span>
                        <div>
                            <h3 class="text-sm font-black text-white">Gzip / Deflate (zlib)</h3>
                            <p class="text-[11px] text-slate-400">Universal compatibility fallback</p>
                        </div>
                    </div>
                    @if($status['capabilities']['gzip'])
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Loaded
                        </span>
                    @else
                        <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            Missing
                        </span>
                    @endif
                </div>
                <div class="mt-4 pt-4 border-t border-slate-800/80 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-400">
                        <span>Installed Version:</span>
                        <span class="font-mono text-slate-200">{{ $status['extension_versions']['zlib'] }}</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Optimal Target:</span>
                        <span class="text-emerald-400 font-semibold">Legacy Clients & Fallback</span>
                    </div>
                    <div class="flex justify-between text-slate-400">
                        <span>Compatibility:</span>
                        <span class="text-slate-300">100% universal across web</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Cascading Chain Visualizer -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>⛓️</span>
                        <span>Cascading Fallback Hierarchy</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Client requests pass through this prioritized chain. If the client or server does not support an algorithm, it seamlessly degrades to the next available tier.
                    </p>
                </div>
                <span class="px-2.5 py-1 text-[10px] font-black uppercase tracking-wider rounded-lg {{ $status['enabled'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                    Status: {{ $status['enabled'] ? 'Active & Optimizing' : 'Disabled' }}
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 pt-2">
                @foreach($status['priority'] as $index => $algo)
                    @php
                        $isCapable = $status['capabilities'][$algo] ?? false;
                        $isDisabled = in_array($algo, $status['disabled_by_admin'], true);
                        $isActive = $isCapable && ! $isDisabled && $status['enabled'];
                    @endphp
                    <div class="p-4 rounded-2xl border transition relative {{ $isActive ? 'bg-slate-900/80 border-slate-700 ring-1 ring-slate-600' : 'bg-slate-950/40 border-slate-900 opacity-60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $isActive ? 'bg-cyan-500/20 text-cyan-400' : 'bg-slate-800 text-slate-500' }}">
                                Tier {{ $index + 1 }}
                            </span>
                            @if($isActive)
                                <span class="text-[10px] text-emerald-400 font-bold flex items-center gap-1">✅ Active</span>
                            @elseif($isDisabled)
                                <span class="text-[10px] text-amber-400 font-bold flex items-center gap-1">⚠️ Admin Disabled</span>
                            @else
                                <span class="text-[10px] text-slate-500 font-bold flex items-center gap-1">❌ Unavailable</span>
                            @endif
                        </div>
                        <h4 class="text-sm font-bold text-white capitalize">{{ $algo === 'br' ? 'Brotli' : $algo }}</h4>
                        <p class="text-[11px] text-slate-400 mt-1">
                            @if($algo === 'zstd')
                                Preferred for JSON/CSV (Speed First)
                            @elseif($algo === 'br')
                                Preferred for HTML/Text (Max Ratio)
                            @elseif($algo === 'gzip')
                                Standard RFC 1952 Fallback
                            @else
                                Raw zlib Deflate Stream
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Live Diagnostic Benchmarker -->
        <div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-800/80 pb-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>🧪</span>
                        <span>Live Diagnostic Compression Benchmarker</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Test your server's live compression performance and ratios across different payload formats in real time.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="runBenchmark('json')" :disabled="loading" class="px-3.5 py-1.5 bg-cyan-950/60 hover:bg-cyan-900/60 text-cyan-300 rounded-xl text-xs font-bold border border-cyan-800/40 transition flex items-center gap-1.5">
                        <span>📄</span> <span>Test JSON API</span>
                    </button>
                    <button type="button" @click="runBenchmark('html')" :disabled="loading" class="px-3.5 py-1.5 bg-indigo-950/60 hover:bg-indigo-900/60 text-indigo-300 rounded-xl text-xs font-bold border border-indigo-800/40 transition flex items-center gap-1.5">
                        <span>🌐</span> <span>Test HTML Markup</span>
                    </button>
                    <button type="button" @click="runBenchmark('csv')" :disabled="loading" class="px-3.5 py-1.5 bg-emerald-950/60 hover:bg-emerald-900/60 text-emerald-300 rounded-xl text-xs font-bold border border-emerald-800/40 transition flex items-center gap-1.5">
                        <span>📊</span> <span>Test CSV Export</span>
                    </button>
                </div>
            </div>

            <!-- Benchmark Results Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
                            <th class="py-3 px-4">Algorithm</th>
                            <th class="py-3 px-4">Engine Status</th>
                            <th class="py-3 px-4">Original Size</th>
                            <th class="py-3 px-4">Compressed Size</th>
                            <th class="py-3 px-4">Payload Reduction</th>
                            <th class="py-3 px-4">Compression Latency</th>
                            <th class="py-3 px-4">Optimal Fit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-900">
                        <template x-for="(res, algo) in benchmark.algorithms" :key="algo">
                            <tr class="hover:bg-slate-900/40 transition">
                                <td class="py-3.5 px-4 font-bold text-white capitalize flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full" :class="res.available ? 'bg-emerald-400' : 'bg-slate-600'"></span>
                                    <span x-text="algo === 'br' ? 'Brotli' : algo.toUpperCase()"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span x-show="res.available" class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Operational</span>
                                    <span x-show="!res.available" class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-slate-800 text-slate-400 border border-slate-700" x-text="res.note"></span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-slate-400" x-text="formatBytes(benchmark.original_size_bytes)"></td>
                                <td class="py-3.5 px-4 font-mono font-bold" :class="res.available ? 'text-white' : 'text-slate-600'" x-text="res.available ? formatBytes(res.compressed_size) : '—'"></td>
                                <td class="py-3.5 px-4">
                                    <div x-show="res.available" class="flex items-center gap-2">
                                        <span class="font-bold text-cyan-400" x-text="res.ratio_percent + '%'"></span>
                                        <div class="w-16 h-1.5 bg-slate-800 rounded-full overflow-hidden">
                                            <div class="h-full bg-cyan-400 rounded-full" :style="'width: ' + res.ratio_percent + '%'"></div>
                                        </div>
                                    </div>
                                    <span x-show="!res.available" class="text-slate-600">—</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono" :class="res.available ? 'text-emerald-400' : 'text-slate-600'" x-text="res.available ? (res.duration_ms ? res.duration_ms + ' ms' : res.duration_microseconds + ' µs') : '—'"></td>
                                <td class="py-3.5 px-4">
                                    <span x-show="algo === summary.recommended_for_content" class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-cyan-500/20 text-cyan-300 border border-cyan-500/40">
                                        ⭐ Recommended
                                    </span>
                                    <span x-show="algo === summary.fastest_algorithm && algo !== summary.recommended_for_content" class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                        ⚡ Fastest
                                    </span>
                                    <span x-show="algo === summary.highest_ratio_algorithm && algo !== summary.recommended_for_content" class="px-2 py-0.5 text-[9px] font-black uppercase rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/40">
                                        🗜️ Smallest
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Configuration Settings Form -->
        <form action="{{ route('admin.compression.update') }}" method="POST" class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
            @csrf

            <div class="border-b border-slate-800/80 pb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <span>⚙️</span>
                        <span>Administrative Controls & Algorithm Governance</span>
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Customize runtime compression behavior, toggles, and fallback parameters.
                    </p>
                </div>
            </div>

            <!-- Master Toggle -->
            <div class="flex items-center justify-between p-4 bg-slate-900/60 rounded-2xl border border-slate-800">
                <div>
                    <label class="text-sm font-bold text-white block">Master Compression Engine</label>
                    <span class="text-xs text-slate-400">Globally enables or disables dynamic HTTP response compression.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="compression_enabled" value="1" class="sr-only peer" {{ $status['enabled'] ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                </label>
            </div>

            <!-- Algorithm Allowlist / Disable specific algorithms -->
            <div class="space-y-3">
                <label class="text-xs font-bold text-slate-300 uppercase tracking-wider block">Disable Specific Algorithms</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach(['zstd' => 'Zstandard', 'br' => 'Brotli', 'gzip' => 'Gzip', 'deflate' => 'Deflate'] as $key => $title)
                        <label class="flex items-center gap-3 p-4 rounded-2xl bg-slate-900/60 border border-slate-800 cursor-pointer hover:border-slate-700 transition">
                            <input type="checkbox" name="disabled_algorithms[]" value="{{ $key }}" class="rounded text-cyan-600 focus:ring-cyan-500 bg-slate-800 border-slate-700" {{ in_array($key, $status['disabled_by_admin'], true) ? 'checked' : '' }}>
                            <div class="text-xs">
                                <span class="font-bold text-white block">Disable {{ $title }}</span>
                                <span class="text-[10px] text-slate-400">Exclude from negotiation</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-cyan-600/20">
                    Save Compression Policy
                </button>
            </div>
        </form>
    </div>

    <script>
        function compressionDashboard() {
            return {
                loading: false,
                benchmark: @json($benchmark),
                summary: {
                    recommended_for_content: 'zstd',
                    fastest_algorithm: 'zstd',
                    highest_ratio_algorithm: 'br'
                },
                formatBytes(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },
                async runBenchmark(type) {
                    this.loading = true;
                    try {
                        const res = await fetch("{{ route('admin.compression.diagnostic') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ type })
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.benchmark = data.benchmark;
                            this.summary = data.summary;
                        }
                    } catch (e) {
                        console.error('Benchmark execution error:', e);
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
</x-admin-layout>
