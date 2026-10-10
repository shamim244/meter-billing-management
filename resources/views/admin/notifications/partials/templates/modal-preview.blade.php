<div x-show="previewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="previewModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">Live Render Preview</h3>
            <button @click="previewModal = false" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <div class="space-y-3 text-xs">
            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Subject:</span>
                <div class="text-white font-semibold mt-0.5" x-text="previewSubject"></div>
            </div>

            <div>
                <span class="text-slate-400 font-bold uppercase text-[10px]">Rendered Body:</span>
                <div class="mt-1 p-4 bg-slate-950 rounded-xl border border-slate-800 text-slate-200 leading-relaxed" x-html="previewBody"></div>
            </div>
        </div>

        <div class="flex justify-end pt-3 border-t border-slate-800">
            <button type="button" @click="previewModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl font-bold text-xs">Close</button>
        </div>
    </div>
</div>
