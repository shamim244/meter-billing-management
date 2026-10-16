/**
 * Reading Gap & Field Inspection Workspace Alpine Controller
 */
function readingGapWorkspace() {
    return {
        loading: false,
        kpis: {},
        consumers: [],
        pagination: { current_page: 1, last_page: 1, total: 0 },
        activeTab: 'all',
        selectedMru: '',
        searchQuery: '',
        
        // Modals
        showSwapModal: false,
        savingSwap: false,
        swapForm: {
            ca_number: '',
            consumer_name: '',
            old_meter_no: '',
            new_meter_no: '',
            initial_reading: '0',
            old_meter_final_reading: '',
            notes: ''
        },

        showInspectionModal: false,
        savingInspection: false,
        inspectionForm: {
            ca_number: '',
            consumer_name: '',
            meter_no: '',
            previous_reading: 0,
            physical_reading: '',
            inspection_date: '',
            notes: ''
        },

        // Inspection History & Management (Module 13)
        showHistoryModal: false,
        loadingHistory: false,
        selectedConsumer: null,
        inspectionsList: [],
        selectedInspectionIds: [],
        editingInspectionId: null,
        editForm: {
            physical_reading: '',
            inspected_at: '',
            notes: ''
        },

        init() {
            if (window.readingGapConfig && window.readingGapConfig.initialCa) {
                this.searchQuery = window.readingGapConfig.initialCa;
            }
            this.fetchData(1);
        },

        async fetchData(page = 1) {
            this.loading = true;
            try {
                const config = window.readingGapConfig || {};
                const url = new URL(config.dataUrl || '/reading-gap/data', window.location.origin);
                url.searchParams.set('tab', this.activeTab);
                if (this.selectedMru) url.searchParams.set('mru_id', this.selectedMru);
                if (this.searchQuery) url.searchParams.set('search', this.searchQuery);
                url.searchParams.set('page', page);

                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.kpis = data.kpis || {};
                    this.consumers = data.consumers || [];
                    this.pagination = data.pagination || { current_page: 1, last_page: 1, total: 0 };
                }
            } catch (err) {
                console.error('Error fetching gap workspace data:', err);
            } finally {
                this.loading = false;
            }
        },

        setTab(tab) {
            this.activeTab = tab;
            this.fetchData(1);
        },

        openInspectionModal(consumer) {
            this.inspectionForm = {
                ca_number: consumer.ca_number,
                consumer_name: consumer.consumer_name,
                meter_no: consumer.meter_no,
                previous_reading: consumer.posted_reading || 0,
                physical_reading: consumer.last_physical_reading || '',
                inspection_date: new Date().toISOString().slice(0, 10),
                notes: ''
            };
            this.showInspectionModal = true;
        },

        async submitInspection() {
            if (!this.inspectionForm.physical_reading) return;
            this.savingInspection = true;
            try {
                const config = window.readingGapConfig || {};
                const res = await fetch(config.recordInspectionUrl || '/api/reading-gap/record-inspection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        ca_number: this.inspectionForm.ca_number,
                        physical_reading: this.inspectionForm.physical_reading,
                        inspection_date: this.inspectionForm.inspection_date,
                        notes: this.inspectionForm.notes
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showInspectionModal = false;
                    if (this.selectedConsumer && this.selectedConsumer.ca_number === this.inspectionForm.ca_number) {
                        await this.loadInspectionHistory(this.inspectionForm.ca_number);
                        this.showHistoryModal = true;
                    }
                    await this.fetchData(this.pagination.current_page);
                } else {
                    alert(data.message || 'Inspection recording failed.');
                }
            } catch (err) {
                console.error('Error recording inspection:', err);
            } finally {
                this.savingInspection = false;
            }
        },

        openMeterSwapModal(consumer) {
            this.swapForm = {
                ca_number: consumer.ca_number,
                consumer_name: consumer.consumer_name,
                old_meter_no: consumer.meter_no,
                new_meter_no: '',
                initial_reading: '0',
                old_meter_final_reading: consumer.last_physical_reading || '',
                notes: ''
            };
            this.showSwapModal = true;
        },

        async submitMeterSwap() {
            if (!this.swapForm.new_meter_no || this.swapForm.initial_reading === '') return;
            this.savingSwap = true;
            try {
                const config = window.readingGapConfig || {};
                const res = await fetch(config.recordSwapUrl || '/api/reading-gap/record-swap', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        ca_number: this.swapForm.ca_number,
                        new_meter_no: this.swapForm.new_meter_no,
                        initial_reading: this.swapForm.initial_reading,
                        old_meter_final_reading: this.swapForm.old_meter_final_reading,
                        notes: this.swapForm.notes
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showSwapModal = false;
                    await this.fetchData(this.pagination.current_page);
                } else {
                    alert(data.message || 'Meter swap recording failed.');
                }
            } catch (err) {
                console.error('Error recording meter swap:', err);
            } finally {
                this.savingSwap = false;
            }
        },

        // Inspection History & Management Methods
        openInspectionHistoryModal(consumer) {
            this.selectedConsumer = consumer;
            this.selectedInspectionIds = [];
            this.editingInspectionId = null;
            this.showHistoryModal = true;
            this.loadInspectionHistory(consumer.ca_number);
        },

        closeHistoryModal() {
            this.showHistoryModal = false;
            this.selectedInspectionIds = [];
            this.editingInspectionId = null;
        },

        async loadInspectionHistory(ca) {
            this.loadingHistory = true;
            try {
                const config = window.readingGapConfig || {};
                const baseUrl = config.inspectionsUrl || '/api/reading-gap/inspections';
                const res = await fetch(`${baseUrl}/${encodeURIComponent(ca)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.inspectionsList = data.inspections || [];
                } else {
                    this.inspectionsList = [];
                }
            } catch (err) {
                console.error('Error loading inspection history:', err);
                this.inspectionsList = [];
            } finally {
                this.loadingHistory = false;
            }
        },

        isAllInspectionsSelected() {
            return this.inspectionsList.length > 0 && this.selectedInspectionIds.length === this.inspectionsList.length;
        },

        toggleSelectAllInspections(e) {
            if (e.target.checked) {
                this.selectedInspectionIds = this.inspectionsList.map(i => i.id);
            } else {
                this.selectedInspectionIds = [];
            }
        },

        startEditInspection(item) {
            this.editingInspectionId = item.id;
            this.editForm = {
                physical_reading: item.physical_reading,
                inspected_at: item.inspected_at,
                notes: item.notes || ''
            };
        },

        cancelEditInspection() {
            this.editingInspectionId = null;
        },

        async saveEditInspection(item) {
            if (!this.editForm.physical_reading) return;
            try {
                const config = window.readingGapConfig || {};
                const baseUrl = config.inspectionsUrl || '/api/reading-gap/inspections';
                const res = await fetch(`${baseUrl}/${item.id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify(this.editForm)
                });
                const data = await res.json();
                if (data.success) {
                    this.editingInspectionId = null;
                    await this.loadInspectionHistory(this.selectedConsumer.ca_number);
                    await this.fetchData(this.pagination.current_page);
                } else {
                    alert(data.message || 'Failed to update inspection.');
                }
            } catch (err) {
                console.error('Error updating inspection:', err);
            }
        },

        async deleteSingleInspection(item) {
            if (!confirm('Are you sure you want to delete this physical visit record?')) return;
            try {
                const config = window.readingGapConfig || {};
                const baseUrl = config.inspectionsUrl || '/api/reading-gap/inspections';
                const res = await fetch(`${baseUrl}/${item.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });
                const data = await res.json();
                if (data.success) {
                    await this.loadInspectionHistory(this.selectedConsumer.ca_number);
                    await this.fetchData(this.pagination.current_page);
                } else {
                    alert(data.message || 'Failed to delete inspection.');
                }
            } catch (err) {
                console.error('Error deleting inspection:', err);
            }
        },

        async bulkDeleteInspections() {
            if (this.selectedInspectionIds.length === 0) return;
            if (!confirm(`Delete ${this.selectedInspectionIds.length} selected physical inspection records?`)) return;
            try {
                const config = window.readingGapConfig || {};
                const res = await fetch(config.bulkDeleteInspectionsUrl || '/api/reading-gap/inspections/bulk-delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    },
                    body: JSON.stringify({
                        ids: this.selectedInspectionIds,
                        ca_number: this.selectedConsumer.ca_number
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedInspectionIds = [];
                    await this.loadInspectionHistory(this.selectedConsumer.ca_number);
                    await this.fetchData(this.pagination.current_page);
                } else {
                    alert(data.message || 'Bulk deletion failed.');
                }
            } catch (err) {
                console.error('Error in bulk delete:', err);
            }
        },

        async clearAllInspections() {
            if (!confirm('Are you sure you want to clear ALL physical visit records for this consumer? The consumer will return to uninspected status.')) return;
            try {
                const config = window.readingGapConfig || {};
                const baseUrl = config.clearAllInspectionsUrl || '/api/reading-gap/inspections/consumer';
                const res = await fetch(`${baseUrl}/${encodeURIComponent(this.selectedConsumer.ca_number)}/clear`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken
                    }
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedInspectionIds = [];
                    await this.loadInspectionHistory(this.selectedConsumer.ca_number);
                    await this.fetchData(this.pagination.current_page);
                } else {
                    alert(data.message || 'Failed to clear inspections.');
                }
            } catch (err) {
                console.error('Error clearing inspections:', err);
            }
        },

        switchToRecordVisit() {
            const cons = this.selectedConsumer;
            this.closeHistoryModal();
            this.openInspectionModal(cons);
        }
    };
}

window.readingGapWorkspace = readingGapWorkspace;
document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('readingGapWorkspace', readingGapWorkspace);
    }
});
