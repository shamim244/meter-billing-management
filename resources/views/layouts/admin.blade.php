<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Admin Panel' }} — NBPDCL SaaS</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & Alpine.js CDNs -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="/js/keyboard-shortcuts.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body x-data="{ sidebarOpen: false }" class="font-sans antialiased bg-slate-900 text-slate-100 min-h-screen flex flex-col md:flex-row">

    <!-- Mobile Drawer Backdrop -->
    <div x-show="sidebarOpen" 
         x-cloak 
         @click="sidebarOpen = false" 
         class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm md:hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
           class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800 flex flex-col justify-between shrink-0 min-h-screen transition-transform duration-200 ease-in-out">
        <div>
            <!-- Admin Logo -->
            <div class="h-16 px-6 border-b border-slate-800/80 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-500 flex items-center justify-center font-bold text-white shadow-md shadow-indigo-500/30">
                        👑
                    </div>
                    <span class="font-extrabold text-base tracking-tight text-white">SaaS Admin</span>
                </div>
                <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white p-1">
                    ✕
                </button>
            </div>

            <!-- Navigation Links (Categorized & Calibrated) -->
            <nav class="p-4 space-y-5 text-xs font-semibold">
                
                <!-- Section 1: Operations & Agents -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
                        Operations & Agents
                    </div>

                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">📊</span>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.users.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">👥</span>
                        <span>Users & Billing Agents</span>
                    </a>

                    <a href="{{ route('admin.mrus.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.mrus.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">🗂️</span>
                        <span>MRU Master List</span>
                    </a>

                    <a href="{{ route('admin.bills.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.bills.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">📑</span>
                        <span>All Bills Inspector</span>
                    </a>
                </div>

                <!-- Section 2: Finance & Payments -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
                        Finance & Ledger
                    </div>

                    <a href="{{ route('admin.wallets.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.wallets.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">👛</span>
                        <span>Agent Wallets</span>
                    </a>

                    @php
                        $navPendingPayments = \App\Models\Payment::withoutUserScope()->where('status', \App\Enums\PaymentStatus::PENDING_VERIFICATION->value)->count();
                        $isPaymentRoute = request()->routeIs('admin.payments.*');
                    @endphp
                    <div x-data="{ open: {{ $isPaymentRoute ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="open = !open" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ $isPaymentRoute ? 'bg-slate-900 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base">💳</span>
                                <span>Payments & Gateway</span>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($navPendingPayments > 0)
                                    <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500 text-slate-950 animate-pulse">
                                        {{ $navPendingPayments }}
                                    </span>
                                @endif
                                <svg class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180 text-indigo-400' : 'text-slate-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </button>

                        <!-- Sub-Menu Links -->
                        <div x-show="open" x-cloak class="pl-4 pr-1 py-1 space-y-1 border-l-2 border-slate-800 ml-5 my-1">
                            <a href="{{ route('admin.payments.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.index') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>📋</span> All Transactions
                            </a>

                            <a href="{{ route('admin.payments.manual') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.manual') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <div class="flex items-center gap-2.5">
                                    <span>⏳</span> Manual Approvals
                                </div>
                                @if($navPendingPayments > 0)
                                    <span class="px-1.5 py-0.2 text-[9px] font-bold rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        {{ $navPendingPayments }}
                                    </span>
                                @endif
                            </a>

                            <a href="{{ route('admin.payments.analytics') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.analytics') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>📊</span> Revenue Analytics
                            </a>

                            <a href="{{ route('admin.payments.audit') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.audit') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>📜</span> Audit Trail
                            </a>

                            <a href="{{ route('admin.payments.settings') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.payments.settings') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>⚙️</span> Gateway Settings
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Plans & Subscriptions -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
                        Plans & Subscriptions
                    </div>

                    <a href="{{ route('admin.plans.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.plans.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">📋</span>
                        <span>Subscription Plans</span>
                    </a>

                    <a href="{{ route('admin.subscriptions.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.subscriptions.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">🔄</span>
                        <span>Agent Subscriptions</span>
                    </a>
                </div>

                <!-- Section 4: Marketing & Growth -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
                        Marketing & Growth
                    </div>

                    <a href="{{ route('admin.coupons.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.coupons.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">🎟️</span>
                        <span>Coupon Campaigns</span>
                    </a>

                    <a href="{{ route('admin.referrals.settings') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.referrals.*') ? 'bg-purple-600 text-white font-bold shadow-lg shadow-purple-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">🎁</span>
                        <span>Refer & Earn Program</span>
                    </a>
                </div>

                <!-- Section 5: System Config & Reports -->
                <div class="space-y-1">
                    <div class="px-3 py-1 text-[10px] font-black uppercase text-slate-500 tracking-wider">
                        System Config & Logs
                    </div>

                    <a href="{{ route('admin.shortcuts.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.shortcuts.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">⌨️</span>
                        <span>Shortcut Defaults</span>
                    </a>

                    <a href="{{ route('admin.tags.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.tags.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">🏷️</span>
                        <span>Review Tags</span>
                    </a>

                    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.reports.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">📊</span>
                        <span>Usage & Health Reports</span>
                    </a>

                    <!-- Notification Engine Dropdown -->
                    <div x-data="{ notifNav: {{ request()->routeIs('admin.notifications.*') ? 'true' : 'false' }} }" class="space-y-1">
                        <button type="button" @click="notifNav = !notifNav" class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.notifications.*') ? 'bg-slate-900 text-white font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                            <div class="flex items-center gap-3">
                                <span class="text-base">🔔</span>
                                <span>Notifications Hub</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform text-slate-500" :class="notifNav ? 'rotate-180 text-indigo-400' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="notifNav" x-cloak class="pl-6 pr-1 py-1 space-y-1 border-l-2 border-slate-800 ml-5">
                            <a href="{{ route('admin.notifications.email_providers.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.email_providers.*') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>⚡</span> Email Providers
                            </a>
                            <a href="{{ route('admin.notifications.mailbox.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.mailbox.*') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>📫</span> Live Mailbox
                            </a>
                            <a href="{{ route('admin.notifications.templates.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.templates.*') ? 'text-indigo-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>📑</span> Templates
                            </a>
                            <a href="{{ route('admin.notifications.failed_queue') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.notifications.failed_queue') ? 'text-rose-400 bg-slate-900 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-900/60' }}">
                                <span>🚨</span> Failed Queue
                            </a>
                        </div>
                    </div>

                    <a href="{{ route('admin.backups.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('admin.backups.*') ? 'bg-indigo-600 text-white font-bold shadow-lg shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-900' }}">
                        <span class="text-base">💾</span>
                        <span>Disaster Recovery & Backups</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Mode Switcher Bottom Actions -->
        <div class="p-4 border-t border-slate-800/80 space-y-2 bg-slate-950/60">
            <!-- Switch to Dashboard -->
            <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-cyan-400 hover:from-emerald-300 hover:to-cyan-300 shadow-md shadow-cyan-500/20 transition group">
                <span>📊 App Dashboard</span>
                <span class="group-hover:translate-x-0.5 transition">→</span>
            </a>

            <!-- Switch to User Control Panel -->
            <a href="{{ route('user-panel.index') }}" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-bold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 transition">
                <span>👤 User Control Panel</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-950/30 transition">
                    <span>🚪 Log Out</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
        <x-impersonation-banner />
        <!-- Top Nav Header with Mode Switchers -->
        <header class="h-16 bg-slate-950/80 backdrop-blur-md border-b border-slate-800 px-4 sm:px-8 flex items-center justify-between sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 -ml-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-900 md:hidden transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-white truncate">
                    {{ $header ?? 'Administration' }}
                </h2>
            </div>

            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Fast Switch to Dashboard -->
                <a href="{{ route('dashboard') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-cyan-300 bg-cyan-950/60 hover:bg-cyan-900/60 border border-cyan-500/30 transition shadow-xs">
                    <span>📊 Dashboard</span>
                </a>

                <!-- Fast Switch to User Panel -->
                <a href="{{ route('user-panel.index') }}" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 transition shadow-xs">
                    <span>👤 User Panel</span>
                </a>

                <div class="flex items-center gap-2 pl-2 border-l border-slate-800">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-500/15 text-indigo-400 border border-indigo-500/30">
                        👑 Admin
                    </span>
                    <span class="text-xs font-bold text-slate-200 hidden md:inline">{{ Auth::user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div class="px-4 sm:px-8 pt-4 sm:pt-6">
            @if(session('success'))
                <div class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-xs sm:text-sm font-medium flex items-center gap-2">
                    <span>✅</span> {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 rounded-2xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs sm:text-sm font-medium flex items-center gap-2">
                    <span>❌</span> {{ session('error') }}
                </div>
            @endif
        </div>

        <!-- Page Body -->
        <main class="flex-1 p-4 sm:p-8">
            @if(isset($slot))
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </main>
    </div>

</body>
</html>
