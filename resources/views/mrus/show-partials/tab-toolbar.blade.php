<!-- Tab Switcher & Dynamic Action Toolbar -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
    <!-- Pill Tabs -->
    <div class="inline-flex p-1 bg-slate-200/80 dark:bg-slate-800 rounded-2xl">
        <button @click="activeTab = 'sessions'" :class="activeTab === 'sessions' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-4 sm:px-5 py-2 rounded-xl text-xs transition flex items-center gap-2">
            <span>📅</span> Billing Sessions ({{ $sessions->count() }})
        </button>
        <button @click="activeTab = 'consumers'" :class="activeTab === 'consumers' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-cyan-300 shadow-xs font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-medium'" class="px-4 sm:px-5 py-2 rounded-xl text-xs transition flex items-center gap-2">
            <span>👥</span> Consumer Master ({{ $consumers->total() }})
        </button>
    </div>

    <!-- Tab Toolbar Buttons -->
    <div class="flex items-center gap-2 flex-wrap">
        <template x-if="activeTab === 'sessions'">
            <button @click="showStartBillingModal = true" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                <span>⚡</span> New Billing Cycle
            </button>
        </template>

        <template x-if="activeTab === 'consumers'">
            <div class="flex items-center gap-2 flex-wrap">
                <button @click="showAddConsumerModal = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    + Add Consumer
                </button>
                <button @click="showImportModal = true" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                    📥 Bulk Paste CAs
                </button>
                <a href="{{ route('mrus.consumers.export', $mru) }}" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                    📤 Export CSV
                </a>
            </div>
        </template>
    </div>
</div>
