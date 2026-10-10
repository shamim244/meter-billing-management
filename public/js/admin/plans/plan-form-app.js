/**
 * Admin Plan Form (Create/Edit) App
 * Decoupled Alpine.js Component
 */

function adminPlanFormApp(config = {}) {
    return {
        basePrice: typeof config.basePrice === 'number' ? config.basePrice : 499,
        durations: Array.isArray(config.durations) && config.durations.length > 0 ? config.durations : [
            { unit: 'month', value: 1, discount: 0, price: 499, name: '', extraMru: '', extraConsumer: '', is_active: true }
        ],

        addDuration(unit = 'month', value = 1) {
            const val = parseInt(value, 10) || 1;
            const price = unit === 'day' 
                ? parseFloat(((this.basePrice / 30) * val).toFixed(2)) 
                : parseFloat((this.basePrice * val).toFixed(2));
            this.durations.push({
                id: null,
                unit: unit,
                value: val,
                discount: 0,
                price: price,
                name: '',
                extraMru: '',
                extraConsumer: '',
                is_active: true
            });
        },

        removeDuration(index) {
            if (this.durations.length > 1) {
                this.durations.splice(index, 1);
            }
        },

        recalculateDurations() {
            this.durations.forEach(d => {
                const discount = Math.min(100, Math.max(0, d.discount || 0));
                const val = Math.max(1, d.value || 1);
                if (d.unit === 'day') {
                    d.price = parseFloat(((this.basePrice / 30) * val * (1 - (discount / 100))).toFixed(2));
                } else {
                    d.price = parseFloat((this.basePrice * val * (1 - (discount / 100))).toFixed(2));
                }
            });
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminPlanFormApp = adminPlanFormApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminPlanFormApp', (config) => adminPlanFormApp(config));
        }
    });
}
