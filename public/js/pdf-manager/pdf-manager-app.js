function pdfManager() {
    const config = window.pdfManagerConfig || {};
    const routes = config.routes || {};
    const csrfToken = config.csrfToken || '';

    return {
        viewMode: config.viewMode || 'table',
        selectedIds: [],
        pageIds: config.pageIds || [],
        actionRunning: false,
        loadingBillId: null,
        showHealthModal: false,
        healthScanning: false,
        healthData: null,
        showUploadModal: false,
        uploadRunning: false,
        showDeleteModal: false,
        deleteRunning: false,
        deleteModalData: {
            month: null,
            year: null,
            label: '',
            totalSize: '',
            pdfCount: 0,
            totalBills: 0,
            olderThanCurrent: false,
            targetScope: 'all',
        },
        uploadMruId: config.mruId || '',
        uploadMonth: config.month || (new Date().getMonth() + 1),
        uploadYear: config.year || new Date().getFullYear(),
        uploadFiles: [],

        init() {
            window.addEventListener('open-health-modal', () => {
                this.showHealthModal = true;
                this.runHealthScan();
            });
            window.addEventListener('open-upload-modal', () => {
                this.showUploadModal = true;
            });
        },

        isAllSelected() {
            return this.pageIds.length > 0 && this.pageIds.every(id => this.selectedIds.includes(id));
        },

        selectAllOnPage() {
            this.pageIds.forEach(id => {
                if (!this.selectedIds.includes(id)) {
                    this.selectedIds.push(id);
                }
            });
        },

        toggleSelectAll(e) {
            if (e.target.checked) {
                this.selectAllOnPage();
            } else {
                this.selectedIds = this.selectedIds.filter(id => !this.pageIds.includes(id));
            }
        },

        triggerBatchDownload() {
            if (this.selectedIds.length === 0) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = routes.batchDownload;

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = csrfToken;
            form.appendChild(csrf);

            this.selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'bill_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        triggerBatchReparse() {
            if (this.selectedIds.length === 0) return;
            this.actionRunning = true;

            fetch(routes.batchReparse, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bill_ids: this.selectedIds })
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                alert(data.message || 'Batch re-parse completed!');
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                alert('Failed to re-parse bills: ' + err);
            });
        },

        triggerBatchRedownload() {
            if (this.selectedIds.length === 0) return;
            if (!confirm('Re-download ' + this.selectedIds.length + ' official PDF bills from NBPDCL servers?')) return;

            this.actionRunning = true;
            fetch(routes.batchRedownload, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bill_ids: this.selectedIds })
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                alert(data.message || 'Re-download completed!');
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                alert('Failed to re-download bills: ' + err);
            });
        },

        triggerBatchDelete() {
            if (this.selectedIds.length === 0) return;
            if (!confirm('Permanently delete ' + this.selectedIds.length + ' physical PDF files and reset records to Pending?')) return;

            this.actionRunning = true;
            fetch(routes.batchDelete, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bill_ids: this.selectedIds })
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                alert(data.message || 'Deleted successfully!');
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                alert('Failed to delete PDFs: ' + err);
            });
        },

        reparseSingle(id) {
            this.actionRunning = true;
            fetch(routes.batchReparse, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bill_ids: [id] })
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                alert('PDF data re-extracted successfully!');
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                alert('Error re-parsing: ' + err);
            });
        },

        deleteSingle(id, ca) {
            if (!confirm(`Delete physical PDF for CA ${ca}?`)) return;
            this.actionRunning = true;

            fetch(routes.deleteSingle, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                alert('Error deleting PDF: ' + err);
            });
        },

        redownloadSingle(id) {
            this.actionRunning = true;
            this.loadingBillId = id;
            fetch(routes.batchRedownload, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bill_ids: [id] })
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                this.loadingBillId = null;
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                this.loadingBillId = null;
                alert('Error downloading: ' + err);
            });
        },

        runHealthScan() {
            this.healthScanning = true;
            fetch(routes.healthCheck)
                .then(r => r.json())
                .then(data => {
                    this.healthScanning = false;
                    this.healthData = data;
                })
                .catch(err => {
                    this.healthScanning = false;
                    alert('Health scan failed: ' + err);
                });
        },

        runStorageSync() {
            this.actionRunning = true;
            fetch(routes.syncStorage, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                this.actionRunning = false;
                alert(data.message);
                window.location.reload();
            })
            .catch(err => {
                this.actionRunning = false;
                alert('Storage sync error: ' + err);
            });
        },

        openDeleteModal(month, year, label, sizeFormatted, pdfCount, totalBills) {
            this.deleteModalData = {
                month: month,
                year: year,
                label: label,
                totalSize: sizeFormatted,
                pdfCount: pdfCount,
                totalBills: totalBills,
                olderThanCurrent: false,
                targetScope: 'all',
            };
            this.showDeleteModal = true;
        },

        openDeleteOlderModal() {
            this.deleteModalData = {
                month: null,
                year: null,
                label: 'All Previous Billing Months',
                totalSize: 'All Old Cycles',
                pdfCount: 'All Historical',
                totalBills: 'All Previous',
                olderThanCurrent: true,
                targetScope: 'all',
            };
            this.showDeleteModal = true;
        },

        confirmCycleDelete() {
            this.deleteRunning = true;
            const payload = {
                target_scope: this.deleteModalData.targetScope,
            };
            if (this.deleteModalData.olderThanCurrent) {
                payload.older_than_current = true;
            } else {
                payload.month = this.deleteModalData.month;
                payload.year = this.deleteModalData.year;
            }

            fetch(routes.purgeCycle, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                this.deleteRunning = false;
                this.showDeleteModal = false;
                alert(data.message || 'PDF files deleted successfully!');
                window.location.reload();
            })
            .catch(err => {
                this.deleteRunning = false;
                alert('Error deleting PDFs: ' + err);
            });
        },

        handleFileSelect(e) {
            this.uploadFiles = Array.from(e.target.files);
        },

        submitUpload() {
            if (this.uploadFiles.length === 0) return;
            this.uploadRunning = true;

            const formData = new FormData();
            formData.append('billing_month', this.uploadMonth);
            formData.append('billing_year', this.uploadYear);
            if (this.uploadMruId) {
                formData.append('mru_id', this.uploadMruId);
            }

            this.uploadFiles.forEach(file => {
                formData.append('files[]', file);
            });

            fetch(routes.upload, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                this.uploadRunning = false;
                alert(data.message || 'Files uploaded!');
                window.location.reload();
            })
            .catch(err => {
                this.uploadRunning = false;
                alert('Upload failed: ' + err);
            });
        }
    };
}
