<!-- Submit / Action Buttons -->
<div class="flex items-center justify-end gap-3 pt-4">
    <a href="{{ route('payments.index') }}" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold transition">
        Cancel
    </a>

    <button type="submit" :disabled="isSubmitting || amount < minAmount || (mode === 'manual_upi' && !utrNumber) || (mode === 'bank_transfer' && !bankReference)" class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-500 hover:to-blue-500 disabled:opacity-50 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-500/25 transition flex items-center gap-2">
        <span x-show="!isSubmitting">⚡</span>
        <span x-show="isSubmitting" class="inline-block animate-spin">⏳</span>
        <span x-text="mode === 'pg' ? (isSubmitting ? 'Opening Gateway...' : 'Proceed to Payment') : (isSubmitting ? 'Submitting...' : 'Submit for Verification')"></span>
    </button>
</div>
