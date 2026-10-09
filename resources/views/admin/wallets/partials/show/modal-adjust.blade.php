{{-- 2-Click Balance Adjustment Minimal Modal (PRD Section 7.4) --}}
<div x-show="showModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
    <div @click.away="closeModal()" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 max-w-lg w-full space-y-5 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span x-text="adjustmentType === 'add' ? '➕' : '➖'"></span>
                <span x-text="adjustmentType === 'add' ? 'Add Balance (Credit)' : 'Deduct Balance (Debit)'"></span>
            </h3>
            <button @click="closeModal()" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form action="{{ route('admin.wallets.adjust', $user->id) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="type" :value="adjustmentType">

            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300">
                Agent: <strong class="text-white">{{ $user->name }}</strong> (<span class="font-mono text-indigo-300">{{ $user->email }}</span>)<br>
                Current Balance: <strong class="font-mono text-emerald-400">₹{{ number_format($balance, 2) }}</strong>
            </div>

            {{-- Amount --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">
                    Amount (₹) <span class="text-rose-400">*</span>
                </label>
                <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" required placeholder="e.g. 500.00" class="w-full text-base font-black font-mono bg-slate-900 border-slate-800 rounded-xl text-white p-3 focus:ring-indigo-500">
            </div>

            {{-- Mandatory Reason --}}
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">
                    Mandatory Reason / Audit Note <span class="text-rose-400">*</span>
                </label>
                <textarea name="reason" x-model="reason" required rows="3" placeholder="Provide clear reason for ledger audit trail (e.g. Billing error reversal, promotional bonus, goodwill refund, offline cash received)..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-3 focus:ring-indigo-500"></textarea>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" @click="closeModal()" class="px-4 py-2.5 text-xs text-slate-400 hover:text-white font-bold">
                    Cancel
                </button>
                <button type="submit" :class="adjustmentType === 'add' ? 'bg-emerald-600 hover:bg-emerald-500 shadow-emerald-600/30' : 'bg-rose-600 hover:bg-rose-500 shadow-rose-600/30'" class="px-5 py-2.5 text-white rounded-xl text-xs font-bold shadow-lg transition flex items-center gap-2">
                    <span x-text="adjustmentType === 'add' ? 'Confirm & Add Balance' : 'Confirm & Deduct Balance'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
