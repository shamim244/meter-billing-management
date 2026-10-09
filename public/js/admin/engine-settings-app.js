/**
 * NBPDCL Admin Billing & Extraction Engine Settings Alpine Controller
 */
function engineSettingsManager() {
    const config = window.engineSettingsConfig || {};

    return {
        globalSpikeMultiplier: config.globalSpikeMultiplier ?? 2.0,
        minSpikeBuffer: config.minSpikeBuffer ?? 30,
        agriMultiplier: config.agriMultiplier ?? 4.0,
        commMultiplier: config.commMultiplier ?? 2.5,
        domMultiplier: config.domMultiplier ?? 2.0,

        colorizationEnabled: config.colorizationEnabled ?? true,
        amountSafeCeiling: config.amountSafeCeiling ?? 500,
        amountWarningCeiling: config.amountWarningCeiling ?? 1500,
        amountDangerFloor: config.amountDangerFloor ?? 2500,
        unitsSafeCeiling: config.unitsSafeCeiling ?? 50,
        unitsWarningCeiling: config.unitsWarningCeiling ?? 120,
        unitsDangerFloor: config.unitsDangerFloor ?? 200,

        applyPreset(preset) {
            if (preset === 'rural') {
                this.amountSafeCeiling = 300;
                this.amountWarningCeiling = 1000;
                this.amountDangerFloor = 2000;
                this.unitsSafeCeiling = 35;
                this.unitsWarningCeiling = 80;
                this.unitsDangerFloor = 150;
            } else if (preset === 'balanced') {
                this.amountSafeCeiling = 500;
                this.amountWarningCeiling = 1500;
                this.amountDangerFloor = 2500;
                this.unitsSafeCeiling = 50;
                this.unitsWarningCeiling = 120;
                this.unitsDangerFloor = 200;
            } else if (preset === 'urban') {
                this.amountSafeCeiling = 800;
                this.amountWarningCeiling = 2500;
                this.amountDangerFloor = 5000;
                this.unitsSafeCeiling = 80;
                this.unitsWarningCeiling = 200;
                this.unitsDangerFloor = 350;
            }
        },
        resetColorDefaults() {
            this.colorizationEnabled = true;
            this.amountSafeCeiling = 500;
            this.amountWarningCeiling = 1500;
            this.amountDangerFloor = 2500;
            this.unitsSafeCeiling = 50;
            this.unitsWarningCeiling = 120;
            this.unitsDangerFloor = 200;
        },

        setGlobalMultiplier(val) {
            this.globalSpikeMultiplier = parseFloat(val).toFixed(1);
        },
        adjustGlobalMultiplier(delta) {
            const current = parseFloat(this.globalSpikeMultiplier) || 2.0;
            this.globalSpikeMultiplier = Math.max(1.0, Math.min(10.0, current + delta)).toFixed(1);
        },
        adjustBuffer(delta) {
            const current = parseInt(this.minSpikeBuffer) || 30;
            this.minSpikeBuffer = Math.max(0, Math.min(500, current + delta));
        },
        adjustAgriMultiplier(delta) {
            const current = parseFloat(this.agriMultiplier) || 4.0;
            this.agriMultiplier = Math.max(1.0, Math.min(20.0, current + delta)).toFixed(1);
        },
        adjustCommMultiplier(delta) {
            const current = parseFloat(this.commMultiplier) || 2.5;
            this.commMultiplier = Math.max(1.0, Math.min(10.0, current + delta)).toFixed(1);
        },
        adjustDomMultiplier(delta) {
            const current = parseFloat(this.domMultiplier) || 2.0;
            this.domMultiplier = Math.max(1.0, Math.min(10.0, current + delta)).toFixed(1);
        },
        resetSpikeDefaults() {
            this.globalSpikeMultiplier = '2.0';
            this.minSpikeBuffer = 30;
            this.agriMultiplier = '4.0';
            this.commMultiplier = '2.5';
            this.domMultiplier = '2.0';
        },

        diagCa: '10230041576',
        diagDriver: 'auto',
        diagMonth: String(config.currentMonth || new Date().getMonth() + 1),
        diagYear: String(config.currentYear || new Date().getFullYear()),
        diagLoading: false,
        diagResult: null,

        async runDiagnostic() {
            if (!this.diagCa.trim()) {
                alert('Please enter a CA number for diagnostic testing.');
                return;
            }

            this.diagLoading = true;
            this.diagResult = null;

            try {
                const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch(config.diagnosticUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ca_number: this.diagCa.trim(),
                        driver: this.diagDriver,
                        month: parseInt(this.diagMonth),
                        year: parseInt(this.diagYear)
                    })
                });

                const data = await res.json();
                this.diagResult = data;
            } catch (e) {
                this.diagResult = {
                    success: false,
                    error: 'Network error while contacting diagnostic endpoint: ' + e.message
                };
            } finally {
                this.diagLoading = false;
            }
        }
    };
}
