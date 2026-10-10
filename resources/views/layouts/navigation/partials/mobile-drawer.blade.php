<!-- Responsive Mobile & Tablet Drawer with Tap-To-Expand Accordion Sections -->
<div id="mobile-navigation-drawer"
     :class="{'block': open, 'hidden': ! open}" 
     x-data="{ 
         activeSection: '{{ request()->routeIs('admin.*') ? 'admin' : (request()->routeIs('user-panel.*', 'referrals.*', 'wallet.*', 'reports.*') ? 'growth' : 'operations') }}' 
     }"
     class="hidden lg:hidden bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 px-4 pt-3 pb-6 max-h-[calc(100vh-4.5rem)] overflow-y-auto space-y-3 custom-scrollbar overscroll-contain">
    
    <!-- 1. Operations Accordion Section -->
    @include('layouts.navigation.partials.mobile.operations-accordion')

    <!-- 2. Growth & Account Accordion Section -->
    @include('layouts.navigation.partials.mobile.growth-accordion')

    <!-- 3. SaaS Administration Accordion Section (Admins Only) -->
    @if(Auth::user()->hasRole('admin'))
        @include('layouts.navigation.partials.mobile.admin-accordion')
    @endif

    <!-- 4. Mobile User Profile Block -->
    @include('layouts.navigation.partials.mobile.user-profile')
</div>
