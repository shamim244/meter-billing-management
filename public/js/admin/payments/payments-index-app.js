function adminPaymentsIndexApp() {
    return {
        approveModal: false,
        rejectModal: false,
        refundModal: false,
        screenshotModal: false,
        currentPayment: null,
        rejectionReason: '',
        notes: '',
        refundReason: '',
        currentScreenshot: '',

        openApprove(p) {
            this.currentPayment = p;
            this.notes = '';
            this.approveModal = true;
        },

        closeApprove() {
            this.approveModal = false;
        },

        openReject(p) {
            this.currentPayment = p;
            this.rejectionReason = '';
            this.notes = '';
            this.rejectModal = true;
        },

        closeReject() {
            this.rejectModal = false;
        },

        openRefund(p) {
            this.currentPayment = p;
            this.refundReason = '';
            this.refundModal = true;
        },

        closeRefund() {
            this.refundModal = false;
        },

        openScreenshot(url) {
            this.currentScreenshot = url;
            this.screenshotModal = true;
        },

        closeScreenshot() {
            this.screenshotModal = false;
            this.currentScreenshot = '';
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminPaymentsIndexApp = adminPaymentsIndexApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminPaymentsIndexApp', adminPaymentsIndexApp);
        }
    });
}
