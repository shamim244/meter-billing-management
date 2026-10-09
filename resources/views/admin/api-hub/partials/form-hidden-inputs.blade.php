<!-- Preserved values to avoid losing settings when saving from different tabs -->
<input type="hidden" name="active_tab" :value="activeTab">

<input type="hidden" name="api_master_enabled" :value="apiMaster ? '1' : ''" :disabled="!apiMaster">
<input type="hidden" name="user_keys_enabled" :value="userKeys ? '1' : ''" :disabled="!userKeys">
<input type="hidden" name="feature_automation_enabled" :value="featureAutomation ? '1' : ''" :disabled="!featureAutomation">
<input type="hidden" name="feature_mobile_sync_enabled" :value="featureMobileSync ? '1' : ''" :disabled="!featureMobileSync">
<input type="hidden" name="feature_batch_sync_enabled" :value="featureBatchSync ? '1' : ''" :disabled="!featureBatchSync">
<input type="hidden" name="feature_consumer_updates_enabled" :value="featureConsumerUpdates ? '1' : ''" :disabled="!featureConsumerUpdates">
<input type="hidden" name="public_docs_enabled" :value="publicDocs ? '1' : ''" :disabled="!publicDocs">
<input type="hidden" name="rate_limiting_enabled" :value="rateLimiting ? '1' : ''" :disabled="!rateLimiting">
<input type="hidden" name="allow_permanent_keys" :value="allowPermanent ? '1' : ''" :disabled="!allowPermanent">

<!-- Numeric values always submitted -->
<input type="hidden" name="general_per_minute" :value="general">
<input type="hidden" name="review_per_minute" :value="review">
<input type="hidden" name="batch_per_minute" :value="batch">
<input type="hidden" name="login_per_minute" :value="login">
<input type="hidden" name="openapi_per_minute" :value="openapi">
<input type="hidden" name="max_keys_per_user" :value="maxKeys">
<input type="hidden" name="default_key_lifetime_days" :value="defaultLifetime">
