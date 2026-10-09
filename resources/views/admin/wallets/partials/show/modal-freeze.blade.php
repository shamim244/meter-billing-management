{{-- Freeze Reason Modal (PRD Section 7.4) --}}
@if(!$user->isWalletFrozen())
    <div x-show="openFreeze" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
        <div @click.away="closeFreezeModal()" class="bg-slate-950 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 shadow-2xl">
            <h3 class="text-base font-bold text-white flex items-center gap-2">
                <span>🔒</span> Freeze Agent Wallet
            </h3>
            <p class="text-xs text-slate-400">
                Freezing prevents any further debits or automated bill download charges on this account.
            </p>

            <form action="{{ route('admin.wallets.toggle-freeze', $user->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Reason for freezing <span class="text-rose-400">*</span>
                    </label>
                    <textarea name="reason" required rows="3" placeholder="Provide audit reason (e.g. Chargeback investigation, suspicious activity, customer dispute)..." class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl text-white p-3 focus:ring-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button type="button" @click="closeFreezeModal()" class="px-4 py-2 text-xs text-slate-400 hover:text-white font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition">
                        Confirm Freeze
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
