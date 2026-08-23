<x-app-layout>
    <div class="space-y-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight flex items-center gap-2.5">
                    <span>🔔</span> Notifications & Alerts
                </h1>
                <p class="text-sm text-slate-400 mt-1">
                    System activity, wallet updates, and billing notifications.
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('notifications.preferences') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-2">
                    <span>⚙️</span> Preferences
                </a>

                @if($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.mark_all_read') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-indigo-600/30">
                            Mark All Read ({{ $unreadCount }})
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="flex items-center gap-2">
            <a href="{{ route('notifications.index', ['filter' => 'all']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $filter === 'all' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border border-slate-800' }}">
                All
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 {{ $filter === 'unread' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border border-slate-800' }}">
                <span>Unread</span>
                @if($unreadCount > 0)
                    <span class="px-1.5 py-0.2 bg-rose-500 text-white text-[10px] font-extrabold rounded-full">{{ $unreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'critical']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $filter === 'critical' ? 'bg-rose-600 text-white shadow-md shadow-rose-600/20' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border border-slate-800' }}">
                Critical Only
            </a>
            <a href="{{ route('notifications.index', ['filter' => 'routine']) }}" class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $filter === 'routine' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-slate-900 text-slate-400 hover:bg-slate-800 border border-slate-800' }}">
                Routine
            </a>
        </div>

        @if(session('success'))
            <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-xs text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <!-- Notifications Feed -->
        <div class="space-y-3">
            @forelse($notifications as $item)
                <div class="p-5 rounded-2xl border transition relative {{ $item->read_at ? 'bg-slate-900/60 border-slate-800/80' : ($item->priority === 'critical' ? 'bg-rose-950/20 border-rose-500/30 shadow-lg shadow-rose-950/20' : 'bg-slate-900 border-indigo-500/30 shadow-lg shadow-indigo-950/20') }}">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="mt-0.5 w-8 h-8 rounded-xl flex items-center justify-center text-base {{ $item->priority === 'critical' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' }}">
                                {{ $item->priority === 'critical' ? '🚨' : '📩' }}
                            </div>

                            <div class="space-y-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-bold {{ $item->read_at ? 'text-slate-300' : 'text-white' }}">
                                        {{ $item->title }}
                                    </h3>
                                    @if($item->priority === 'critical')
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-rose-500/20 text-rose-300 border border-rose-500/30">
                                            CRITICAL
                                        </span>
                                    @endif
                                    @if(!$item->read_at)
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    @endif
                                </div>

                                <p class="text-xs text-slate-300 leading-relaxed">
                                    {{ $item->body }}
                                </p>

                                <div class="text-[11px] text-slate-500 pt-1 font-mono">
                                    {{ $item->created_at->diffForHumans() }} ({{ $item->created_at->format('M d, Y h:i A') }})
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            @if(!$item->read_at)
                                <form method="POST" action="{{ route('notifications.mark_read', $item) }}">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition" title="Mark as read">
                                        ✓ Mark Read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-16 text-center bg-slate-900/60 rounded-3xl border border-slate-800/80 space-y-3">
                    <div class="text-3xl">📭</div>
                    <div class="text-sm font-semibold text-slate-300">No notifications found</div>
                    <p class="text-xs text-slate-500">You're all caught up with your alerts and system messages.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="pt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
