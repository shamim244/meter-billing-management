<!-- TAB 2: Clean / Remove MRUs -->
<div x-show="cleanupTab === 'mrus'" class="space-y-4" x-cloak>
    <div class="p-3 rounded-xl bg-amber-950/30 border border-amber-500/20 text-xs text-amber-200 leading-relaxed">
        ⚠️ <strong>MRU Removal:</strong> Removing an MRU permanently deletes the MRU container, its consumer records, and associated PDF files. The user's account, subscription, and remaining MRUs will remain untouched.
    </div>

    @if($mrus->isEmpty())
        <div class="p-6 text-center text-xs text-slate-500 bg-slate-950 rounded-2xl border border-slate-800">
            This user has no active MRUs.
        </div>
    @else
        <form method="POST" action="{{ route('admin.users.clean_mrus', $user) }}" class="space-y-4">
            @csrf
            <div class="flex items-center justify-between pb-1">
                <label class="text-xs font-bold text-slate-300">Select MRUs to Delete:</label>
                <label class="text-xs text-slate-400 flex items-center gap-1.5 cursor-pointer">
                    <input type="checkbox" x-model="selectAllMrus" @change="toggleAllMrus()" class="rounded text-indigo-600 focus:ring-indigo-500 bg-slate-950 border-slate-700">
                    <span>Select All</span>
                </label>
            </div>

            <div class="max-h-56 overflow-y-auto space-y-2 pr-1">
                @foreach($mrus as $mru)
                    <label class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between text-xs cursor-pointer hover:border-slate-700 transition">
                        <div class="flex items-center gap-2.5">
                            <input type="checkbox" name="mru_ids[]" value="{{ $mru->id }}" x-model="selectedMruIds" class="rounded text-indigo-600 focus:ring-indigo-500 bg-slate-900 border-slate-700">
                            <div>
                                <span class="font-bold text-white font-mono">{{ $mru->code }}</span>
                                <span class="text-slate-300 ml-1">{{ $mru->name }}</span>
                            </div>
                        </div>
                        <span class="text-[11px] text-slate-400 font-medium">
                            {{ $mru->consumer_accounts_count }} consumers
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="pt-3 border-t border-slate-800 flex justify-end gap-2">
                <button type="button" @click="showCleanupModal = false" class="px-4 py-2 text-xs rounded-xl bg-slate-800 text-slate-300">Cancel</button>
                <button type="submit" :disabled="selectedMruIds.length === 0" onclick="return confirm('Permanently delete the selected MRUs and their data?');" class="px-5 py-2 text-xs font-bold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white disabled:opacity-40 disabled:cursor-not-allowed shadow">
                    Delete Selected MRUs (<span x-text="selectedMruIds.length"></span>)
                </button>
            </div>
        </form>
    @endif
</div>
