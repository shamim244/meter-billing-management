<x-app-layout>
    <div x-data="mrusIndexApp()" class="py-8 min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Hero Header & Stats Banner -->
            @include('mrus.partials.hero-header')

            <!-- Flash Session Alerts -->
            @include('mrus.partials.flash-alerts')

            <!-- Search & Status Filter Controls -->
            @include('mrus.partials.filter-bar')

            <!-- MRUs Grid & Empty State -->
            @include('mrus.partials.cards-grid')

            <!-- Interactive Modals -->
            @include('mrus.partials.modals.create-modal')
            @include('mrus.partials.modals.existing-mru-modal')
            @include('mrus.partials.modals.cycle-modal')
            @include('mrus.partials.modals.delete-modal')

        </div>
    </div>

    <!-- Client-Side Configuration Bridge & External App Script -->
    <script>
        window.mrusIndexConfig = {
            mrus: @js($mrus),
            selectedMruId: '{{ $mrus->first()?->id ?? "" }}',
            cycleMonth: {{ now()->month }},
            cycleYear: {{ now()->year }},
            csrfToken: '{{ csrf_token() }}',
            walletIndexUrl: '{{ route('wallet.index') }}',
            subscriptionUrl: '{{ route('user-panel.subscription') }}'
        };
    </script>
    <script src="{{ asset('js/mrus/mrus-index-app.js') }}?v={{ filemtime(public_path('js/mrus/mrus-index-app.js')) }}"></script>
</x-app-layout>
