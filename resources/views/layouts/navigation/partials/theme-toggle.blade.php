<!-- Theme Toggle Button -->
<div x-data="navigationThemeToggle()">
    <button @click="toggle()" 
            type="button" 
            class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-sm border border-slate-200/60 dark:border-slate-700/60" 
            :title="darkMode ? 'Switch to Light Theme' : 'Switch to Dark Theme'">
        <span x-show="!darkMode">🌙</span>
        <span x-show="darkMode" x-cloak>☀️</span>
    </button>
</div>
