{{-- Read Content Modal --}}
<div x-show="viewModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
    <div @click.away="closeViewModal()" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-3xl w-full shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="truncate pr-4">
                <div class="text-xs font-bold text-indigo-400 font-mono" x-text="'From: ' + currentFrom"></div>
                <h3 class="text-base font-bold text-white truncate" x-text="currentSubject"></h3>
            </div>
            <button @click="closeViewModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3">
            <div x-show="loadingContent" class="py-12 text-center text-slate-400">
                <div class="animate-spin text-2xl mb-2">⚡</div>
                <div class="text-xs">Loading email content from Hostinger API...</div>
            </div>

            <div x-show="!loadingContent" class="space-y-3">
                <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800" x-show="currentHtml">
                    <div class="text-[10px] uppercase font-bold text-slate-400 mb-2">Rendered HTML View:</div>
                    <div class="p-4 bg-slate-900 rounded-xl text-slate-200 text-xs overflow-x-auto" x-html="currentHtml"></div>
                </div>

                <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800">
                    <div class="text-[10px] uppercase font-bold text-slate-400 mb-2">Plain Text Content:</div>
                    <pre class="text-xs font-mono text-slate-300 whitespace-pre-wrap" x-text="currentText"></pre>
                </div>
            </div>
        </div>

        <div class="border-t border-slate-800 pt-3 flex justify-end">
            <button @click="closeViewModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">
                Close
            </button>
        </div>
    </div>
</div>
