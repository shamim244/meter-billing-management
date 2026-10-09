{{-- Configuration Settings Form --}}
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

    {{-- Master Toggle --}}
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

    {{-- Algorithm Allowlist / Disable specific algorithms --}}
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
