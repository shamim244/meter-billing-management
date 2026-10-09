{{-- Rebinding Banner Modal Alert (Live Key Listening) --}}
<div x-show="rebindingAction" class="p-6 rounded-3xl bg-indigo-950/90 border-2 border-indigo-500 text-center shadow-xl transition-all" x-cloak>
    <div class="flex items-center justify-center gap-2 text-xs font-bold uppercase tracking-wider text-indigo-300">
        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
        Listening for System Keypress
    </div>
    <div class="text-lg font-black text-white mt-1.5">
        Assign key for: <span class="text-cyan-300 underline decoration-2 underline-offset-4" x-text="labels[rebindingAction] || rebindingAction"></span>
    </div>
    <div class="mt-3 inline-block px-5 py-2 rounded-2xl bg-slate-900 border border-indigo-700 shadow-sm">
        <span class="text-sm font-mono font-black text-cyan-300" x-text="rebindDisplay"></span>
    </div>
    <div class="mt-4 flex items-center justify-center gap-2">
        <button type="button" @click="cancelRebind()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold rounded-xl transition">
            Cancel (Escape)
        </button>
    </div>
</div>
