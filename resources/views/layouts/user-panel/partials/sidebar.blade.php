<!-- User Panel Sidebar -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
       class="fixed md:sticky top-0 inset-y-0 left-0 z-50 w-64 sm:w-72 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 flex flex-col justify-between shrink-0 h-screen transition-transform duration-200 ease-in-out shadow-lg md:shadow-none overflow-y-auto">
    <div>
        @include('layouts.user-panel.partials.sidebar-brand')
        @include('layouts.user-panel.partials.sidebar-badge')

        <!-- User Panel Navigation Links (Categorized & Calibrated) -->
        <nav class="p-4 space-y-4 text-xs font-semibold">
            @include('layouts.user-panel.partials.nav.account')
            @include('layouts.user-panel.partials.nav.growth')
            @include('layouts.user-panel.partials.nav.preferences')
            @include('layouts.user-panel.partials.nav.support')
        </nav>
    </div>

    @include('layouts.user-panel.partials.sidebar-footer')
</aside>
