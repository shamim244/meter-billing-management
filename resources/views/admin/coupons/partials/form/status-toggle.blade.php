<div class="pt-2">
    <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $coupon->is_active ?? true) ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-800 text-indigo-600 focus:ring-indigo-500">
        <span>{{ isset($coupon) ? 'Coupon campaign is active and eligible for redemptions' : 'Activate coupon campaign immediately upon creation' }}</span>
    </label>
</div>
