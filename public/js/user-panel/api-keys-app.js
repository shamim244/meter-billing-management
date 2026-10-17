/**
 * User Panel API Keys Manager Alpine.js Application
 * Handles key generation modal, revocation confirmation modal, secret copying, and per-row Reveal/Unreveal.
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

/**
 * Per-row Reveal / Unreveal & 1-Click Copy Component
 */
function apiKeyRow(keyId, prefix, isRevealable) {
    return {
        keyId: keyId,
        prefix: prefix,
        isRevealable: Boolean(isRevealable),
        revealed: false,
        plainKey: '',
        loading: false,
        copied: false,
        error: '',

        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                || window.nbpdclCsrfToken 
                || '';
        },

        async toggleReveal() {
            if (this.revealed) {
                this.revealed = false;
                return;
            }

            if (this.plainKey) {
                this.revealed = true;
                return;
            }

            if (!this.isRevealable) {
                alert('This legacy API key was created before reversible reveal was enabled. Please generate a new key.');
                return;
            }

            this.loading = true;
            this.error = '';

            try {
                const response = await fetch(`/user-panel/api-keys/${this.keyId}/reveal`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.getCsrfToken(),
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (data.success && data.plain_key) {
                    this.plainKey = data.plain_key;
                    this.revealed = true;
                } else {
                    this.error = data.message || 'Failed to reveal key.';
                    alert(this.error);
                }
            } catch (err) {
                this.error = 'Network error while revealing key.';
                alert(this.error);
            } finally {
                this.loading = false;
            }
        },

        async copyKey() {
            if (!this.plainKey) {
                if (!this.isRevealable) {
                    alert('This legacy API key was created before reversible reveal was enabled. Please generate a new key.');
                    return;
                }
                this.loading = true;
                try {
                    const response = await fetch(`/user-panel/api-keys/${this.keyId}/reveal`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    if (data.success && data.plain_key) {
                        this.plainKey = data.plain_key;
                    } else {
                        alert(data.message || 'Failed to copy key.');
                        return;
                    }
                } catch (err) {
                    alert('Network error while copying key.');
                    return;
                } finally {
                    this.loading = false;
                }
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(this.plainKey);
            }
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2500);
        }
    };
}

window.userApiKeysManager = userApiKeysManager;
window.apiKeyRow = apiKeyRow;
