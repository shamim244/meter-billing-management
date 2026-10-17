/**
 * User Panel API Keys Manager Alpine.js Application
 * Handles key generation modal, revocation confirmation modal, secret copying, and per-row Instant Reveal/Unreveal.
 */

/**
 * Universal clipboard copy with fallback for all browsers and HTTP/HTTPS contexts.
 */
function copyTextToClipboard(text) {
    if (!text) return;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).catch(function () {
            fallbackCopyText(text);
        });
    } else {
        fallbackCopyText(text);
    }
}

/**
 * Fallback clipboard copy using an off-screen textarea.
 */
function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.top = '0';
    textArea.style.left = '0';
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = '0';
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
    } catch (err) {
        console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
}

/**
 * Global User Panel API Keys Page Manager
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
            copyTextToClipboard(text);
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
 * Per-row Instant Reveal / Unreveal & 1-Click Copy Component
 */
function apiKeyRow(keyId, prefix, initialPlainKey) {
    return {
        keyId: keyId,
        prefix: prefix,
        plainKey: initialPlainKey || '',
        isRevealable: Boolean(initialPlainKey),
        revealed: false,
        copied: false,

        toggleReveal() {
            if (!this.isRevealable) {
                alert('This legacy API key was created before the reveal feature was enabled. Please generate a new key.');
                return;
            }
            this.revealed = !this.revealed;
        },

        copyKey() {
            if (!this.plainKey) {
                alert('This legacy API key was created before the reveal feature was enabled. Please generate a new key.');
                return;
            }

            copyTextToClipboard(this.plainKey);
            this.copied = true;
            setTimeout(() => {
                this.copied = false;
            }, 2000);
        }
    };
}

window.copyTextToClipboard = copyTextToClipboard;
window.userApiKeysManager = userApiKeysManager;
window.apiKeyRow = apiKeyRow;
