/**
 * Admin Notification Templates App
 * Pure decoupled JavaScript - zero Blade directives.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('notificationTemplatesApp', (config = {}) => ({
        editModal: false,
        previewModal: false,
        activeTemplate: null,
        previewSubject: '',
        previewBody: '',
        previewUrl: config.previewUrl || '/admin/notifications/templates/preview',
        csrfToken: config.csrfToken || '',

        openEdit(t) {
            this.activeTemplate = Object.assign({}, t);
            this.editModal = true;
        },

        async openPreview() {
            if (!this.activeTemplate) return;
            try {
                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        subject: this.activeTemplate.subject,
                        body_template: this.activeTemplate.body_template
                    })
                });
                const data = await res.json();
                this.previewSubject = data.subject || '(No Subject - In-App)';
                this.previewBody = data.formatted_html || '';
                this.previewModal = true;
            } catch (err) {
                console.error('Failed to preview template', err);
            }
        }
    }));
});

window.notificationTemplatesApp = function(config = {}) {
    return {
        editModal: false,
        previewModal: false,
        activeTemplate: null,
        previewSubject: '',
        previewBody: '',
        previewUrl: config.previewUrl || '/admin/notifications/templates/preview',
        csrfToken: config.csrfToken || '',

        openEdit(t) {
            this.activeTemplate = Object.assign({}, t);
            this.editModal = true;
        },

        async openPreview() {
            if (!this.activeTemplate) return;
            try {
                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken
                    },
                    body: JSON.stringify({
                        subject: this.activeTemplate.subject,
                        body_template: this.activeTemplate.body_template
                    })
                });
                const data = await res.json();
                this.previewSubject = data.subject || '(No Subject - In-App)';
                this.previewBody = data.formatted_html || '';
                this.previewModal = true;
            } catch (err) {
                console.error('Failed to preview template', err);
            }
        }
    };
};
