<!-- Submit Button -->
<div class="pt-3">
    <button type="submit" 
            :disabled="isSubmitting"
            class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-500 via-cyan-500 to-brand-500 hover:from-emerald-600 hover:via-cyan-600 hover:to-brand-600 text-white font-bold text-sm shadow-lg shadow-emerald-500/20 transition-all duration-200 active:scale-[0.99] flex items-center justify-center gap-2 disabled:opacity-50">
        <span x-show="!isSubmitting">✨ Create Account & Get Started</span>
        <span x-show="isSubmitting" class="flex items-center gap-2" x-cloak>
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            Creating Account...
        </span>
    </button>
</div>

<!-- Switch to Login -->
<div class="mt-8 pt-6 border-t border-slate-800/80 text-center">
    <p class="text-xs text-slate-400">
        Already have an account? 
        <a href="{{ route('login') }}" class="font-bold text-brand-400 hover:text-brand-300 ml-1 hover:underline transition">
            Sign In →
        </a>
    </p>
</div>
