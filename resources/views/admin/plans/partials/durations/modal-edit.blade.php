<!-- MODAL: Edit Duration -->
<div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.outside="showEditModal = false" class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg shadow-2xl p-6 space-y-5 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>✏️</span> Edit Duration — <span x-text="editForm.title"></span>
            </h3>
            <button @click="showEditModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
        </div>

        <form method="POST" :action="'/admin/plans/{{ $plan->id }}/durations/' + editForm.id" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Display Label (Optional)</label>
                <input type="text" name="name" x-model="editForm.name" placeholder="e.g. 7 Days Trial, Annual" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Discount %</label>
                    <input type="number" step="0.1" min="0" max="100" name="discount_percent" x-model.number="editForm.discount" @input="recalculateEditPrice()" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Final Payable Price (₹) *</label>
                    <input type="number" step="0.01" min="0" name="final_price" x-model.number="editForm.price" required class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-emerald-400 font-bold p-2.5 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800/80">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Extra MRU Rate (₹)</label>
                    <input type="number" step="0.01" min="0" name="extra_mru_rate" x-model="editForm.extraMru" placeholder="Base (₹{{ $plan->extra_mru_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Extra CA Rate (₹)</label>
                    <input type="number" step="0.01" min="0" name="extra_consumer_rate" x-model="editForm.extraConsumer" placeholder="Base (₹{{ $plan->extra_consumer_rate }})" class="w-full text-xs bg-slate-950 border-slate-800 rounded-xl text-white p-2.5 focus:ring-indigo-500 font-mono">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" name="is_active" value="1" x-model="editForm.isActive" id="edit_is_active" class="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-0">
                <label for="edit_is_active" class="text-xs font-semibold text-slate-300 cursor-pointer">
                    Active & visible to agents
                </label>
            </div>

            <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30 cursor-pointer">
                    Update Duration
                </button>
            </div>
        </form>
    </div>
</div>
