<x-admin-layout>
    <div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        showMigrateModal: false,
        selectedUserId: null,
        selectedUserName: '',
        openMigrate(userId, userName) {
            this.selectedUserId = userId;
            this.selectedUserName = userName;
            this.showMigrateModal = true;
        }
    }">
        @include('admin.plans.partials.agents.header')
        @include('admin.plans.partials.agents.table')
        @include('admin.plans.partials.agents.modal-migrate')
    </div>
</x-admin-layout>
