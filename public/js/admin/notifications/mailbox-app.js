/**
 * Hostinger Live Mailbox Inspector App
 * Decoupled Alpine.js Component
 */

function mailboxApp() {
    return {
        viewModal: false,
        composeModal: false,
        loadingContent: false,
        currentSubject: '',
        currentFrom: '',
        currentHtml: '',
        currentText: '',

        viewMessage(address, uid, subject, from) {
            this.viewModal = true;
            this.loadingContent = true;
            this.currentSubject = subject;
            this.currentFrom = from;
            this.currentHtml = '';
            this.currentText = '';

            fetch(`/admin/notifications/mailbox/${uid}/content?address=${encodeURIComponent(address)}`)
                .then(res => res.json())
                .then(data => {
                    this.currentHtml = data.html || '';
                    this.currentText = data.text || '';
                    this.loadingContent = false;
                })
                .catch(err => {
                    alert('Failed to load message content: ' + err);
                    this.loadingContent = false;
                });
        },

        closeViewModal() {
            this.viewModal = false;
        },

        openComposeModal() {
            this.composeModal = true;
        },

        closeComposeModal() {
            this.composeModal = false;
        }
    };
}

if (typeof window !== 'undefined') {
    window.mailboxApp = mailboxApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('mailboxApp', () => mailboxApp());
        }
    });
}
