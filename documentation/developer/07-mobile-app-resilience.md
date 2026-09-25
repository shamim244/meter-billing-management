# 07 — Mobile App Resilience & Dynamic Discovery

When a server is migrated to a new cloud provider, IP address, or domain, field workers with the Flutter mobile application installed on Android/iOS devices must not disconnect or require an emergency App Store update.

---

## 📡 Dynamic Endpoint Discovery (`GET /api/v1/app/config`)

The Flutter mobile application queries this lightweight endpoint during app startup and periodically during background network sync:

### Endpoint:
```http
GET /api/v1/app/config
Accept: application/json
```

### JSON Response:
```json
{
  "success": true,
  "app_name": "NBPDCL Meter Billing",
  "api_version": "v1",
  "active_server_url": "https://billing.nbpdcl.co.in",
  "api_base_url": "https://billing.nbpdcl.co.in/api/v1",
  "fallback_server_urls": [
    "https://backup-api.nbpdcl.co.in/api/v1",
    "http://143.198.88.21/api/v1"
  ],
  "min_app_version": "2.0.0",
  "latest_app_version": "2.1.4",
  "maintenance_mode": false,
  "qr_connect_code": "https://billing.nbpdcl.co.in/api/v1",
  "compression_supported": ["zstd", "brotli", "gzip"],
  "timestamp": 1727284800
}
```

---

## 🔄 Failover & Dynamic Migration Algorithm

```
                 Mobile App boots on Android/iOS device
                                   │
                                   ▼
                 Attempt connection to Cached Primary URL
                                   │
                 ┌─────────────────┴─────────────────┐
                 ▼                                   ▼
          HTTP 200 SUCCESS                   HTTP Timeout / 502 / DNS Failure
                 │                                   │
                 ▼                                   ▼
          Cache updated config               Iterate Fallback Server URLs
          Proceed to dashboard                       │
                                                     ▼
                                             Connect to Healthy Failover
                                             Update local device storage
                                             Display: "Switched to Failover Server"
```

### Key Capabilities:
1. **Zero App Store Lag**: Domain changes take effect immediately across all field devices without waiting days for Google Play Store review.
2. **Version Enforcement**: If `min_app_version` exceeds the device's installed version, the app displays a graceful modal with a direct APK download link.
3. **Maintenance Mode Guard**: If `maintenance_mode = true`, mobile requests pause destructive writes and inform the user of scheduled maintenance.

---

## 📱 QR Connect Pairing

New billing agents can be onboarded in seconds without manually typing API URLs or server hostnames:
1. Administrator opens **Admin > Cloud Migration & Portability** (or **API Hub**).
2. The agent scans the rendered **Server Pairing QR Code** using the mobile app camera.
3. The app parses the `qr_connect_code` payload, validates the handshake, and authenticates the agent.
