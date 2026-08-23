<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 transition-colors">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <!-- Left Side: Brand Logo & Navigation Links -->
            <div class="flex items-center gap-6">
                <!-- Brand Logo -->
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 p-0.5 shadow-md shadow-brand-500/20 group-hover:scale-105 transition duration-150">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <span class="text-base">⚡</span>
                        </div>
                    </div>
                    <div>
                        <div class="text-base font-black tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5 leading-none">
                            <span>NBPDCL</span>
                            <span class="text-[10px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-brand-500/15 text-brand-600 dark:text-brand-400 border border-brand-500/30">SaaS</span>
                        </div>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden sm:flex items-center gap-1.5">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <span>📊</span>
                        <span>Dashboard</span>
                    </x-nav-link>

                    <x-nav-link :href="route('mrus.index')" :active="request()->routeIs('mrus.*')">
                        <span>🗂️</span>
                        <span>MRUs</span>
                    </x-nav-link>

                    <x-nav-link :href="route('processing.index')" :active="request()->routeIs('processing.*')">
                        <span>⚡</span>
                        <span>Processing</span>
                    </x-nav-link>

                    <x-nav-link :href="route('pdf-manager.index')" :active="request()->routeIs('pdf-manager.*')">
                        <span>📑</span>
                        <span>PDF Manager</span>
                    </x-nav-link>

                    <x-nav-link :href="route('wallet.index')" :active="request()->routeIs('wallet.*')">
                        <span>👛</span>
                        <span>Wallet</span>
                    </x-nav-link>

                    <x-nav-link :href="route('reports.usage')" :active="request()->routeIs('reports.*')">
                        <span>📈</span>
                        <span>Reports</span>
                    </x-nav-link>

                    <x-nav-link :href="route('user-panel.index')" :active="request()->routeIs('user-panel.*')">
                        <span>👤</span>
                        <span>User Panel</span>
                    </x-nav-link>

                    @if(Auth::user()->hasRole('admin'))
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                            <span>👑</span>
                            <span>Admin</span>
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Right Side Controls: Status, Theme Toggle & User Menu -->
            <div class="hidden sm:flex sm:items-center gap-3">
                
                <!-- System Status Indicator -->
                <div class="hidden md:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 dark:bg-emerald-500/15 border border-emerald-500/20 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Online</span>
                </div>

                <!-- Notification Bell Dropdown -->
                <div x-data="{
                    open: false,
                    unreadCount: 0,
                    notifications: [],
                    async fetchNotifs() {
                        try {
                            const res = await fetch('{{ route('notifications.recent') }}');
                            const data = await res.json();
                            this.unreadCount = data.unread_count || 0;
                            this.notifications = data.notifications || [];
                        } catch (e) {}
                    },
                    async markRead(id) {
                        try {
                            await fetch('/notifications/' + id + '/read', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                            });
                            this.fetchNotifs();
                        } catch (e) {}
                    }
                }" x-init="fetchNotifs()" class="relative">
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

                <!-- Theme Toggle Button -->
                <div x-data="{
                    darkMode: document.documentElement.classList.contains('dark'),
                    toggle() {
                        this.darkMode = !this.darkMode;
                        if (this.darkMode) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('color-theme', 'dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('color-theme', 'light');
                        }
                    }
                }">
                    <button @click="toggle()" 
                            type="button" 
                            class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-sm border border-slate-200/60 dark:border-slate-700/60" 
                            :title="darkMode ? 'Switch to Light Theme' : 'Switch to Dark Theme'">
                        <span x-show="!darkMode">🌙</span>
                        <span x-show="darkMode" x-cloak>☀️</span>
                    </button>
                </div>

                <!-- User Dropdown Menu -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50 hover:bg-slate-100 dark:bg-slate-800/80 dark:hover:bg-slate-800 text-slate-800 dark:text-slate-200 font-semibold text-xs transition shadow-sm">
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-[10px] flex items-center justify-center font-mono shadow-sm">
                                {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                            </div>
                            <span class="truncate max-w-[120px]">{{ Auth::user()->name }}</span>
                            @if(Auth::user()->hasRole('admin'))
                                <span class="px-1.5 py-0.2 text-[9px] font-black uppercase rounded bg-indigo-500/15 text-indigo-600 dark:text-indigo-300 border border-indigo-500/30">Admin</span>
                            @endif
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- User Card Info -->
                        <div class="px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800">
                            <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ Auth::user()->name }}</div>
                            <div class="text-[11px] font-mono text-slate-400 truncate">{{ Auth::user()->email }}</div>
                            <div class="mt-1 flex items-center gap-1.5">
                                <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.2 rounded {{ Auth::user()->hasRole('admin') ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' }}">
                                    {{ Auth::user()->hasRole('admin') ? 'Administrator' : 'Operator' }}
                                </span>
                            </div>
                        </div>

                        <div class="py-1">
                            <x-dropdown-link :href="route('user-panel.index')">
                                <span>👤</span>
                                <span>User Control Panel</span>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('user-panel.subscription')">
                                <span>💳</span>
                                <span>Subscription & Quotas</span>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('user-panel.shortcuts')">
                                <span>⌨️</span>
                                <span>Keyboard Shortcuts</span>
                            </x-dropdown-link>

                            <x-dropdown-link :href="route('user-panel.preferences')">
                                <span>⚙️</span>
                                <span>Preferences</span>
                            </x-dropdown-link>

                            @if(Auth::user()->hasRole('admin'))
                                <x-dropdown-link :href="route('admin.dashboard')">
                                    <span>👑</span>
                                    <span>Admin Panel</span>
                                </x-dropdown-link>
                            @endif
                        </div>

                        <!-- Log Out -->
                        <div class="pt-1 border-t border-slate-100 dark:border-slate-800">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40">
                                    <span>🚪</span>
                                    <span>Log Out</span>
                                </x-dropdown-link>
                            </form>
                        </div>
                    </x-slot>
                </x-dropdown>

            </div>

            <!-- Mobile Controls: Theme Toggle & Hamburger -->
            <div class="flex items-center gap-2 sm:hidden">
                <!-- Theme Toggle Button Mobile -->
                <div x-data="{
                    darkMode: document.documentElement.classList.contains('dark'),
                    toggle() {
                        this.darkMode = !this.darkMode;
                        if (this.darkMode) {
                            document.documentElement.classList.add('dark');
                            localStorage.setItem('color-theme', 'dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                            localStorage.setItem('color-theme', 'light');
                        }
                    }
                }">
                    <button @click="toggle()" 
                            type="button" 
                            class="w-9 h-9 rounded-xl flex items-center justify-center bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition shadow-xs border border-slate-200/60 dark:border-slate-700/60" 
                            :title="darkMode ? 'Switch to Light Theme' : 'Switch to Dark Theme'">
                        <span x-show="!darkMode">🌙</span>
                        <span x-show="darkMode" x-cloak>☀️</span>
                    </button>
                </div>

                <!-- Hamburger Button -->
                <button @click="open = ! open" class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition" aria-label="Toggle Navigation">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- Responsive Mobile Drawer -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-4 pt-3 pb-5 space-y-3">
        <div class="space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span>📊</span>
                <span class="font-bold">Dashboard</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('mrus.index')" :active="request()->routeIs('mrus.*')">
                <span>🗂️</span>
                <span class="font-bold">MRU Workspaces</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('processing.index')" :active="request()->routeIs('processing.*')">
                <span>⚡</span>
                <span class="font-bold">Data Processing</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('pdf-manager.index')" :active="request()->routeIs('pdf-manager.*')">
                <span>📑</span>
                <span class="font-bold">PDF Manager</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('wallet.index')" :active="request()->routeIs('wallet.*')">
                <span>👛</span>
                <span class="font-bold">Wallet & Ledger</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('reports.usage')" :active="request()->routeIs('reports.*')">
                <span>📈</span>
                <span class="font-bold">Usage & Reports</span>
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('user-panel.index')" :active="request()->routeIs('user-panel.*')">
                <span>👤</span>
                <span class="font-bold">User Panel</span>
            </x-responsive-nav-link>

            @if(Auth::user()->hasRole('admin'))
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    <span>👑</span>
                    <span class="font-bold">Admin Panel</span>
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Mobile User Profile Block -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
            <div class="flex items-center justify-between px-2 py-1">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-cyan-400 text-white font-black text-xs flex items-center justify-center font-mono shadow-sm">
                        {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-bold text-sm text-slate-900 dark:text-white">{{ Auth::user()->name }}</div>
                        <div class="text-xs font-mono text-slate-400 truncate max-w-[200px]">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded {{ Auth::user()->hasRole('admin') ? 'bg-indigo-500/15 text-indigo-600 dark:text-indigo-300' : 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' }}">
                    {{ Auth::user()->hasRole('admin') ? 'Admin' : 'Operator' }}
                </span>
            </div>

            <div class="space-y-1 pt-1">
                <x-responsive-nav-link :href="route('user-panel.subscription')">
                    <span>💳</span>
                    <span>Subscription & Quotas</span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('user-panel.shortcuts')">
                    <span>⌨️</span>
                    <span>Keyboard Shortcuts</span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('user-panel.preferences')">
                    <span>⚙️</span>
                    <span>General Preferences</span>
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('user-panel.profile')">
                    <span>👤</span>
                    <span>Profile & Security</span>
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-rose-600 dark:text-rose-400">
                        <span>🚪</span>
                        <span>Log Out</span>
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
