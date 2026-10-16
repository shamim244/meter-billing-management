/**
 * Dedicated Consumer Reading Gap Forensic Dossier Alpine.js Application
 * Handles physical inspection visit logging, editing, deletion, recovery pacing tuning, and meter swaps.
 */
function consumerGapDetailWorkspace() {
    return {
        // Physical Inspection Modal
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

        // Edit Existing Inspection
        editingInspectionId: null,
        editForm: {
            physical_reading: '',
            inspected_at: '',
            notes: ''
        },

        // Meter Swap Modal
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

        // Strategy / Pacing Tuning
        pacingForm: {
            gap_recovery_mode: 'auto',
            custom_safe_ceiling: ''
        },
        savingPacing: false,
        pacingSuccess: false,

        init() {
            const config = window.consumerGapConfig || {};
            this.pacingForm.gap_recovery_mode = config.gapRecoveryMode || 'auto';
            this.pacingForm.custom_safe_ceiling = config.customSafeCeiling || '';
            this.resetInspectionForm();
            this.resetSwapForm();
        },

        resetInspectionForm() {
            const config = window.consumerGapConfig || {};
            this.inspectionForm = {
                ca_number: config.caNumber || '',
                consumer_name: config.consumerName || '',
                meter_no: config.meterNo || '',
                previous_reading: config.postedReading || 0,
                physical_reading: '',
                inspection_date: new Date().toISOString().slice(0, 10),
                notes: ''
            };
        },

        resetSwapForm() {
            const config = window.consumerGapConfig || {};
            this.swapForm = {
                ca_number: config.caNumber || '',
                consumer_name: config.consumerName || '',
                old_meter_no: config.meterNo || '',
                new_meter_no: '',
                initial_reading: '0',
                old_meter_final_reading: '',
                notes: ''
            };
        },

        openInspectionModal() {
            this.resetInspectionForm();
            this.showInspectionModal = true;
        },

        openMeterSwapModal() {
            this.resetSwapForm();
            this.showSwapModal = true;
        },

        startEditInspection(item) {
            this.editingInspectionId = item.id;
            this.editForm = {
                physical_reading: item.physical_reading,
                inspected_at: item.inspected_at ? item.inspected_at.slice(0, 10) : '',
                notes: item.notes || ''
            };
        },

        cancelEditInspection() {
            this.editingInspectionId = null;
        },

        async saveEditInspection(id) {
            if (!this.editForm.physical_reading) {
                alert('Physical meter reading is required.');
                return;
            }

            const config = window.consumerGapConfig || {};
            try {
                const res = await fetch(`${config.inspectionsUrl}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.editForm)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to update inspection visit.');
                }
            } catch (err) {
                console.error('Error saving inspection edit:', err);
                alert('A network error occurred while updating the visit.');
            }
        },

        async submitInspection() {
            if (!this.inspectionForm.physical_reading) {
                alert('Please enter a physical meter reading.');
                return;
            }

            this.savingInspection = true;
            const config = window.consumerGapConfig || {};
            try {
                const res = await fetch(config.recordInspectionUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ca_number: this.inspectionForm.ca_number,
                        physical_reading: this.inspectionForm.physical_reading,
                        inspected_at: this.inspectionForm.inspection_date,
                        notes: this.inspectionForm.notes
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showInspectionModal = false;
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to record meter visit.');
                }
            } catch (err) {
                console.error('Error recording inspection:', err);
                alert('A network error occurred while saving the inspection.');
            } finally {
                this.savingInspection = false;
            }
        },

        async submitMeterSwap() {
            if (!this.swapForm.new_meter_no || this.swapForm.initial_reading === '') {
                alert('Please provide the new meter number and initial reading.');
                return;
            }

            this.savingSwap = true;
            const config = window.consumerGapConfig || {};
            try {
                const res = await fetch(config.recordSwapUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.swapForm)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showSwapModal = false;
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to record meter replacement.');
                }
            } catch (err) {
                console.error('Error recording meter swap:', err);
                alert('A network error occurred while recording meter swap.');
            } finally {
                this.savingSwap = false;
            }
        },

        async deleteSingleInspection(id) {
            if (!confirm('Are you sure you want to delete this inspection visit?')) {
                return;
            }

            const config = window.consumerGapConfig || {};
            try {
                const res = await fetch(`${config.inspectionsUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete inspection.');
                }
            } catch (err) {
                console.error('Error deleting inspection:', err);
                alert('A network error occurred while deleting the inspection.');
            }
        },

        async clearAllInspections() {
            const config = window.consumerGapConfig || {};
            if (!confirm(`Are you sure you want to remove ALL recorded physical visits for CA ${config.caNumber}? This will reset the gap status to uninspected.`)) {
                return;
            }

            try {
                const res = await fetch(`${config.clearAllInspectionsUrl}/${config.caNumber}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to clear inspections.');
                }
            } catch (err) {
                console.error('Error clearing inspections:', err);
                alert('A network error occurred while clearing inspections.');
            }
        },

        async savePacingStrategy() {
            this.savingPacing = true;
            this.pacingSuccess = false;
            const config = window.consumerGapConfig || {};
            try {
                const res = await fetch(config.updateConsumerUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        gap_recovery_mode: this.pacingForm.gap_recovery_mode,
                        custom_safe_ceiling: this.pacingForm.custom_safe_ceiling
                    })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.pacingSuccess = true;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    alert(data.message || 'Failed to update pacing strategy.');
                }
            } catch (err) {
                console.error('Error updating pacing:', err);
                alert('A network error occurred while updating pacing strategy.');
            } finally {
                this.savingPacing = false;
            }
        }
    };
}
