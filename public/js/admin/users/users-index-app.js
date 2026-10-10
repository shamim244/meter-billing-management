/**
 * Admin Users Index Management App
 * Decoupled Alpine.js Component
 */

function adminUsersIndexApp() {
    return {
        selectedUsers: [],
        selectAll: false,
        bulkAction: '',
        bulkPlanTier: 'pro',

        toggleAll() {
            if (this.selectAll) {
                this.selectedUsers = Array.from(document.querySelectorAll('.user-checkbox')).map(cb => parseInt(cb.value));
            } else {
                this.selectedUsers = [];
            }
        },

        confirmBulkAction(event) {
            if (this.bulkAction === 'delete' && !confirm('Permanently purge selected users and all their storage PDFs?')) {
                if (event) {
                    event.preventDefault();
                }
                return false;
            }
            return true;
        }
    };
}

if (typeof window !== 'undefined') {
    window.adminUsersIndexApp = adminUsersIndexApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('adminUsersIndexApp', () => adminUsersIndexApp());
        }
    });
}
