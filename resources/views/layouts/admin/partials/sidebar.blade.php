<!-- Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
       class="fixed md:static inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800 flex flex-col justify-between shrink-0 min-h-screen transition-transform duration-200 ease-in-out">
    <div>
        @include('layouts.admin.partials.sidebar-logo')

        <!-- Navigation Links (Categorized & Calibrated) -->
        <nav class="p-4 space-y-5 text-xs font-semibold">
            @include('layouts.admin.partials.nav.operations')
            @include('layouts.admin.partials.nav.finance')
            @include('layouts.admin.partials.nav.plans')
            @include('layouts.admin.partials.nav.growth')
            @include('layouts.admin.partials.nav.system')
        </nav>
    </div>

    @include('layouts.admin.partials.sidebar-footer')
</aside>
