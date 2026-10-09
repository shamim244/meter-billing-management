/**
 * Admin Plan Durations Manager Alpine.js Application
 * Handles add/edit modal states and dynamic price recalculation
 */
function planDurationsManager(durationsData, basePriceVal) {
    return {
        basePrice: parseFloat(basePriceVal) || 0,
        showAddModal: false,
        showEditModal: false,
        form: {
            unit: 'month',
            value: 1,
            name: '',
            discount: 0,
            price: 0,
            extraMru: '',
            extraConsumer: '',
            isActive: true,
        },
        editForm: {
            id: null,
            title: '',
            unit: 'month',
            value: 1,
            name: '',
            discount: 0,
            price: 0,
            extraMru: '',
            extraConsumer: '',
            isActive: true,
        },

        openAddModal() {
            this.form = {
                unit: 'month',
                value: 1,
                name: '',
                discount: 0,
                price: this.basePrice,
                extraMru: '',
                extraConsumer: '',
                isActive: true,
            };
            this.recalculatePrice();
            this.showAddModal = true;
        },

        openEditModal(dur) {
            this.editForm = {
                id: dur.id,
                title: dur.name || (dur.duration_value + ' ' + (dur.duration_unit === 'day' ? 'Days' : 'Months')),
                unit: dur.duration_unit || 'month',
                value: dur.duration_value || dur.duration_months || 1,
                name: dur.name || '',
                discount: parseFloat(dur.discount_percent) || 0,
                price: parseFloat(dur.final_price) || 0,
                extraMru: dur.extra_mru_rate || '',
                extraConsumer: dur.extra_consumer_rate || '',
                isActive: Boolean(dur.is_active),
            };
            this.showEditModal = true;
        },

        recalculatePrice() {
            const discount = Math.min(100, Math.max(0, this.form.discount || 0));
            const val = Math.max(1, this.form.value || 1);
            if (this.form.unit === 'day') {
                this.form.price = parseFloat(((this.basePrice / 30) * val * (1 - (discount / 100))).toFixed(2));
            } else {
                this.form.price = parseFloat((this.basePrice * val * (1 - (discount / 100))).toFixed(2));
            }
        },

        recalculateEditPrice() {
            const discount = Math.min(100, Math.max(0, this.editForm.discount || 0));
            const val = Math.max(1, this.editForm.value || 1);
            if (this.editForm.unit === 'day') {
                this.editForm.price = parseFloat(((this.basePrice / 30) * val * (1 - (discount / 100))).toFixed(2));
            } else {
                this.editForm.price = parseFloat((this.basePrice * val * (1 - (discount / 100))).toFixed(2));
            }
        }
    };
}

window.planDurationsManager = planDurationsManager;
