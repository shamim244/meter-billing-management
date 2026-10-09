/**
 * Admin Subscriptions & Lifecycle State Machine App
 * Decoupled Alpine.js Component
 */

function adminSubscriptionsApp() {
    return {
        showOverrideModal: false,
        subId: null,
        agentName: '',
        currentStatus: '',
        targetStatus: 'active',

        openOverride(id, name, status) {
            this.subId = id;
            this.agentName = name;
            this.currentStatus = status;
            this.targetStatus = status === 'suspended' ? 'active' : 'suspended';
            this.showOverrideModal = true;
        },

        closeOverride() {
            this.showOverrideModal = false;
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminSubscriptionsApp = adminSubscriptionsApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminSubscriptionsApp', () => adminSubscriptionsApp());
        }
    });
}
