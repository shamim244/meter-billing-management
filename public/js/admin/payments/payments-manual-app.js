/**
 * Admin Manual Payments Verification Queue
 * Decoupled Alpine.js Component
 */

function paymentsManualApp() {
    return {
        approveModal: false,
        rejectModal: false,
        previewModal: false,
        previewImageUrl: null,
        currentPayment: null,
        rejectionReason: '',
        notes: '',

        openApprove(payment) {
            this.currentPayment = payment;
            this.notes = '';
            this.approveModal = true;
        },

        closeApprove() {
            this.approveModal = false;
        },

        openReject(payment) {
            this.currentPayment = payment;
            this.rejectionReason = '';
            this.notes = '';
            this.rejectModal = true;
        },

        closeReject() {
            this.rejectModal = false;
        },

        openPreview(imageUrl) {
            this.previewImageUrl = imageUrl;
            this.previewModal = true;
        },

        closePreview() {
            this.previewModal = false;
        }
    };
}

if (typeof window !== 'undefined') {
    window.paymentsManualApp = paymentsManualApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('paymentsManualApp', () => paymentsManualApp());
        }
    });
}
