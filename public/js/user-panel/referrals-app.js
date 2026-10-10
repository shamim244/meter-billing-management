/**
 * User Panel Referrals App
 * Decoupled Alpine.js Component
 */

function userReferralsApp() {
    return {
        showRegenerateModal: false,
        copiedCode: false,
        copiedLink: false,

        copyToClipboard(text, type) {
            if (navigator && navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    if (type === 'code') {
                        this.copiedCode = true;
                        setTimeout(() => this.copiedCode = false, 2500);
                    } else {
                        this.copiedLink = true;
                        setTimeout(() => this.copiedLink = false, 2500);
                    }
                });
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.userReferralsApp = userReferralsApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('userReferralsApp', () => userReferralsApp());
        }
    });
}
