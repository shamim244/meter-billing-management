<x-app-layout>
    <div x-data="mruHubApp()" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Breadcrumb Navigation -->
            <div class="flex items-center justify-between">
                <a href="{{ route('mrus.index') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-cyan-400 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to MRU Workspaces
                </a>
                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">Workspace #{{ $mru->id }}</span>
            </div>

            <!-- Flash Session Alerts -->
            @include('mrus.partials.flash-alerts')

            <!-- Master Hero Summary Card -->
            @include('mrus.show-partials.hero-card')

            <!-- 0-Consumer Onboarding Banner -->
            @include('mrus.show-partials.onboarding-banner')

            <!-- Tab Switcher & Dynamic Action Toolbar -->
            @include('mrus.show-partials.tab-toolbar')

            <!-- Tab 1: Monthly Billing Sessions Grid -->
            @include('mrus.show-partials.sessions-tab')

            <!-- Tab 2: Consumer Master List Ledger -->
            @include('mrus.show-partials.consumers-tab')

            <!-- Workspace Modals -->
            @include('mrus.show-partials.modals.add-consumer-modal')
            @include('mrus.show-partials.modals.edit-consumer-modal')
            @include('mrus.show-partials.modals.bulk-import-modal')
            @include('mrus.show-partials.modals.start-billing-modal')
            @include('mrus.show-partials.modals.edit-mru-modal')
            @include('mrus.show-partials.modals.delete-mru-modal')

        </div>
    </div>

    <!-- Client-Side Configuration Bridge & External App Script -->
    <script>
        window.mrusShowConfig = {
            mruId: {{ $mru->id }},
            csrfToken: '{{ csrf_token() }}',
            activeTab: '{{ (!empty($search) || $consumers->total() === 0) ? "consumers" : "sessions" }}',
            cycleMonth: {{ now()->month }},
            cycleYear: {{ now()->year }},
            destroyUrl: '{{ route('mrus.destroy', $mru) }}',
            mrusIndexUrl: '{{ route('mrus.index') }}'
        };
    </script>
    <script src="{{ asset('js/mrus/mrus-show-app.js') }}?v={{ filemtime(public_path('js/mrus/mrus-show-app.js')) }}"></script>
</x-app-layout>
