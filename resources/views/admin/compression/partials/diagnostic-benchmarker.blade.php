{{-- Live Diagnostic Benchmarker --}}
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

    {{-- Benchmark Results Table --}}
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
