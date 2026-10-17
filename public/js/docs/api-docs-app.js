/**
 * Developer Portal & Interactive API Console Alpine.js Application
 * Handles language switching, endpoint testing, request bodies, and code snippet copying
 */
function devPortal(config) {
    config = config || {};
    return {
        activeLang: 'python',
        searchQuery: '',
        consoleEndpoint: 'GET /bills?status=pending',
        consoleMethod: 'GET',
        consoleBody: '',
        consoleApiKey: config.initialApiKey || '',
        consoleResponse: null,
        consoleStatusCode: null,
        consoleLatency: null,
        consoleLoading: false,

        init() {
            this.syncEndpointMethod(this.consoleEndpoint);
        },

        getCleanPath() {
            let p = this.consoleEndpoint || '';
            return p.replace(/^(POST|PATCH|GET)\s+/, '');
        },

        syncEndpointMethod(endpoint) {
            if (endpoint.startsWith('POST /consumers/')) {
                this.consoleMethod = 'POST';
                this.consoleBody = JSON.stringify({
                    min_avg_units: 40,
                    max_avg_units: 100,
                    custom_avg_units: 75,
                    is_avg_excluded: false
                }, null, 2);
            } else if (endpoint.startsWith('PATCH ')) {
                this.consoleMethod = 'PATCH';
                this.consoleBody = JSON.stringify({
                    ca_number: '10230063090',
                    billing_month: 10,
                    billing_year: 2026,
                    status: 'doubt',
                    reason_code: 'PREMISES_LOCKED',
                    working_reading: '850'
                }, null, 2);
            } else if (endpoint.startsWith('POST ')) {
                this.consoleMethod = 'POST';
                this.consoleBody = '{}';
            } else {
                this.consoleMethod = 'GET';
                this.consoleBody = '';
            }
        },

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
                const targetUrl = (config.baseUrl || '') + this.getCleanPath();
                const headers = {
                    'Accept': 'application/json'
                };
                if (this.consoleApiKey) {
                    headers['Authorization'] = 'Bearer ' + this.consoleApiKey.trim();
                }

                const options = {
                    method: this.consoleMethod,
                    headers: headers
                };

                if (this.consoleMethod === 'POST' || this.consoleMethod === 'PATCH') {
                    headers['Content-Type'] = 'application/json';
                    options.body = this.consoleBody || '{}';
                }

                const res = await fetch(targetUrl, options);
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
