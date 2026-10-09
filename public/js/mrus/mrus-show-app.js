/**
 * MRU Detail Hub App Logic
 * Decoupled client-side Alpine component for MRU Workspace Detail & Sessions
 */
function mruHubApp() {
    const config = window.mrusShowConfig || {};

    return {
        activeTab: config.activeTab || 'sessions',
        showAddConsumerModal: false,
        showEditConsumerModal: false,
        showImportModal: false,
        showStartBillingModal: false,
        showEditMruModal: false,
        showDeleteMruModal: false,
        isDeletingMru: false,
        editingConsumer: null,
        bulkImportText: '',
        cycleMonth: config.cycleMonth || (new Date().getMonth() + 1),
        cycleYear: config.cycleYear || new Date().getFullYear(),
        executingAction: 'download_all',
        billingInProgress: false,
        billingResult: null,

        cycleOverageRequired: false,
        cycleOverageAmount: 0,
        cycleOverageMessage: '',

        get detectedLinesCount() {
            if (!this.bulkImportText.trim()) return 0;
            return this.bulkImportText.trim().split(/\r?\n/).filter(line => line.trim().length > 0).length;
        },

        openEditConsumerModal(consumer) {
            this.editingConsumer = {
                id: consumer.id,
                ca_number: consumer.ca_number,
                consumer_name: consumer.consumer_name || '',
                meter_no: consumer.meter_no || '',
                tariff_category: consumer.tariff_category || 'DS-II',
                billing_basis: consumer.billing_basis || 'OK',
                baseline_amount: consumer.baseline_amount !== null && consumer.baseline_amount !== undefined ? consumer.baseline_amount : '0.00',
                baseline_previous_reading: consumer.baseline_previous_reading !== null && consumer.baseline_previous_reading !== undefined ? consumer.baseline_previous_reading : (consumer.last_working_reading || ''),
                mobile: consumer.mobile || '',
                address: consumer.address || '',
                status: consumer.status || 'active'
            };
            this.showEditConsumerModal = true;
        },

        openDownloadForSession(month, year) {
            this.cycleMonth = month;
            this.cycleYear = year;
            this.cycleOverageRequired = false;
            this.cycleOverageAmount = 0;
            this.cycleOverageMessage = '';
            this.showStartBillingModal = true;
        },

        syncMissingSession(month, year) {
            this.billingInProgress = true;
            this.billingResult = null;
            this.executingAction = 'sync_missing';

            fetch(`/mrus/${config.mruId}/sync-missing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || ''
                },
                body: JSON.stringify({
                    billing_month: month,
                    billing_year: year
                })
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Sync failed');
                }
                return data;
            })
            .then(json => {
                this.billingResult = json;
                if (json.success) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 800);
                }
            })
            .catch(err => {
                this.billingResult = {
                    success: false,
                    message: err.message || 'An error occurred while syncing missing bills.'
                };
            })
            .finally(() => {
                this.billingInProgress = false;
            });
        },

        triggerMruBilling(actionType = 'download_all', payOverage = false) {
            this.executingAction = actionType;
            this.billingInProgress = true;
            this.billingResult = null;

            fetch(`/mrus/${config.mruId}/start-billing`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || ''
                },
                body: JSON.stringify({
                    billing_month: this.cycleMonth,
                    billing_year: this.cycleYear,
                    action_type: actionType,
                    pay_overage: payOverage ? 1 : 0
                })
            })
            .then(async res => {
                const data = await res.json();
                if (res.status === 402 && data.requires_overage) {
                    this.cycleOverageRequired = true;
                    this.cycleOverageAmount = data.amount_due || 0;
                    this.cycleOverageMessage = data.message || 'Consumer quota exceeded. Wallet deduction required.';
                    throw new Error(data.message);
                }
                if (!res.ok) {
                    throw new Error(data.message || 'Cycle creation failed');
                }
                return data;
            })
            .then(json => {
                this.billingResult = json;
                if (json.success) {
                    setTimeout(() => {
                        if (json.redirect_url) {
                            window.location.href = json.redirect_url;
                        } else {
                            window.location.reload();
                        }
                    }, 800);
                }
            })
            .catch(err => {
                this.billingResult = {
                    success: false,
                    message: err.message || 'An error occurred while executing cycle request.'
                };
            })
            .finally(() => {
                this.billingInProgress = false;
            });
        },

        confirmDeleteMru() {
            this.isDeletingMru = true;
            fetch(config.destroyUrl || `/mrus/${config.mruId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken || '',
                    'Accept': 'application/json'
                }
            })
            .then(async res => {
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Error deleting MRU');
                return data;
            })
            .then(data => {
                this.isDeletingMru = false;
                this.showDeleteMruModal = false;
                window.location.href = data.redirect_url || config.mrusIndexUrl || '/mrus';
            })
            .catch(err => {
                this.isDeletingMru = false;
                alert('Failed to delete MRU: ' + err.message);
            });
        }
    };
}
