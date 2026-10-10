<div x-data="{ openModal: false }"
     @open-resolve-modal.window="openModal = true"
     x-show="openModal"
     x-cloak
     class="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-4">
    <div @click.away="openModal = false" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
        <h3 class="text-base font-bold text-white flex items-center gap-2">
            <span>✅</span> Mark Issue as Resolved
        </h3>
        <p class="text-xs text-slate-400">Describe the fix or code changes applied to resolve this bug.</p>

        <form method="POST" action="{{ route('admin.issues.resolve', $issue) }}" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Resolution Summary <span class="text-rose-500">*</span></label>
                <textarea name="ai_resolution_notes"
                          rows="4"
                          required
                          placeholder="e.g. Fixed base average locking in SmartAverageCalculationService. Verified with automated tests."
                          class="w-full text-xs bg-slate-950 border border-slate-800 rounded-xl p-3 text-white focus:ring-1 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" @click="openModal = false" class="px-4 py-2 text-xs font-bold text-slate-400 hover:text-white transition">Cancel</button>
                <button type="submit" class="px-4 py-2 text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl shadow-md transition">Save & Resolve</button>
            </div>
        </form>
    </div>
</div>
