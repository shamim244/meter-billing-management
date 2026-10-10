@php
    $isReady = $preflight['server_ready'] ?? $preflight['ready'];
@endphp

<!-- Action Buttons -->
<div class="pt-4 flex items-center justify-between border-t border-slate-800">
    <a href="{{ route('install.step1') }}" class="px-4 py-2.5 text-xs font-bold text-slate-400 hover:text-white bg-slate-950 hover:bg-slate-800 rounded-xl transition">
        ↻ Re-check Environment
    </a>

    @if($isReady)
        <a href="{{ route('install.step2') }}" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-black rounded-xl shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
            <span>Next: Database Setup</span>
            <span>→</span>
        </a>
    @else
        <button disabled class="px-6 py-2.5 bg-slate-800 text-slate-500 text-xs font-black rounded-xl cursor-not-allowed">
            Fix Critical Issues to Proceed
        </button>
    @endif
</div>
