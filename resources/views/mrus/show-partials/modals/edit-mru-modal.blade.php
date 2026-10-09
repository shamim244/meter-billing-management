<!-- MODAL: Edit / Rename MRU Workspace -->
<div x-show="showEditMruModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showEditMruModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                    ✏️
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit & Rename MRU</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Update MRU code and workspace name</p>
                </div>
            </div>
            <button @click="showEditMruModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('mrus.update', $mru) }}" class="overflow-y-auto p-4 sm:p-6 space-y-4">
            @csrf
            @method('PUT')

            <div class="p-3.5 bg-blue-50 dark:bg-blue-950/50 rounded-2xl border border-blue-100 dark:border-blue-800/60 text-xs text-blue-800 dark:text-blue-200 leading-relaxed">
                💡 <strong>Smart Storage Migration:</strong> Changing the MRU Code will automatically move all physical PDF storage folders on disk and update all bill file paths atomically.
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Code *</label>
                <input type="text" name="code" value="{{ $mru->code }}" required class="w-full text-xs font-mono uppercase rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Name / Area *</label>
                <input type="text" name="name" value="{{ $mru->name }}" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status</label>
                <select name="status" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                    <option value="active" {{ $mru->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $mru->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                <button type="button" @click="showEditMruModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">
                    Cancel
                </button>
                <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">
                    Save & Sync Paths
                </button>
            </div>
        </form>
    </div>
</div>
