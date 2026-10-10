<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-bold text-slate-300 mb-1">Usage Limit Per User</label>
        <input type="number" name="usage_limit_per_user" value="{{ old('usage_limit_per_user', $coupon->usage_limit_per_user ?? 1) }}" min="1" max="1000" required class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
        <p class="text-[10px] text-slate-500 mt-0.5">Default is 1 (one-time redemption per billing agent).</p>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-300 mb-1">Total Platform Redemptions Cap</label>
        <input type="number" name="usage_limit_total" value="{{ old('usage_limit_total', $coupon->usage_limit_total ?? '') }}" min="1" placeholder="Leave empty for unlimited" class="w-full text-xs font-mono bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
        <p class="text-[10px] text-slate-500 mt-0.5">Optional platform-wide maximum total uses.</p>
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-300 mb-1">Schedule Start Date (Optional)</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', isset($coupon) && $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '') }}" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
    </div>

    <div>
        <label class="block text-xs font-bold text-slate-300 mb-1">Expiration Date (Optional)</label>
        <input type="datetime-local" name="expires_at" value="{{ old('expires_at', isset($coupon) && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}" class="w-full text-xs bg-slate-900 border-slate-800 rounded-xl py-2 px-3 text-white">
    </div>
</div>
