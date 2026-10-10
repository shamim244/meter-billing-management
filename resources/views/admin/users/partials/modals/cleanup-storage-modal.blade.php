<!-- MODAL 4: Granular Data & Storage Management Console -->
<div x-show="showCleanupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="showCleanupModal = false" class="bg-slate-900 border border-slate-700 rounded-3xl p-6 max-w-2xl w-full shadow-2xl space-y-5 animate-in fade-in zoom-in-95 duration-150">
        @include('admin.users.partials.modals.cleanup.header')
        @include('admin.users.partials.modals.cleanup.tab-pdfs')
        @include('admin.users.partials.modals.cleanup.tab-mrus')
        @include('admin.users.partials.modals.cleanup.tab-bills')
        @include('admin.users.partials.modals.cleanup.tab-purge')
    </div>
</div>
