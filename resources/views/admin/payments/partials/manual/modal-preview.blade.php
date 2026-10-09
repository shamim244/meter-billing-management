{{-- Receipt Zoom Preview Modal --}}
<div x-show="previewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
    <div @click.away="closePreview()" class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-4 shadow-2xl space-y-3">
        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
            <h3 class="text-sm font-bold text-white">Payment Receipt Proof</h3>
            <button type="button" @click="closePreview()" class="p-1 text-slate-400 hover:text-white text-base">✕</button>
        </div>
        <div class="flex items-center justify-center max-h-[70vh] overflow-auto bg-slate-950 rounded-xl p-2">
            <img :src="previewImageUrl" alt="Receipt Full" class="max-h-[65vh] object-contain rounded-lg">
        </div>
        <div class="flex justify-end">
            <a :href="previewImageUrl" target="_blank" download class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition">
                Open in Full Tab ↗
            </a>
        </div>
    </div>
</div>
