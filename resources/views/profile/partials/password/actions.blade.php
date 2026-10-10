<!-- Submit Button & Feedback -->
<div class="pt-3 flex items-center gap-3">
    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs rounded-xl shadow-md shadow-indigo-500/20 transition flex items-center gap-2">
        <span>🔐 Update Password</span>
    </button>

    @if (session('status') === 'password-updated')
        <span
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 3000)"
            class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Password saved!
        </span>
    @endif
</div>
