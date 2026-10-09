function rateLimitsApp(config) {
    const c = config || window.rateLimitsConfig || {};
    return {
        enabled: Boolean(c.enabled),
        general: Number(c.general) || 240,
        review: Number(c.review) || 120,
        batch: Number(c.batch) || 30,
        login: Number(c.login) || 15,
        openapi: Number(c.openapi) || 60,
        confirmReset: false,

        setGeneral(val) {
            this.general = Number(val);
        },
        setReview(val) {
            this.review = Number(val);
        },
        setBatch(val) {
            this.batch = Number(val);
        },
        setLogin(val) {
            this.login = Number(val);
        },
        setOpenapi(val) {
            this.openapi = Number(val);
        },
        openResetModal() {
            this.confirmReset = true;
        },
        closeResetModal() {
            this.confirmReset = false;
        }
    };
}

if (typeof window !== 'undefined') {
    window.rateLimitsApp = rateLimitsApp;
}

if (typeof document !== 'undefined') {
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && typeof Alpine.data === 'function') {
            Alpine.data('rateLimitsApp', (config) => rateLimitsApp(config));
        }
    });
}
