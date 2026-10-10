<div class="p-12 text-center space-y-4">
    <div class="w-16 h-16 rounded-3xl bg-slate-100 dark:bg-slate-800/80 text-slate-400 dark:text-slate-500 flex items-center justify-center text-2xl mx-auto">
        🔑
    </div>
    <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">No API Keys Found</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1">
            Generate your first API key to connect your Phone ADB automation script, Flutter field app, or custom client.
        </p>
    </div>
    <button @click="createModalOpen = true" 
            type="button" 
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-500 text-white text-xs font-bold transition shadow-md cursor-pointer">
        <span>＋ Generate First Key</span>
    </button>
</div>
