<div x-show="showRegenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" x-cloak>
    <div @click.outside="showRegenerateModal = false" class="w-full max-w-md rounded-3xl bg-slate-900 border border-slate-800 p-6 shadow-2xl space-y-4">
        <div class="flex items-center gap-3">
            <span class="p-2.5 rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
            <div>
                <h3 class="text-base font-bold text-white">Regenerate Referral Code?</h3>
                <p class="text-xs text-slate-400">Issue a fresh referral code & invite link</p>
            </div>
        </div>

        <p class="text-xs text-slate-300 leading-relaxed">
            Generating a new code will immediately <strong>deactivate your current link ({{ $stats['referral_code'] }})</strong> for new registrations. Any referrals currently in the hold period will <strong>remain completely protected and continue to pay out normally</strong>.
        </p>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" @click="showRegenerateModal = false" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                Cancel
            </button>
            <form action="{{ route('referrals.regenerate') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition">
                    Yes, Issue New Code
                </button>
            </form>
        </div>
    </div>
</div>
