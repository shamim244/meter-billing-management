<!-- Section 6: Performance & Concurrency Limits -->
<div class="bg-slate-950 p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl space-y-6">
    <div class="border-b border-slate-800/80 pb-4">
        <h2 class="text-base font-bold text-white flex items-center gap-2">
            <span>🚀</span>
            <span>Performance & Multi-cURL Limits</span>
        </h2>
        <p class="text-xs text-slate-400 mt-0.5">
            Tune simultaneous network connections and execution timeout thresholds.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Simultaneous Connections (Multi-cURL Concurrency)
            </label>
            <input type="number" name="concurrency" min="1" max="50" value="{{ old('concurrency', $settings['concurrency']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-indigo-500 focus:border-indigo-500">
            <span class="text-[10px] text-slate-500 mt-1 block">Default: 10 connections. Higher values increase throughput but require more network bandwidth.</span>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Request Timeout (Seconds)
            </label>
            <input type="number" name="timeout" min="5" max="180" value="{{ old('timeout', $settings['timeout']) }}" required class="w-full bg-slate-900 border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-indigo-500 focus:border-indigo-500">
            <span class="text-[10px] text-slate-500 mt-1 block">Default: 45s. Maximum time permitted for each single handle before timing out.</span>
        </div>
    </div>

    <div class="pt-4 border-t border-slate-900 flex justify-end">
        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
            <span>💾</span>
            <span>Save Engine Configuration</span>
        </button>
    </div>
</div>
