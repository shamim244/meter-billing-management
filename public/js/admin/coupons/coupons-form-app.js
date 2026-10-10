/**
 * Coupon Campaign Form Management (Create / Edit)
 * Pure decoupled JavaScript - zero Blade directives.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('couponsFormApp', (config = {}) => ({
        type: config.type || 'subscription_discount',
        discountKind: config.discountKind || 'percentage',
        slabs: Array.isArray(config.slabs) && config.slabs.length > 0 
            ? config.slabs 
            : [
                { min_amount: 100, max_amount: 1000, bonus_percent: 5 },
                { min_amount: 1001, max_amount: 5000, bonus_percent: 10 },
                { min_amount: 5001, max_amount: '', bonus_percent: 15 }
            ],

        addSlab() {
            const last = this.slabs[this.slabs.length - 1];
            const nextMin = last && last.max_amount ? parseInt(last.max_amount, 10) + 1 : 10000;
            this.slabs.push({ min_amount: nextMin, max_amount: '', bonus_percent: 20 });
        },

        removeSlab(index) {
            if (this.slabs.length > 1) {
                this.slabs.splice(index, 1);
            }
        }
    }));
});

window.couponsFormApp = function(config = {}) {
    return {
        type: config.type || 'subscription_discount',
        discountKind: config.discountKind || 'percentage',
        slabs: Array.isArray(config.slabs) && config.slabs.length > 0 
            ? config.slabs 
            : [
                { min_amount: 100, max_amount: 1000, bonus_percent: 5 },
                { min_amount: 1001, max_amount: 5000, bonus_percent: 10 },
                { min_amount: 5001, max_amount: '', bonus_percent: 15 }
            ],

        addSlab() {
            const last = this.slabs[this.slabs.length - 1];
            const nextMin = last && last.max_amount ? parseInt(last.max_amount, 10) + 1 : 10000;
            this.slabs.push({ min_amount: nextMin, max_amount: '', bonus_percent: 20 });
        },

        removeSlab(index) {
            if (this.slabs.length > 1) {
                this.slabs.splice(index, 1);
            }
        }
    };
};
