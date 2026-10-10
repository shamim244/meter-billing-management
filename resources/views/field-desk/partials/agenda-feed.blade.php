<!-- Feed Content Area -->
<div>
    <!-- Loading Skeleton -->
    <div x-show="loading" class="py-12 flex flex-col items-center justify-center text-slate-400 space-y-3">
        <svg class="animate-spin h-8 w-8 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
        </svg>
        <span class="text-xs font-semibold">Loading FieldDesk Agenda...</span>
    </div>

    @include('field-desk.partials.agenda.empty-state')

    <!-- Agenda Cards List -->
    <div x-show="!loading && items.length > 0" class="space-y-3">
        <template x-for="item in items" :key="item.id">
            <div class="bg-white dark:bg-slate-900/95 rounded-2xl p-4 sm:p-5 border border-slate-200/80 dark:border-slate-800 shadow-sm hover:shadow-md transition duration-150">
                @include('field-desk.partials.agenda.card-header')
                @include('field-desk.partials.agenda.card-body')
                @include('field-desk.partials.agenda.card-actions')
            </div>
        </template>
    </div>

    @include('field-desk.partials.agenda.pagination')
</div>