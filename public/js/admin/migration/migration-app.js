/**
 * Admin Universal Cloud Migration App
 * Decoupled Alpine.js Component
 */

function adminMigrationApp() {
    return {
        inspectModal: false,
        manifestData: null,

        openInspect(manifest) {
            this.manifestData = manifest;
            this.inspectModal = true;
        },

        closeInspect() {
            this.inspectModal = false;
            this.manifestData = null;
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminMigrationApp = adminMigrationApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminMigrationApp', () => adminMigrationApp());
        }
    });
}
