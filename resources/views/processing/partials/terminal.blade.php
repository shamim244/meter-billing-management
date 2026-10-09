<!-- Real-Time Console Terminal Stream -->
<div class="bg-slate-950 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden">
    <!-- Terminal Header Bar -->
    <div class="px-6 py-4 bg-slate-900 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-full bg-rose-500/80"></span>
                <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
            </div>
            <div class="text-xs font-mono font-bold text-slate-300 flex items-center gap-2">
                <span>💻 Live Execution Stream</span>
                <span class="text-[10px] font-mono text-slate-500 hidden sm:inline">(process.log)</span>
                <span x-show="isAnyTaskRunning()" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] bg-cyan-950 text-cyan-400 border border-cyan-800 animate-pulse">
                    LIVE
                </span>
            </div>
        </div>

        <!-- Terminal Controls -->
        <div class="flex items-center gap-2 flex-wrap">
            <button @click="copyLogs()" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-mono transition">
                📋 Copy Logs
            </button>
            <button @click="clearConsoleScreen()" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-mono transition">
                🧹 Clear Screen
            </button>
            <button @click="clearLogFile()" class="px-3 py-1 bg-rose-950/60 hover:bg-rose-900 text-rose-300 rounded-lg text-xs font-mono border border-rose-800/60 transition">
                🗑️ Reset Log
            </button>
        </div>
    </div>

    <!-- Terminal Body Stream -->
    <div id="consoleTerminalBody" class="p-6 font-mono text-xs text-slate-300 h-80 overflow-y-auto space-y-1 select-text">
        <template x-if="!filteredLogLines || filteredLogLines.length === 0">
            <div class="text-slate-600 italic py-12 text-center">
                <span x-show="!searchQuery && activeLogFilter === 'all'">Console ready. Run Downloader or Parser to stream real-time task logs...</span>
                <span x-show="searchQuery || activeLogFilter !== 'all'">No log entries match the current filter/search criteria.</span>
            </div>
        </template>

        <template x-for="(line, idx) in filteredLogLines" :key="idx">
            <div class="leading-relaxed break-all py-0.5" :class="{
                'text-emerald-400 font-bold bg-emerald-950/20 px-1 rounded': line.includes('✅') || line.includes('Task Completed'),
                'text-rose-400 font-bold bg-rose-950/30 px-1 rounded border-l-2 border-rose-500': line.includes('❌') || line.includes('ERROR') || line.includes('failed'),
                'text-amber-300 font-bold bg-amber-950/20 px-1 rounded': line.includes('⚠️') || line.includes('Initiating'),
                'text-cyan-300 font-bold': line.includes('====='),
                'text-slate-400': !line.includes('✅') && !line.includes('❌') && !line.includes('⚠️') && !line.includes('=====')
            }" x-text="line"></div>
        </template>
    </div>
</div>
