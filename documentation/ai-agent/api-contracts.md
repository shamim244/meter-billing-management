# REST API Endpoint Contracts

All API endpoints are prefixed with `/api/v1` and support adaptive compression (`br`, `zstd`, `gzip`) and API rate-limiting analytics.

---

## 🔒 Authentication & Headers

| Header | Description | Required? |
| :--- | :--- | :--- |
| `Authorization` | `Bearer <sanctum_token>` | For mobile agent sessions |
| `X-API-KEY` | Raw 64-char API key | For automated integrations |
| `Accept` | `application/json` | Required |
| `Accept-Encoding` | `zstd, br, gzip` | Highly recommended for 80% compression |

---

## 📡 Endpoints

### 1. Dynamic Server Discovery & Configuration
* **Route**: `GET /api/v1/app/config`
* **Auth**: Public (No key required)
* **Response**:
```json
{
  "success": true,
  "app_name": "NBPDCL Meter Billing",
  "api_version": "v1",
  "active_server_url": "https://billing.nbpdcl.co.in",
  "api_base_url": "https://billing.nbpdcl.co.in/api/v1",
  "fallback_server_urls": [
    "https://backup-api.nbpdcl.co.in/api/v1"
  ],
  "min_app_version": "1.0.0",
  "latest_app_version": "1.2.0",
  "maintenance_mode": false,
  "qr_connect_code": "https://billing.nbpdcl.co.in/api/v1",
  "compression_supported": ["zstd", "brotli", "gzip"],
  "timestamp": 1727284800
}
```

---

### 2. Fetch Assigned MRUs (Books)
* **Route**: `GET /api/v1/mrus`
* **Auth**: Required (`Bearer` or `X-API-KEY`)
* **Response**:
```json
{
  "success": true,
  "data": [
    {
      "mru_code": "M-101",
      "village_name": "Dumra",
      "total_consumers": 450,
      "pending_bills": 120,
      "submitted_bills": 330
    }
  ]
}
```

---

### 3. Fetch Consumers for MRU
* **Route**: `GET /api/v1/mrus/{code}/consumers`
* **Auth**: Required
* **Response**:
```json
{
  "success": true,
  "mru_code": "M-101",
  "consumers": [
    {
      "ca_number": "100234891",
      "consumer_name": "Ramesh Kumar",
      "connected_load": 2.0,
      "tariff": "DS-II",
      "previous_reading": 1420.0,
      "working_reading": 1495.0,
      "net_amount": 620.0,
      "status": "Submitted"
    }
  ]
}
```

---

### 4. Bulk Upload Meter Readings
* **Route**: `POST /api/v1/readings/upload-batch`
* **Auth**: Required
* **Payload**:
```json
{
  "mru_code": "M-101",
  "readings": [
    {
      "ca_number": "100234891",
      "current_reading": 1510.0,
      "reading_source": "manual",
      "status": "Submitted",
      "remark": "Meter verified ok",
      "recorded_at": "2026-09-25T14:20:00Z"
    }
  ]
}
```
* **Response**:
```json
{
  "success": true,
  "synced_count": 1,
  "failed_count": 0,
  "errors": []
}
```

---

## 🚨 Standard Error Schemas

### 401 Unauthorized:
```json
{
  "success": false,
  "error": "Unauthenticated",
  "message": "Valid Bearer token or X-API-KEY header required."
}
```

### 429 Rate Limit Exceeded:
```json
{
  "success": false,
  "error": "RateLimitExceeded",
  "message": "Rate limit exceeded (max 120 requests/minute). Please retry after 15 seconds.",
  "retry_after_seconds": 15
}
```
