<x-app-layout>
    <div x-data="mruHubApp()" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Breadcrumb Navigation -->
            <div class="flex items-center justify-between">
                <a href="{{ route('mrus.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-cyan-400 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to MRU Workspaces
                </a>
                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">Workspace #{{ $mru->id }}</span>
            </div>

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>✅</span> {{ session('success') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('info'))
                <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-cyan-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>ℹ️</span> {{ session('info') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-blue-600 dark:text-cyan-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('warning'))
                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>⚠️</span> {{ session('warning') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-amber-600 dark:text-amber-400 hover:opacity-75">✕</button>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-rose-800 dark:text-rose-300 text-xs font-semibold flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <span>❌</span> {{ session('error') }}
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-rose-600 dark:text-rose-400 hover:opacity-75">✕</button>
                </div>
            @endif

            <!-- MRU Master Hero Card -->
            <div class="bg-white dark:bg-slate-900/90 backdrop-blur-md p-6 sm:p-8 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-2.5 flex-wrap mb-2">
                            <span class="px-3 py-1 rounded-xl text-xs font-mono font-black bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60 tracking-wider">
                                {{ $mru->code }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $mru->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $mru->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $mru->status }}
                            </span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                            {{ $mru->name }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">
                            {{ $mru->full_identifier ?: "Permanent Consumer Master Workspace" }}
                        </p>
                    </div>

                    <!-- Hero Quick Stats & Settings -->
                    <div class="flex flex-wrap items-center gap-4 lg:gap-6 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 pt-4 lg:pt-0 lg:pl-8">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Master Consumers</span>
                            <div class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white font-mono mt-0.5">
                                {{ number_format($consumers->total()) }}
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Billing Sessions</span>
                            <div class="text-xl sm:text-2xl font-black text-blue-600 dark:text-cyan-400 font-mono mt-0.5">
                                {{ $sessions->count() }}
                            </div>
                        </div>
                        <div class="flex items-center gap-2 pt-1 sm:pt-0">
                            <button @click="showEditMruModal = true" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition" title="Edit MRU Name or Code">
                                ✏️ Edit
                            </button>
                            <button @click="showDeleteMruModal = true" class="px-3.5 py-2 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-xs font-bold border border-rose-200 dark:border-rose-800/60 transition flex items-center gap-1" title="Delete MRU Workspace">
                                <span>🗑️</span>
                                <span>Delete MRU</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

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

            <!-- TAB 1: MONTHLY BILLING SESSIONS -->
            <div x-show="activeTab === 'sessions'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($sessions as $session)
                        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-xs hover:shadow-md transition-all duration-200 p-6 space-y-5 flex flex-col justify-between group">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 rounded-xl text-xs font-mono font-black bg-blue-50 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60">
                                        {{ date('F, Y', mktime(0, 0, 0, (int)$session->billing_month, 1, (int)$session->billing_year)) }}
                                    </span>
                                    @if($session->billing_month == now()->month && $session->billing_year == now()->year)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">● Active Cycle</span>
                                    @elseif($loop->first)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 uppercase tracking-wider">Latest Cycle</span>
                                    @else
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Cycle Archive</span>
                                    @endif
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3">
                                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Bills Processed</span>
                                        <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">{{ number_format($session->total_bills) }}</div>
                                    </div>
                                    <div class="bg-slate-50 dark:bg-slate-800/60 p-3.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Total Billed</span>
                                        <div class="text-lg font-black text-blue-600 dark:text-cyan-400 font-mono mt-0.5">₹{{ number_format($session->total_amount, 2) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <a href="{{ route('dashboard', ['mru_id' => $mru->id, 'month' => $session->billing_month, 'year' => $session->billing_year]) }}" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow-xs group-hover:shadow-md transition">
                                    Open Month Dashboard →
                                </a>
                                <div class="grid grid-cols-3 gap-2">
                                    <button @click="syncMissingSession({{ $session->billing_month }}, {{ $session->billing_year }})" class="py-2 bg-amber-50 dark:bg-amber-950/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 text-amber-800 dark:text-amber-300 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition" title="Only downloads missing or failed bills">
                                        ⚡ Sync
                                    </button>
                                    <button @click="openDownloadForSession({{ $session->billing_month }}, {{ $session->billing_year }})" class="py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition" title="Re-download all bills in this cycle">
                                        🔄 Pull All
                                    </button>
                                    <form action="{{ route('mrus.sessions.destroy', ['mru' => $mru->id, 'month' => $session->billing_month, 'year' => $session->billing_year]) }}" method="POST" onsubmit="return confirm('Permanently delete {{ date('F, Y', mktime(0, 0, 0, (int)$session->billing_month, 1, (int)$session->billing_year)) }} session, its {{ $session->total_bills }} bills, and ALL physical PDF files on disk?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full py-2 bg-rose-50 dark:bg-rose-950/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 rounded-xl text-[11px] font-bold flex items-center justify-center gap-1 transition" title="Delete this session and purge all PDFs">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full bg-white dark:bg-slate-900 p-12 text-center rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
                            <div class="w-14 h-14 bg-blue-50 dark:bg-blue-950/40 text-blue-500 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                                📅
                            </div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">No monthly billing sessions recorded yet</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-5">
                                Initialize your first billing cycle (e.g. {{ date('F Y') }}) for this MRU workspace.
                            </p>
                            <button @click="showStartBillingModal = true" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 shadow-sm transition">
                                + Create First Billing Cycle
                            </button>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- TAB 2: CONSUMER MASTER LIST -->
            <div x-show="activeTab === 'consumers'" class="space-y-4">
                <!-- Search and Count Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                    <form method="GET" action="{{ route('mrus.show', $mru) }}" class="relative flex-1 max-w-md">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Search CA Number, Consumer Name, Meter No..." class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-9 pr-8 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        @if(!empty($search))
                            <a href="{{ route('mrus.show', $mru) }}" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">✕</a>
                        @endif
                    </form>

                    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        Showing <strong class="text-slate-900 dark:text-white font-mono">{{ $consumers->count() }}</strong> of <strong class="text-slate-900 dark:text-white font-mono">{{ $consumers->total() }}</strong> registered consumers
                    </div>
                </div>

                <!-- Consumers Table -->
                <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50/90 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-[11px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                                <tr>
                                    <th class="py-3.5 px-6">CA Number</th>
                                    <th class="py-3.5 px-6">Consumer Name</th>
                                    <th class="py-3.5 px-6 text-center">Meter No</th>
                                    <th class="py-3.5 px-6 text-center">Mobile</th>
                                    <th class="py-3.5 px-6 text-center">Status</th>
                                    <th class="py-3.5 px-6 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                                @forelse($consumers as $consumer)
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/50 transition">
                                        <td class="py-3.5 px-6 font-mono font-bold text-blue-600 dark:text-cyan-400">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <a href="{{ route('bills.history', $consumer->ca_number) }}" class="hover:underline" title="View historical ledger">
                                                    {{ $consumer->ca_number }}
                                                </a>
                                                @if($consumer->tariff_category)
                                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-bold font-mono bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200/50 dark:border-indigo-800/50">
                                                        {{ $consumer->tariff_category }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 font-semibold text-slate-900 dark:text-white">
                                            {{ $consumer->consumer_name ?: '—' }}
                                        </td>
                                        <td class="py-3.5 px-6 text-center font-mono text-slate-500 dark:text-slate-400">
                                            {{ $consumer->meter_no ?: '—' }}
                                        </td>
                                        <td class="py-3.5 px-6 text-center font-mono text-slate-500 dark:text-slate-400">
                                            {{ $consumer->mobile ?: '—' }}
                                        </td>
                                        <td class="py-3.5 px-6 text-center">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $consumer->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                                <span class="w-1 h-1 rounded-full {{ $consumer->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                {{ $consumer->status }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-6 text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="openEditConsumerModal(@js($consumer))" class="text-blue-600 dark:text-cyan-400 hover:underline text-xs font-bold">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('mrus.consumers.destroy', [$mru, $consumer]) }}" onsubmit="return confirm('Remove consumer {{ $consumer->ca_number }} from master list?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                            <div class="text-2xl mb-1">👥</div>
                                            No consumers found matching your search.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($consumers->hasPages())
                        <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-200 dark:border-slate-700">
                            {{ $consumers->appends(['search' => $search])->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- MODAL: Add Single Consumer -->
            <div x-show="showAddConsumerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showAddConsumerModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                +
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Add Consumer</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Register CA in MRU {{ $mru->code }}</p>
                            </div>
                        </div>
                        <button @click="showAddConsumerModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <form method="POST" action="{{ route('mrus.consumers.store', $mru) }}" class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">CA Number *</label>
                            <input type="text" name="ca_number" placeholder="e.g. 10230046961" required class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Consumer Name</label>
                            <input type="text" name="consumer_name" placeholder="Full name" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Meter Number</label>
                                <input type="text" name="meter_no" placeholder="e.g. 3808220" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Mobile</label>
                                <input type="text" name="mobile" placeholder="10 digits" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Address (Optional)</label>
                            <input type="text" name="address" placeholder="Village / Area details" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showAddConsumerModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">Cancel</button>
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">Save Consumer</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Edit Existing Consumer -->
            <div x-show="showEditConsumerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showEditConsumerModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ✏️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Consumer</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">CA: <span class="font-mono font-bold text-blue-600 dark:text-cyan-400" x-text="editingConsumer?.ca_number"></span></p>
                            </div>
                        </div>
                        <button @click="showEditConsumerModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <form :action="'/mrus/{{ $mru->id }}/consumers/' + (editingConsumer ? editingConsumer.id : '')" method="POST" class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Consumer Name</label>
                            <input type="text" name="consumer_name" x-model="editingConsumer.consumer_name" placeholder="Full name" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Meter Number</label>
                                <input type="text" name="meter_no" x-model="editingConsumer.meter_no" placeholder="e.g. 3808220" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Mobile</label>
                                <input type="text" name="mobile" x-model="editingConsumer.mobile" placeholder="Phone number" class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Address</label>
                            <input type="text" name="address" x-model="editingConsumer.address" placeholder="Address" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Status</label>
                            <select name="status" x-model="editingConsumer.status" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showEditConsumerModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">Cancel</button>
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Bulk Paste CAs -->
            <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showImportModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                📥
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Bulk Import CA Numbers</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Paste plain CAs, CSV rows, or TSV data</p>
                            </div>
                        </div>
                        <button @click="showImportModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <form method="POST" action="{{ route('mrus.consumers.import', $mru) }}" class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Paste CA Numbers (one per line):</label>
                            <textarea name="ca_data" x-model="bulkImportText" rows="7" placeholder="10230046961&#10;102300783538&#10;102300783541" required class="w-full text-xs font-mono rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white p-3 focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                            <span>Detected Lines: <strong class="font-mono text-slate-900 dark:text-white" x-text="detectedLinesCount">0</strong></span>
                            <span class="text-slate-400">Non-numeric headers skipped</span>
                        </div>
                        <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showImportModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">Cancel</button>
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">Import CAs</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Start Billing Cycle for this MRU -->
            <div x-show="showStartBillingModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!billingInProgress) showStartBillingModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ⚡
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">New Billing Cycle</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Launch monthly cycle for MRU {{ $mru->code }}</p>
                            </div>
                        </div>
                        <button @click="showStartBillingModal = false" :disabled="billingInProgress" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        <!-- For MRU Info Card -->
                        <div class="bg-slate-50 dark:bg-slate-800/80 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Target MRU Workspace</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white">{{ $mru->name }} ({{ $mru->code }})</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-xl text-xs font-mono font-bold bg-blue-50 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 border border-blue-100 dark:border-blue-800/60">
                                {{ $consumers->total() }} Consumers
                            </span>
                        </div>

                        <!-- Billing Month & Year Selectors -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Billing Month -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Month</label>
                                <select x-model="cycleMonth" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    <option value="1">January</option>
                                    <option value="2">February</option>
                                    <option value="3">March</option>
                                    <option value="4">April</option>
                                    <option value="5">May</option>
                                    <option value="6">June</option>
                                    <option value="7">July</option>
                                    <option value="8">August</option>
                                    <option value="9">September</option>
                                    <option value="10">October</option>
                                    <option value="11">November</option>
                                    <option value="12">December</option>
                                </select>
                            </div>

                            <!-- Billing Year -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Billing Year</label>
                                <select x-model="cycleYear" class="w-full text-xs font-semibold rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white py-2.5 px-3 focus:ring-2 focus:ring-blue-500">
                                    @php
                                        $currYear = (int) date('Y');
                                        $availableYears = range(max(2020, $currYear - 3), $currYear + 5);
                                        rsort($availableYears);
                                    @endphp
                                    @foreach($availableYears as $yr)
                                        <option value="{{ $yr }}">{{ $yr }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Tip Box -->
                        <div class="p-3.5 bg-blue-50/60 dark:bg-blue-950/40 rounded-2xl border border-blue-100 dark:border-blue-900/60 text-[11px] text-blue-800 dark:text-cyan-300 leading-relaxed">
                            💡 <strong>Tip:</strong> Choose <strong>"Create Cycle Only"</strong> to initialize the billing session workspace immediately with preceding readings, or <strong>"Create & Download All"</strong> to fetch official PDFs right away.
                        </div>

                        <!-- Live Progress Box -->
                        <div x-show="billingInProgress" class="bg-slate-950 text-cyan-300 p-4 rounded-2xl font-mono text-xs space-y-1.5 border border-slate-800 shadow-inner">
                            <div class="flex items-center gap-2 text-white font-bold">
                                <svg class="animate-spin h-4 w-4 text-cyan-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                <span x-text="executingAction === 'create_only' ? 'Initializing cycle workspace...' : 'Launching cycle & pulling PDFs...'"></span>
                            </div>
                            <div class="text-slate-400 text-[10px]">Processing consumers concurrently. Please wait...</div>
                        </div>

                        <!-- Cycle Overage Confirmation Alert -->
                        <div x-show="cycleOverageRequired" class="p-4 bg-amber-50 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700/80 rounded-2xl space-y-2">
                            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold text-xs">
                                <span>⚠️</span> Consumer Quota Notice
                            </div>
                            <p class="text-xs text-amber-900 dark:text-amber-200" x-text="cycleOverageMessage"></p>
                            <button type="button" @click="triggerMruBilling(executingAction, true)" :disabled="billingInProgress" class="w-full mt-2 py-2.5 px-4 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow transition flex items-center justify-center gap-1.5">
                                <span>✓</span> Confirm & Pay ₹<span x-text="cycleOverageAmount"></span> from Wallet
                            </button>
                        </div>

                        <!-- Result message -->
                        <div x-show="billingResult && !cycleOverageRequired" class="p-3.5 rounded-2xl text-xs font-semibold" :class="billingResult?.success ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800'" x-text="billingResult?.message"></div>
                    </div>

                    <!-- Modal Actions: Two distinct choices -->
                    <div class="p-4 sm:p-6 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-2.5">
                        <button type="button" @click="showStartBillingModal = false" :disabled="billingInProgress" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-center">
                            Cancel
                        </button>

                        <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                            <button type="button" @click="triggerMruBilling('create_only')" :disabled="billingInProgress || {{ $consumers->total() }} === 0" class="w-full sm:w-auto px-4 py-2.5 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 disabled:opacity-40 text-slate-800 dark:text-slate-200 rounded-xl text-xs font-bold transition text-center">
                                ➕ Create Cycle Only
                            </button>
                            <button type="button" @click="triggerMruBilling('download_all')" :disabled="billingInProgress || {{ $consumers->total() }} === 0" class="w-full sm:w-auto px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-1">
                                <span>⚡</span> Create & Download All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: Edit / Rename MRU Workspace -->
            <div x-show="showEditMruModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="showEditMruModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-cyan-400 flex items-center justify-center font-bold text-base">
                                ✏️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit & Rename MRU</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Update MRU code and workspace name</p>
                            </div>
                        </div>
                        <button @click="showEditMruModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">✕</button>
                    </div>

                    <form method="POST" action="{{ route('mrus.update', $mru) }}" class="overflow-y-auto p-4 sm:p-6 space-y-4">
                        @csrf
                        @method('PUT')

                        <div class="p-3.5 bg-blue-50 dark:bg-blue-950/50 rounded-2xl border border-blue-100 dark:border-blue-800/60 text-xs text-blue-800 dark:text-blue-200 leading-relaxed">
                            💡 <strong>Smart Storage Migration:</strong> Changing the MRU Code will automatically move all physical PDF storage folders on disk and update all bill file paths atomically.
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Code *</label>
                            <input type="text" name="code" value="{{ $mru->code }}" required class="w-full text-xs font-mono uppercase rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">MRU Name / Area *</label>
                            <input type="text" name="name" value="{{ $mru->name }}" required class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">Status</label>
                            <select name="status" class="w-full text-xs rounded-xl border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500">
                                <option value="active" {{ $mru->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $mru->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2 sm:gap-2.5 border-t border-slate-100 dark:border-slate-800">
                            <button type="button" @click="showEditMruModal = false" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition text-center">
                                Cancel
                            </button>
                            <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition text-center">
                                Save & Sync Paths
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: Delete MRU Confirmation -->
            <div x-show="showDeleteMruModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                <div @click.outside="if(!isDeletingMru) showDeleteMruModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-md my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                    <div class="p-4 sm:p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center font-bold text-base">
                                🗑️
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">Delete MRU Workspace</h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Permanent removal of workspace & files</p>
                            </div>
                        </div>
                        <button @click="showDeleteMruModal = false" :disabled="isDeletingMru" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 disabled:opacity-40 p-1">✕</button>
                    </div>

                    <div class="p-4 sm:p-6 space-y-4 overflow-y-auto">
                        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">MRU Name</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ $mru->name }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">MRU Code</span>
                                <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ $mru->code }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Master Consumers</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ number_format($consumers->total()) }} Accounts</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-slate-500 font-semibold uppercase">Billing Sessions</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $sessions->count() }} Sessions</span>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-800 dark:text-rose-300 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold">
                                <span>⚠️</span>
                                <span>Warning: Irreversible Action</span>
                            </div>
                            <p class="text-[11px] text-rose-700 dark:text-rose-400 leading-relaxed">
                                Deleting this MRU will permanently delete all consumer accounts, all historical billing sessions, and purge all physical PDF bill files stored on disk for MRU <strong>{{ $mru->code }}</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="p-4 sm:p-6 border-t border-slate-100 dark:border-slate-800 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5">
                        <button type="button" @click="showDeleteMruModal = false" :disabled="isDeletingMru" class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200 transition text-center">
                            Cancel
                        </button>
                        <button type="button" 
                                @click="confirmDeleteMru()" 
                                :disabled="isDeletingMru"
                                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-rose-500/20">
                            <span x-show="!isDeletingMru">🗑️ Delete MRU & Files</span>
                            <span x-show="isDeletingMru" class="flex items-center gap-1">
                                <span class="animate-spin text-xs">⏳</span> Deleting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        function mruHubApp() {
            return {
                activeTab: 'sessions',
                showAddConsumerModal: false,
                showEditConsumerModal: false,
                showImportModal: false,
                showStartBillingModal: false,
                showEditMruModal: false,
                showDeleteMruModal: false,
                isDeletingMru: false,
                editingConsumer: null,
                bulkImportText: '',
                cycleMonth: {{ now()->month }},
                cycleYear: {{ now()->year }},
                executingAction: 'download_all',
                billingInProgress: false,
                billingResult: null,

                cycleOverageRequired: false,
                cycleOverageAmount: 0,
                cycleOverageMessage: '',

                get detectedLinesCount() {
                    if (!this.bulkImportText.trim()) return 0;
                    return this.bulkImportText.trim().split(/\r?\n/).filter(line => line.trim().length > 0).length;
                },

                openEditConsumerModal(consumer) {
                    this.editingConsumer = {
                        id: consumer.id,
                        ca_number: consumer.ca_number,
                        consumer_name: consumer.consumer_name || '',
                        meter_no: consumer.meter_no || '',
                        mobile: consumer.mobile || '',
                        address: consumer.address || '',
                        status: consumer.status || 'active'
                    };
                    this.showEditConsumerModal = true;
                },

                openDownloadForSession(month, year) {
                    this.cycleMonth = month;
                    this.cycleYear = year;
                    this.cycleOverageRequired = false;
                    this.cycleOverageAmount = 0;
                    this.cycleOverageMessage = '';
                    this.showStartBillingModal = true;
                },

                syncMissingSession(month, year) {
                    this.billingInProgress = true;
                    this.billingResult = null;
                    this.executingAction = 'sync_missing';

                    fetch('/mrus/{{ $mru->id }}/sync-missing', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            billing_month: month,
                            billing_year: year
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || 'Sync failed');
                        }
                        return data;
                    })
                    .then(json => {
                        this.billingResult = json;
                        if (json.success) {
                            setTimeout(() => {
                                window.location.reload();
                            }, 800);
                        }
                    })
                    .catch(err => {
                        this.billingResult = {
                            success: false,
                            message: err.message || 'An error occurred while syncing missing bills.'
                        };
                    })
                    .finally(() => {
                        this.billingInProgress = false;
                    });
                },

                triggerMruBilling(actionType = 'download_all', payOverage = false) {
                    this.executingAction = actionType;
                    this.billingInProgress = true;
                    this.billingResult = null;

                    fetch('/mrus/{{ $mru->id }}/start-billing', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            billing_month: this.cycleMonth,
                            billing_year: this.cycleYear,
                            action_type: actionType,
                            pay_overage: payOverage ? 1 : 0
                        })
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (res.status === 402 && data.requires_overage) {
                            this.cycleOverageRequired = true;
                            this.cycleOverageAmount = data.amount_due || 0;
                            this.cycleOverageMessage = data.message || 'Consumer quota exceeded. Wallet deduction required.';
                            throw new Error(data.message);
                        }
                        if (!res.ok) {
                            throw new Error(data.message || 'Cycle creation failed');
                        }
                        return data;
                    })
                    .then(json => {
                        this.billingResult = json;
                        if (json.success) {
                            setTimeout(() => {
                                if (json.redirect_url) {
                                    window.location.href = json.redirect_url;
                                } else {
                                    window.location.reload();
                                }
                            }, 800);
                        }
                    })
                    .catch(err => {
                        this.billingResult = {
                            success: false,
                            message: err.message || 'An error occurred while executing cycle request.'
                        };
                    })
                    .finally(() => {
                        this.billingInProgress = false;
                    });
                },

                confirmDeleteMru() {
                    this.isDeletingMru = true;
                    fetch('{{ route('mrus.destroy', $mru) }}', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async res => {
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.message || 'Error deleting MRU');
                        return data;
                    })
                    .then(data => {
                        this.isDeletingMru = false;
                        this.showDeleteMruModal = false;
                        window.location.href = data.redirect_url || '{{ route('mrus.index') }}';
                    })
                    .catch(err => {
                        this.isDeletingMru = false;
                        alert('Failed to delete MRU: ' + err.message);
                    });
                }
            };
        }
    </script>
</x-app-layout>
