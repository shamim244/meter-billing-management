document.addEventListener('alpine:init', () => {
    Alpine.data('emailProvidersApp', () => ({
        addModal: false,
        editModal: false,
        testModal: false,
        selectedProviderId: null,
        selectedProviderLabel: '',
        driverType: 'smtp',
        editData: {},

        openAddModal() {
            this.driverType = 'smtp';
            this.addModal = true;
        },

        closeAddModal() {
            this.addModal = false;
        },

        openEditModal(data) {
            this.editData = Object.assign({}, data);
            this.editModal = true;
        },

        closeEditModal() {
            this.editModal = false;
        },

        openTestModal(id, label) {
            this.selectedProviderId = id;
            this.selectedProviderLabel = label;
            this.testModal = true;
        },

        closeTestModal() {
            this.testModal = false;
        }
    }));
});
