<div class="bg-gradient-to-br from-indigo-950/60 via-slate-950 to-slate-950 p-6 rounded-3xl border border-indigo-900/60 shadow-xl space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-indigo-600/30 text-indigo-400 flex items-center justify-center text-xl font-bold">
                🤖
            </div>
            <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <span>AI Agent Diagnostic Bundle</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-mono">Antigravity Ready</span>
                </h2>
                <p class="text-xs text-slate-400">Hand this diagnostic bundle to the AI agent to reproduce, fix code, and run tests.</p>
            </div>
        </div>

        <!-- Copy AI Prompt Button -->
        <button type="button"
                @click="navigator.clipboard.writeText($refs.aiPromptBox.value); copied = true; setTimeout(() => copied = false, 3000)"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition active:scale-95 cursor-pointer">
            <span x-show="!copied">📋 Copy AI Fix Prompt</span>
            <span x-show="copied" x-cloak class="text-emerald-300 font-bold">✓ Copied to Clipboard!</span>
        </button>
    </div>

    <!-- Terminal Command Shortcut -->
    <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 truncate">
            <span class="text-indigo-400 font-bold">CLI Command:</span>
            <code class="text-indigo-200 select-all">php artisan issue:show {{ $issue->issue_code }}</code>
        </div>
        <span class="text-[10px] text-slate-500 shrink-0">Terminal Agent Access</span>
    </div>

    <!-- Formatted Prompt Preview -->
    <div class="relative">
        <textarea x-ref="aiPromptBox"
                  readonly
                  rows="10"
                  class="w-full text-xs font-mono p-4 rounded-2xl bg-slate-950 border border-slate-800 text-indigo-200 select-all focus:ring-0 leading-relaxed">{{ $aiPrompt }}</textarea>
    </div>
</div>
