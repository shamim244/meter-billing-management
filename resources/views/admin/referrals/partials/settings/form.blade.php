<!-- Settings Form Card -->
<div class="glass-card rounded-3xl p-6 sm:p-8 border border-slate-800/80">
    <form action="{{ route('admin.referrals.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Enable/Disable Switch -->
        <div class="flex items-center justify-between p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-white">Enable Refer & Earn Program</h3>
                <p class="text-xs text-slate-400 mt-0.5">Allow agents to share invitation links and earn wallet rewards on referee payments</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="is_enabled" value="1" {{ $settings['is_enabled'] ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Reward Trigger -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Qualifying Reward Trigger <span class="text-rose-400">*</span>
                </label>
                <select name="reward_trigger" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-sm text-white focus:outline-none focus:border-purple-500">
                    <option value="subscription" {{ $settings['reward_trigger'] === 'subscription' ? 'selected' : '' }}>
                        📦 First Subscription Payment (Recommended — Proves Real Usage)
                    </option>
                    <option value="topup" {{ $settings['reward_trigger'] === 'topup' ? 'selected' : '' }}>
                        💳 First Wallet Top-Up Deposit
                    </option>
                </select>
                <p class="text-[11px] text-slate-500 mt-1.5">Determines which payment event generates the one-time referral payout for the referrer.</p>
            </div>

            <!-- Minimum Qualifying Amount -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Minimum Qualifying Amount (₹) <span class="text-rose-400">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500 text-sm font-semibold">₹</span>
                    <input type="number" step="1" min="1" name="minimum_qualifying_amount" value="{{ old('minimum_qualifying_amount', $settings['minimum_qualifying_amount']) }}" required class="w-full pl-8 pr-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-sm text-white focus:outline-none focus:border-purple-500">
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5">Payments below this threshold will NOT trigger any referral reward (prevents ₹1 farming).</p>
            </div>

            <!-- Reward Kind -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Referrer Reward Type <span class="text-rose-400">*</span>
                </label>
                <select name="reward_kind" class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-sm text-white focus:outline-none focus:border-purple-500">
                    <option value="percentage" {{ $settings['reward_kind'] === 'percentage' ? 'selected' : '' }}>
                        Percentage (%) of Qualifying Payment
                    </option>
                    <option value="flat" {{ $settings['reward_kind'] === 'flat' ? 'selected' : '' }}>
                        Flat Rupee Amount (₹)
                    </option>
                </select>
            </div>

            <!-- Reward Value -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Platform Default Reward Value <span class="text-rose-400">*</span>
                </label>
                <input type="number" step="0.01" min="0" name="reward_value" value="{{ old('reward_value', $settings['reward_value']) }}" required class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-sm text-white focus:outline-none focus:border-purple-500">
                <p class="text-[11px] text-slate-500 mt-1.5">e.g. 10 for 10% or 50 for ₹50 flat. (Can be overridden per specific Agent).</p>
            </div>

            <!-- Hold Period Days -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Hold Period Duration (Days) <span class="text-rose-400">*</span>
                </label>
                <input type="number" min="0" max="90" name="hold_period_days" value="{{ old('hold_period_days', $settings['hold_period_days']) }}" required class="w-full px-4 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-sm text-white focus:outline-none focus:border-purple-500">
                <p class="text-[11px] text-slate-500 mt-1.5">Days to hold the reward in 'pending' status before automatic wallet crediting. Reversals during this window cancel payout with ₹0 impact.</p>
            </div>

            <!-- Optional Referee Perk -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                    Referee Discount/Perk (Optional)
                </label>
                <div class="grid grid-cols-2 gap-3">
                    <select name="referee_discount_kind" class="w-full px-3 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                        <option value="">None (Referrer Only)</option>
                        <option value="percentage" {{ $settings['referee_discount_kind'] === 'percentage' ? 'selected' : '' }}>Percentage (%) Off</option>
                        <option value="flat" {{ $settings['referee_discount_kind'] === 'flat' ? 'selected' : '' }}>Flat (₹) Off</option>
                    </select>
                    <input type="number" step="0.01" min="0" name="referee_discount_value" value="{{ old('referee_discount_value', $settings['referee_discount_value']) }}" placeholder="e.g. 5" class="w-full px-3 py-3 rounded-xl bg-slate-900/80 border border-slate-700 text-xs text-white focus:outline-none focus:border-purple-500">
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5">Optional discount applied for the referred new agent on their checkout.</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-800 flex justify-end">
            <button type="submit" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-bold text-sm shadow-lg shadow-purple-600/20 transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save Configuration
            </button>
        </div>
    </form>
</div>
