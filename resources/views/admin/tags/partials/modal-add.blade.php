<div x-show="showNewTagModal" class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4" x-cloak>
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-2xl">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <span>➕</span> Add New Review Tag
            </h3>
            <button type="button" @click="showNewTagModal = false" class="text-slate-400 hover:text-white text-lg font-bold">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.tags.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Tag Code <span class="text-rose-400">*</span></label>
                <input type="text" name="code" x-model="newCode" required placeholder="e.g. 24DAYS / MTR_BURNT" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 font-mono uppercase focus:ring-indigo-500">
                <span class="text-[10px] text-slate-500 mt-1 block">Unique identifier for database storage.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Full Label (Reports & Tooltip) <span class="text-rose-400">*</span></label>
                <input type="text" name="label" x-model="newLabel" required placeholder="e.g. Not-approved Previous BQC and RQC" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Card Pill Label (Short Mobile Display) <span class="text-rose-400">*</span></label>
                <input type="text" name="short_label" x-model="newShortLabel" required placeholder="e.g. Not-Apprv Prev BQC/RQC" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                <span class="text-[10px] text-slate-500 mt-1 block">Compact text shown on card pills to prevent UI overflow.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Badge Color Theme <span class="text-rose-400">*</span></label>
                <select name="color" x-model="newColor" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
                    <option value="emerald">🟢 Emerald (Green)</option>
                    <option value="blue">🔵 Blue</option>
                    <option value="purple">🟣 Purple</option>
                    <option value="amber">🟠 Amber</option>
                    <option value="rose">🔴 Rose / Red</option>
                    <option value="cyan">🩵 Cyan</option>
                    <option value="indigo">🔷 Indigo</option>
                    <option value="slate">⚪ Slate / Grey</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" @click="showNewTagModal = false" class="px-4 py-2 bg-slate-800 text-slate-300 rounded-xl text-xs font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition">
                    Add Tag
                </button>
            </div>
        </form>
    </div>
</div>
