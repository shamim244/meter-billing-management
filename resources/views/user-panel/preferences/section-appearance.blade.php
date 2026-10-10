<!-- 6. Appearance / Theme -->
<div class="space-y-2 pb-2">
    <label class="text-sm font-bold text-slate-900 dark:text-white block">Color Theme Preference</label>
    <p class="text-xs text-slate-500 dark:text-slate-400">Select your preferred visual theme for the entire portal.</p>
    
    <div class="grid grid-cols-3 gap-3 pt-2">
        <label class="p-4 rounded-2xl border cursor-pointer text-center transition {{ ($preferences['theme'] ?? 'system') === 'light' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="theme" value="light" class="sr-only" {{ ($preferences['theme'] ?? 'system') === 'light' ? 'checked' : '' }}>
            <div class="text-lg mb-1">☀️</div>
            <div class="text-xs font-bold">Light Mode</div>
        </label>

        <label class="p-4 rounded-2xl border cursor-pointer text-center transition {{ ($preferences['theme'] ?? 'system') === 'dark' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="theme" value="dark" class="sr-only" {{ ($preferences['theme'] ?? 'system') === 'dark' ? 'checked' : '' }}>
            <div class="text-lg mb-1">🌙</div>
            <div class="text-xs font-bold">Dark Mode</div>
        </label>

        <label class="p-4 rounded-2xl border cursor-pointer text-center transition {{ ($preferences['theme'] ?? 'system') === 'system' ? 'border-brand-500 bg-brand-50/40 dark:bg-brand-950/40 text-brand-700 dark:text-cyan-300 ring-2 ring-brand-500/20' : 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/50 text-slate-700 dark:text-slate-300' }}">
            <input type="radio" name="theme" value="system" class="sr-only" {{ ($preferences['theme'] ?? 'system') === 'system' ? 'checked' : '' }}>
            <div class="text-lg mb-1">💻</div>
            <div class="text-xs font-bold">System Sync</div>
        </label>
    </div>
</div>

<!-- Submit Button -->
<div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end">
    <button type="submit" class="px-6 py-2.5 bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs rounded-xl shadow-md shadow-brand-500/20 transition">
        💾 Save Preferences
    </button>
</div>
