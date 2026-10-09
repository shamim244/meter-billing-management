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
                        <th class="py-3.5 px-4 text-center">Tariff</th>
                        <th class="py-3.5 px-4 text-center">Basis</th>
                        <th class="py-3.5 px-4 text-center">Baseline Amount</th>
                        <th class="py-3.5 px-4 text-center">Initial Reading</th>
                        <th class="py-3.5 px-4 text-center">Meter No</th>
                        <th class="py-3.5 px-4 text-center">Mobile</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                    @forelse($consumers as $consumer)
                        @include('mrus.show-partials.consumer-table-row', ['consumer' => $consumer])
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="text-3xl mb-2">👥</div>
                                @if(!empty($search))
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No consumers found matching "{{ $search }}"</p>
                                    <a href="{{ route('mrus.show', $mru) }}" class="inline-block mt-2 text-xs text-blue-600 dark:text-cyan-400 hover:underline">Clear Search</a>
                                @else
                                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No consumers registered in this MRU workspace yet</p>
                                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1 max-w-sm mx-auto">Add consumer accounts individually or paste CA numbers in bulk to start billing.</p>
                                    <div class="flex items-center justify-center gap-2.5 mt-4">
                                        <button type="button" @click="showAddConsumerModal = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                                            + Add Consumer
                                        </button>
                                        <button type="button" @click="showImportModal = true" class="px-3.5 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 transition">
                                            📥 Bulk Paste CAs
                                        </button>
                                    </div>
                                @endif
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
