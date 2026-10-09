/**
 * User Panel API Keys Manager Alpine.js Application
 * Handles key generation modal, revocation confirmation modal, and secret copying
 */
function userApiKeysManager() {
    return {
        createModalOpen: false,
        revokeModalOpen: false,
        revokeKeyId: null,
        revokeKeyName: '',
        copied: false,

        copySecret(text) {
            if (!text) return;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            }
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2500);
        },

        openRevoke(id, name) {
            this.revokeKeyId = id;
            this.revokeKeyName = name;
            this.revokeModalOpen = true;
        }
    };
}

window.userApiKeysManager = userApiKeysManager;
