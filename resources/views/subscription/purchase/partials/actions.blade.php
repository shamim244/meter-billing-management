<!-- Submit Button -->
<div class="pt-4 border-t border-slate-100 dark:border-slate-800">
    <button type="submit" :disabled="isSubmitting" class="w-full py-3.5 px-6 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-black text-sm shadow-lg shadow-brand-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50">
        <span x-show="!isSubmitting">Proceed to Pay ₹{{ number_format($pricingDetails['final_amount'], 2) }} →</span>
        <span x-show="isSubmitting" x-cloak>Processing Checkout...</span>
    </button>
</div>
