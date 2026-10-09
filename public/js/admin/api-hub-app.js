/**
 * NBPDCL Admin API Hub Controller
 */
function apiHubManager() {
    const config = window.apiHubConfig || {};

    return {
        activeTab: new URLSearchParams(window.location.search).get('tab') || 'analytics',
        apiMaster: config.apiMaster ?? true,
        userKeys: config.userKeys ?? true,
        featureAutomation: config.featureAutomation ?? true,
        featureMobileSync: config.featureMobileSync ?? true,
        featureBatchSync: config.featureBatchSync ?? true,
        featureConsumerUpdates: config.featureConsumerUpdates ?? true,
        publicDocs: config.publicDocs ?? true,
        rateLimiting: config.rateLimiting ?? true,
        allowPermanent: config.allowPermanent ?? false,
        general: config.general ?? 240,
        review: config.review ?? 120,
        batch: config.batch ?? 30,
        login: config.login ?? 15,
        openapi: config.openapi ?? 60,
        maxKeys: config.maxKeys ?? 5,
        defaultLifetime: config.defaultLifetime ?? 90,
        confirmReset: false,
        confirmClearAnalytics: false,
        revokingKey: null
    };
}
