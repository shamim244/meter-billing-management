<!-- MODAL 8: FieldDesk Quick Bridge Modal (Tier 2 Fast Pop-up) -->
<div x-show="showFieldDeskModal" x-cloak
     @keydown.escape.window="showFieldDeskModal = false"
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div @click.outside="showFieldDeskModal = false" class="bg-white dark:bg-slate-900 rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-lg my-auto max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
        
        @include('dashboard.partials.modals.field-desk.header')
        @include('dashboard.partials.modals.field-desk.contact-bar')

        <!-- Body -->
        <div class="overflow-y-auto p-4 sm:p-6 space-y-4">
            <!-- Loading State -->
            <div x-show="fieldDeskLoading" class="py-8 flex flex-col items-center justify-center text-slate-400 space-y-2">
                <svg class="w-6 h-6 animate-spin text-emerald-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-xs">Fetching FieldDesk records...</span>
            </div>

            <!-- Content when loaded -->
            <div x-show="!fieldDeskLoading" class="space-y-4">
                @include('dashboard.partials.modals.field-desk.active-action')
                @include('dashboard.partials.modals.field-desk.create-form')
            </div>
        </div>

        @include('dashboard.partials.modals.field-desk.footer')

    </div>
</div>
