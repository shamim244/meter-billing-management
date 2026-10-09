/**
 * Admin User 360° Dossier Alpine.js Application
 * Handles modals, MRU selection toggles, and data cleanup workflows
 */
function userDossierApp(config) {
    config = config || {};
    return {
        showGrantModal: false,
        showQuotaModal: false,
        showNotificationModal: false,
        showCleanupModal: false,
        cleanupTab: 'pdfs',
        grantMode: 'new_plan',
        selectedPlanId: config.defaultPlanId || '',
        pdfScope: 'all',
        pdfMruId: config.defaultMruId || '',
        pdfMonth: config.currentMonth || '1',
        pdfYear: config.currentYear || '2026',
        selectedMruIds: [],
        selectAllMrus: false,
        mruIds: config.mruIds || [],
        billMruId: '',
        billMonth: '',
        billYear: '',
        billStatus: 'all',
        purgeConfirm: '',

        toggleAllMrus() {
            if (this.selectAllMrus) {
                this.selectedMruIds = [...this.mruIds];
            } else {
                this.selectedMruIds = [];
            }
        }
    };
}

window.userDossierApp = userDossierApp;
