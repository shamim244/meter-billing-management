<!-- 1. CREATE / NEW ACTION MODAL -->
<div x-show="modals.create"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="modals.create = false"
         class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        
        @include('field-desk.partials.modals.create.header')

        <form @submit.prevent="submitCreate()" class="space-y-3.5 text-xs">
            @include('field-desk.partials.modals.create.form-fields')
            @include('field-desk.partials.modals.create.gps-box')
            @include('field-desk.partials.modals.create.footer')
        </form>

    </div>
</div>