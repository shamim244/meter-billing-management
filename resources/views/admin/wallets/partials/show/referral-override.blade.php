{{-- Referral & Earn Per-Agent Reward Override Box (PRD Section 7) --}}
<div class="glass-card rounded-3xl p-6 border border-slate-800/80 bg-gradient-to-r from-purple-950/20 via-slate-900/40 to-slate-950">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
        <div class="flex items-center gap-3">
            <span class="p-2.5 rounded-2xl bg-purple-500/10 text-purple-400 border border-purple-500/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    Referral Reward & Program Override
                    @if(isset($referralOverride['has_override']) && $referralOverride['has_override'])
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Custom Override Active</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-400 border border-slate-700">Platform Default</span>
                    @endif
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Referral Code: <span class="font-mono text-purple-300 font-bold">{{ $referralOverride['coupon']?->code ?? 'Auto-generated on signup' }}</span>
                    • Status: <span class="{{ ($referralOverride['is_active'] ?? true) ? 'text-emerald-400' : 'text-rose-400' }} font-semibold">{{ ($referralOverride['is_active'] ?? true) ? 'Active' : 'Deactivated' }}</span>
                </p>
            </div>
        </div>

        @if($referralOverride['coupon'])
            <form action="{{ route('admin.referrals.coupon.toggle', $referralOverride['coupon']) }}" method="POST">
                @csrf
                @method('PATCH')
                <button type="submit" class="px-3.5 py-1.5 rounded-xl {{ $referralOverride['is_active'] ? 'bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }} text-xs font-bold transition">
                    {{ $referralOverride['is_active'] ? '🚫 Deactivate Referral Code' : '✅ Re-activate Referral Code' }}
                </button>
            </form>
        @endif
    </div>

    <form action="{{ route('admin.referrals.users.override', $user) }}" method="POST" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Override Reward Type</label>
            <select name="override_kind" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                <option value="percentage" {{ ($referralOverride['discount_kind'] ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage (%) of Payment</option>
                <option value="flat" {{ ($referralOverride['discount_kind'] ?? '') === 'flat' ? 'selected' : '' }}>Flat Rupee Amount (₹)</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Custom Reward Value</label>
            <input type="number" step="0.01" min="0" name="override_value" value="{{ $referralOverride['discount_value'] ?? '' }}" placeholder="Leave blank to use platform default" class="w-full px-3 py-2 rounded-xl bg-slate-900 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-purple-600/20">
                <span>💾</span> Save Override
            </button>
        </div>
    </form>
</div>
