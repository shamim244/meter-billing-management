/**
 * Developer Portal & Interactive API Console Alpine.js Application
 * Handles language switching, endpoint testing, and code snippet copying
 */
function devPortal(config) {
    config = config || {};
    return {
        activeLang: 'python',
        searchQuery: '',
        consoleEndpoint: '/auth/me',
        consoleApiKey: config.initialApiKey || '',
        consoleResponse: null,
        consoleStatusCode: null,
        consoleLatency: null,
        consoleLoading: false,

        copyCode(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            }
            alert('Copied to clipboard!');
        },

        async sendLiveRequest() {
            this.consoleLoading = true;
            this.consoleResponse = 'Dispatching HTTP request...';
            this.consoleStatusCode = null;
            const startTime = performance.now();

            try {
                const targetUrl = (config.baseUrl || '') + this.consoleEndpoint;
                const headers = {
                    'Accept': 'application/json'
                };
                if (this.consoleApiKey) {
                    headers['Authorization'] = 'Bearer ' + this.consoleApiKey.trim();
                }

                const res = await fetch(targetUrl, { headers });
                const endTime = performance.now();
                this.consoleLatency = Math.round(endTime - startTime);
                this.consoleStatusCode = res.status;

                const json = await res.json();
                this.consoleResponse = JSON.stringify(json, null, 2);
            } catch (err) {
                this.consoleStatusCode = 500;
                this.consoleResponse = 'Network Error: ' + err.message;
            } finally {
                this.consoleLoading = false;
            }
        }
    };
}

window.devPortal = devPortal;
