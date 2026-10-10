<!-- Header Brand & Switcher Indicator -->
<div class="h-16 px-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-500 flex items-center justify-center font-bold text-white shadow-md shadow-brand-500/20">
            👤
        </div>
        <div>
            <span class="font-extrabold text-sm tracking-tight text-slate-900 dark:text-white block leading-tight">User Control Center</span>
            <span class="text-[10px] font-bold text-brand-600 dark:text-cyan-400 uppercase tracking-wider">Account Hub</span>
        </div>
    </div>
    <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
        ✕
    </button>
</div>
