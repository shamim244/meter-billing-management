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
            <div class="col-span-full bg-white dark:bg-slate-900 p-10 sm:p-12 text-center rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
                @if($consumers->total() === 0)
                    <div class="w-14 h-14 bg-amber-50 dark:bg-amber-950/40 text-amber-500 rounded-full flex items-center justify-center mx-auto text-2xl">
                        👥
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Step 1: Add Consumers First</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 leading-relaxed">
                            This MRU workspace has no registered consumers. You need to register or import consumer account numbers (CAs) before you can initialize a monthly billing cycle.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-2.5 pt-2">
                        <button type="button" @click="showAddConsumerModal = true" class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                            <span>+</span> Add First Consumer
                        </button>
                        <button type="button" @click="showImportModal = true" class="px-4 py-2.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition flex items-center gap-1.5">
                            <span>📥</span> Bulk Paste CA Numbers
                        </button>
                        <button type="button" @click="activeTab = 'consumers'" class="px-4 py-2.5 text-blue-600 dark:text-cyan-400 hover:underline text-xs font-semibold">
                            View Consumer Master Tab →
                        </button>
                    </div>
                @else
                    <div class="w-14 h-14 bg-blue-50 dark:bg-blue-950/40 text-blue-500 dark:text-blue-400 rounded-full flex items-center justify-center mx-auto mb-1 text-2xl">
                        📅
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">No monthly billing sessions recorded yet</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mt-1 mb-3">
                        Initialize your first billing cycle (e.g. {{ date('F Y') }}) for this MRU workspace.
                    </p>
                    <button @click="showStartBillingModal = true" class="px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 shadow-sm transition">
                        + Create First Billing Cycle
                    </button>
                @endif
            </div>
        @endforelse
    </div>
</div>
