document.addEventListener('alpine:init', () => {
    Alpine.data('adminWalletShowApp', () => ({
        showModal: false,
        openFreeze: false,
        adjustmentType: 'add',
        amount: '',
        reason: '',

        openModal(type) {
            this.adjustmentType = type;
            this.amount = '';
            this.reason = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        openFreezeModal() {
            this.openFreeze = true;
        },

        closeFreezeModal() {
            this.openFreeze = false;
        }
    }));
});
