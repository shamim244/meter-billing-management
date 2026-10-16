/**
 * Dedicated Consumer Workspace Alpine.js Application
 * Handles tab navigation, profile editing, physical inspections, meter swaps, and quick sync.
 */
function consumerDetailWorkspace() {
    return {
        activeTab: 'overview',
        showEditProfileModal: false,
        savingProfile: false,
        profileForm: {},

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

        triggeringPull: false,
        deletingInspectionId: null,

        init() {
            const config = window.consumerConfig || {};
            this.activeTab = config.defaultTab || 'overview';

            // Check URL hash or search param
            const urlParams = new URLSearchParams(window.location.search);
            const queryTab = urlParams.get('tab');
            if (queryTab && ['overview', 'gap', 'fielddesk', 'bills', 'matrix'].includes(queryTab)) {
                this.activeTab = queryTab;
            } else if (window.location.hash) {
                const hashTab = window.location.hash.replace('#', '');
                if (['overview', 'gap', 'fielddesk', 'bills', 'matrix'].includes(hashTab)) {
                    this.activeTab = hashTab;
                }
            }

            // Initialize forms
            this.profileForm = Object.assign({}, config.profileData || {});
            this.resetInspectionForm();
            this.resetSwapForm();
        },

        setActiveTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        },

        resetInspectionForm() {
            const config = window.consumerConfig || {};
            this.inspectionForm = {
                ca_number: config.caNumber || '',
                consumer_name: config.consumerName || '',
                meter_no: config.meterNo || '',
                previous_reading: 0,
                physical_reading: '',
                inspection_date: new Date().toISOString().slice(0, 10),
                notes: ''
            };
        },

        resetSwapForm() {
            const config = window.consumerConfig || {};
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

        async saveProfile() {
            this.savingProfile = true;
            const config = window.consumerConfig || {};
            try {
                const res = await fetch(config.updateUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.profileForm)
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    this.showEditProfileModal = false;
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to update consumer profile.');
                }
            } catch (err) {
                console.error('Error updating consumer profile:', err);
                alert('A network or server error occurred while updating the profile.');
            } finally {
                this.savingProfile = false;
            }
        },

        async submitInspection() {
            if (!this.inspectionForm.physical_reading) {
                alert('Please enter a physical meter reading.');
                return;
            }

            this.savingInspection = true;
            const config = window.consumerConfig || {};
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
                alert('A network or server error occurred while saving the inspection.');
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
            const config = window.consumerConfig || {};
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
                alert('A network or server error occurred while recording meter swap.');
            } finally {
                this.savingSwap = false;
            }
        },

        async deleteSingleInspection(id) {
            if (!confirm('Are you sure you want to delete this inspection visit?')) {
                return;
            }

            this.deletingInspectionId = id;
            const config = window.consumerConfig || {};
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
            } finally {
                this.deletingInspectionId = null;
            }
        },

        async clearAllInspections() {
            const config = window.consumerConfig || {};
            if (!confirm(`Are you sure you want to remove ALL recorded physical visits for CA ${config.caNumber}? This cannot be undone.`)) {
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

        async triggerQuickPull() {
            this.triggeringPull = true;
            const config = window.consumerConfig || {};
            try {
                const res = await fetch(config.quickPullUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ ca: config.caNumber })
                });

                const data = await res.json();
                if (res.ok && data.success) {
                    alert(data.message || 'Bill pulled successfully.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Quick pull could not complete.');
                }
            } catch (err) {
                console.error('Error triggering quick pull:', err);
                alert('A network error occurred while pulling the bill.');
            } finally {
                this.triggeringPull = false;
            }
        }
    };
}
