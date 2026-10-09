<!-- Filter Pills & Search Bar -->
<div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
    <!-- Status Filter Tabs -->
    <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800/80 rounded-2xl overflow-x-auto">
        <a href="{{ route('user-panel.issues', ['status' => 'all', 'q' => $search]) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            All ({{ $stats['total'] }})
        </a>
        <a href="{{ route('user-panel.issues', ['status' => 'active', 'q' => $search]) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'active' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            Active ({{ $stats['active'] }})
        </a>
        <a href="{{ route('user-panel.issues', ['status' => 'resolved', 'q' => $search]) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $status === 'resolved' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
            Resolved ({{ $stats['resolved'] }})
        </a>
    </div>

    <!-- Search Input -->
    <form method="GET" action="{{ route('user-panel.issues') }}" class="relative w-full sm:w-72">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" 
               name="q" 
               value="{{ $search }}" 
               placeholder="Search title, ticket code, CA..." 
               class="w-full text-xs font-medium pl-9 pr-4 py-2 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs">🔍</span>
        @if(!empty($search))
            <a href="{{ route('user-panel.issues', ['status' => $status]) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs">✕</a>
        @endif
    </form>
</div>
