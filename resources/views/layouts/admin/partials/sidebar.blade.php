<!-- Admin Enterprise Sidebar with 5 Collapsible Domain Pillars -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
       class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800 flex flex-col justify-between shrink-0 min-h-screen transition-transform duration-200 ease-in-out">
    <div class="flex flex-col h-full overflow-hidden">
        @include('layouts.admin.partials.sidebar-logo')

        <!-- Independent Smooth Scroll Navigation (5 Collapsible Domain Pillars) -->
        <nav class="flex-1 overflow-y-auto max-h-[calc(100vh-5rem)] p-3 space-y-1.5 text-xs font-semibold custom-scrollbar">
            @include('layouts.admin.partials.nav.operations')
            @include('layouts.admin.partials.nav.finance')
            @include('layouts.admin.partials.nav.growth')
            @include('layouts.admin.partials.nav.communications')
            @include('layouts.admin.partials.nav.system')
        </nav>
    </div>

    @include('layouts.admin.partials.sidebar-footer')
</aside>
