/**
 * Admin Coupons Campaign Manager
 * Decoupled Alpine.js Component
 */

function couponsIndexApp() {
    return {
        selectedCoupons: [],
        selectAll: false,

        toggleAll() {
            if (this.selectAll) {
                this.selectedCoupons = Array.from(document.querySelectorAll('.coupon-checkbox')).map(cb => parseInt(cb.value));
            } else {
                this.selectedCoupons = [];
            }
        }
    };
}

if (typeof window !== 'undefined') {
    window.couponsIndexApp = couponsIndexApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('couponsIndexApp', () => couponsIndexApp());
        }
    });
}
