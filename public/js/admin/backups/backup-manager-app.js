function adminBackupApp() {
    return {
        showModal: false,
        loadingModal: false,
        manifestData: null,

        inspectManifest(backupId) {
            this.showModal = true;
            this.loadingModal = true;
            this.manifestData = null;

            fetch(`/admin/backups/${backupId}/manifest`)
                .then(res => res.json())
                .then(data => {
                    this.manifestData = data;
                    this.loadingModal = false;
                })
                .catch(err => {
                    alert('Failed to load backup manifest: ' + err);
                    this.showModal = false;
                    this.loadingModal = false;
                });
        },

        closeModal() {
            this.showModal = false;
            this.loadingModal = false;
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminBackupApp = adminBackupApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminBackupApp', adminBackupApp);
        }
    });
}
