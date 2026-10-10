<!-- 4. EDIT ACTION MODAL -->
<div x-show="modals.edit"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div @click.away="modals.edit = false"
         class="bg-white dark:bg-slate-900 rounded-3xl max-w-lg w-full p-6 border border-slate-200 dark:border-slate-800 shadow-2xl space-y-4">
        
        @include('field-desk.partials.modals.edit.header')

        <form @submit.prevent="submitEdit()" class="space-y-3.5 text-xs">
            @include('field-desk.partials.modals.edit.form-fields')
            @include('field-desk.partials.modals.edit.gps-box')
            @include('field-desk.partials.modals.edit.footer')
        </form>

    </div>
</div>