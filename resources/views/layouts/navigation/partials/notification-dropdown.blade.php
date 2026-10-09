<!-- Notification Bell Dropdown -->
<div x-data="navigationNotifications('{{ route('notifications.recent') }}', '{{ csrf_token() }}')" x-init="fetchNotifs()" class="relative">
    <button @click="open = !open; if (open) fetchNotifs();" 
            type="button" 
            class="relative w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-sm border border-slate-200/60 dark:border-slate-700/60"
            title="Notifications">
        <span>🔔</span>
        <template x-if="unreadCount > 0">
            <span class="absolute -top-1 -right-1 px-1.5 py-0.2 bg-rose-500 text-white text-[10px] font-extrabold rounded-full animate-pulse" x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
        </template>
    </button>

    <!-- Dropdown Content -->
    <div x-show="open" @click.away="open = false" x-cloak class="absolute right-0 mt-2 w-80 sm:w-96 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 z-50 overflow-hidden">
        <div class="p-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Notifications</span>
                <template x-if="unreadCount > 0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-500" x-text="unreadCount + ' unread'"></span>
                </template>
            </div>
            <a href="{{ route('notifications.preferences') }}" class="text-[11px] text-indigo-500 hover:underline font-semibold">Preferences</a>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800/80 max-h-72 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="py-8 text-center text-xs text-slate-400">
                    No notifications right now.
                </div>
            </template>
            <template x-for="item in notifications" :key="item.id">
                <div class="p-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/50" :class="{'bg-indigo-50/50 dark:bg-indigo-950/20': !item.is_read}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="space-y-0.5 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-slate-900 dark:text-white" x-text="item.title"></span>
                                <template x-if="item.priority === 'critical'">
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-rose-500/20 text-rose-400">CRITICAL</span>
                                </template>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-400 line-clamp-2 leading-relaxed" x-text="item.body"></p>
                            <div class="text-[10px] text-slate-400 pt-0.5" x-text="item.created_at_human"></div>
                        </div>
                        <template x-if="!item.is_read">
                            <button @click="markRead(item.id)" class="text-[10px] text-indigo-500 hover:underline shrink-0 font-semibold" title="Mark Read">✓</button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <div class="p-2.5 bg-slate-50 dark:bg-slate-950/80 border-t border-slate-100 dark:border-slate-800 text-center">
            <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                View all notifications →
            </a>
        </div>
    </div>
</div>
