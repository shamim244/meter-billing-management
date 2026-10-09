{{-- Screenshot Modal --}}
<div x-show="screenshotModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
    <div @click.away="closeScreenshot()" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-4 shadow-2xl space-y-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-300">Payment Screenshot Proof</span>
            <button @click="closeScreenshot()" class="text-slate-400 hover:text-white text-lg">✕</button>
        </div>
        <div class="max-h-[75vh] overflow-auto flex items-center justify-center bg-slate-950 rounded-xl p-2">
            <img :src="currentScreenshot" alt="Proof" class="max-h-[70vh] object-contain rounded-lg shadow">
        </div>
    </div>
</div>
