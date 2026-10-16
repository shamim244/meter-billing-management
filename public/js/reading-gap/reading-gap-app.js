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
        }
    };
}
