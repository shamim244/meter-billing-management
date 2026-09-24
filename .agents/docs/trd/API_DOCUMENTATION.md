# NBPDCL REST API Documentation (v1)

**Specification Standard:** OpenAPI 3.0 / REST  
**Authentication:** Laravel Sanctum Bearer Token  
**Base URL:** `https://your-domain.com/api/v1` (or `http://localhost/api/v1`)  
**Core Principles:** Fast, Efficient, and Secure  

---

## 1. Quick Start Guide

### Step 1: Obtain Your API Token
1. Log into your dashboard on the web.
2. Go to **Profile Settings** $\rightarrow$ **API Tokens**.
3. Click **"Generate New Token"** and copy your secret bearer token.

### Step 2: Make Your First Request
Include the token in the `Authorization` header on every request:

```bash
curl -X GET "http://localhost/api/v1/bills?month=4&year=2026&status=pending" \
     -H "Authorization: Bearer 1|nbpdcl_sec_token_9f823a7b..." \
     -H "Accept: application/json"
```

---

## 2. API Endpoints Reference

---

### Endpoint 1: Fetch Filtered & Sorted Bills
Retrieves the list of consumer bills for a given MRU and billing cycle, supporting every filter and priority sort sequence available on the web dashboard.

* **Method:** `GET`
* **Route:** `/api/v1/bills`
* **Query Parameters:**

| Parameter | Type | Required? | Default | Allowed Values / Description |
|---|---|---|---|---|
| `mru_id` | integer / string | No | null | MRU ID or unique MRU code (e.g. `0244`, `HALA`). |
| `month` | integer | Yes | Current | Billing month (`1` to `12`). |
| `year` | integer | Yes | Current | Billing year (e.g. `2026`). |
| `status` | string | No | `all` | `all`, `pending`, `submitted`, `doubt`, `critical`. |
| `sort_col` | string | No | `ca_number` | `ca_number`, `amount`, `units`, `current_reading`, `previous_reading`, `meter_no`. |
| `sort_asc` | boolean | No | `true` | `true` (Ascending / Low-to-High), `false` (Descending / High-to-Low). |
| `status_sort` | string | No | `default` | Priority sequence: `pdcs` (Pending ➔ Doubt ➔ Critical ➔ Submitted), `dcps`, `cdps`, `spdc`, `default`. |
| `search` | string | No | null | Search substring matching CA number, Consumer Name, or Meter Number. |
| `per_page` | integer | No | `50` | Pagination limit (max `250`). |
| `page` | integer | No | `1` | Page number. |

#### Example Request:
```http
GET /api/v1/bills?mru_id=1&month=4&year=2026&status=pending&sort_col=amount&sort_asc=true&status_sort=pdcs HTTP/1.1
Host: your-domain.com
Authorization: Bearer <API_TOKEN>
Accept: application/json
```

#### Example 200 OK Response:
```json
{
  "success": true,
  "mru": {
    "id": 1,
    "code": "0244",
    "name": "NISARBHATI"
  },
  "period": "04/2026",
  "counts": {
    "all": 244,
    "pending": 41,
    "submitted": 189,
    "critical": 4,
    "doubt": 10
  },
  "pagination": {
    "total": 41,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1
  },
  "data": [
    {
      "id": 1042,
      "ca_number": "10230063090",
      "consumer_name": "SURESH DAS KABIL DAS",
      "tariff_category": "KJ",
      "billing_basis": "OK",
      "meter_no": "3810621",
      "total_amount": 32794.00,
      "units_consumed": 300,
      "db_prev_reading": "500",
      "working_reading": "800",
      "official_pdf_reading": "800",
      "pdf_sync_status": "matched",
      "review_status": "pending",
      "reason_code": null,
      "remark": null
    }
  ]
}
```

---

### Endpoint 2: Submit Human Review Decision
The primary atomic endpoint used by field operators, automation scripts, or AI tools to register a human inspection decision (`submitted`, `doubt`, `critical`, or `pending`) along with reasons, remarks, and readings.

* **Method:** `PATCH`
* **Route:** `/api/v1/bills/review`
* **Headers:** `Content-Type: application/json`
* **Request Body Payload:**

| Field | Type | Required? | Description |
|---|---|---|---|
| `ca_number` | string | **Yes** | 11–12 digit Consumer Account Number. |
| `billing_month` | integer | **Yes** | Billing month (`1` to `12`). |
| `billing_year` | integer | **Yes** | Billing year (e.g. `2026`). |
| `status` | string | **Yes** | Human verdict: `submitted`, `doubt`, `critical`, `pending`. |
| `reason_code` | string | Conditional | Standardized reason code (recommended when marking `doubt` or `critical`). |
| `remark` | string | Optional | Free-form notes from field inspection (max 500 chars). |
| `working_reading` | string | Optional | Updated physical meter reading (auto-syncs master ledger & cascades forward). |

#### Example Payload 1: Marking as SUBMITTED (Verified Normal)
```json
{
  "ca_number": "10230063090",
  "billing_month": 4,
  "billing_year": 2026,
  "status": "submitted",
  "working_reading": "800",
  "remark": null
}
```

#### Example Payload 2: Marking as DOUBT (Locked Premises)
```json
{
  "ca_number": "10230074463",
  "billing_month": 4,
  "billing_year": 2026,
  "status": "doubt",
  "reason_code": "PREMISES_LOCKED",
  "remark": "Main gate locked, neighbor says owner is in Patna"
}
```

#### Example Payload 3: Marking as CRITICAL (Meter Burnt)
```json
{
  "ca_number": "10230058477",
  "billing_month": 4,
  "billing_year": 2026,
  "status": "critical",
  "reason_code": "METER_BURNT_DEAD",
  "remark": "Display burnt and blackened after lightning strike",
  "working_reading": null
}
```

#### Example 200 OK Response:
```json
{
  "success": true,
  "message": "Bill for CA 10230063090 updated to 'submitted'.",
  "data": {
    "ca_number": "10230063090",
    "review_status": "submitted",
    "reason_code": null,
    "remark": null,
    "working_reading": "800",
    "updated_at": "2026-09-19T16:30:00Z"
  }
}
```

---

### Endpoint 3: Offline-to-Online Batch Synchronization
Designed specifically for field operations in rural zones with weak network connectivity. The field client logs all decisions locally throughout the day and pushes the complete batch in one single transaction upon returning to connectivity.

* **Method:** `POST`
* **Route:** `/api/v1/bills/batch-sync`
* **Request Body Payload:**

```json
{
  "billing_month": 4,
  "billing_year": 2026,
  "reviews": [
    {
      "ca_number": "10230063090",
      "status": "submitted",
      "working_reading": "800"
    },
    {
      "ca_number": "10230074463",
      "status": "doubt",
      "reason_code": "PREMISES_LOCKED",
      "remark": "House closed"
    },
    {
      "ca_number": "10230058477",
      "status": "critical",
      "reason_code": "METER_BURNT_DEAD",
      "remark": "Screen dead"
    }
  ]
}
```

#### Example 200 OK Response:
```json
{
  "success": true,
  "total_received": 3,
  "synced_count": 3,
  "failed_count": 0,
  "failures": []
}
```

---

### Endpoint 4: Quick-Pull Single Consumer Bill
Instantly downloads and parses an individual CA bill on demand from the official BSPHCL API.

* **Method:** `POST`
* **Route:** `/api/v1/bills/quick-pull`
* **Payload:**
```json
{
  "ca_number": "10230063090",
  "billing_month": 4,
  "billing_year": 2026,
  "mru_id": 1
}
```

---

### Endpoint 5: Master Data Queries

* **`GET /api/v1/mrus`**: Returns all active MRU workspaces with active consumer counts.
* **`GET /api/v1/consumers?mru_id=1&search=SURESH`**: Searches permanent consumer master accounts.

---

## 3. Standard Reason Code Dictionary

### Doubt Reason Codes (`status: "doubt"`)
| Code | Label in Dashboard | Typical Scenario |
|---|---|---|
| `PREMISES_LOCKED` | 🔒 Premises Locked | House closed, gate padlocked, or owner away. |
| `SUSPICIOUS_READING` | ⚠️ Suspicious Reading | Meter spike or drop inconsistent with historical median. |
| `SUSPICIOUS_AMOUNT` | 💰 Suspicious Amount | Unexpected arrears jump disputed by consumer. |
| `PREV_MONTH_MISMATCH` | 📉 Previous Reading Mismatch | Physical meter dial doesn't match last month's recorded value. |
| `OWNER_RECHECK_REQUEST` | 👤 Consumer Re-check Request | Consumer requested re-examination before submission. |
| `OTHER` | 📝 Other Remark | Custom observation typed by reader. |

### Critical Reason Codes (`status: "critical"`)
| Code | Label in Dashboard | Typical Scenario |
|---|---|---|
| `METER_BURNT_DEAD` | 🔥 Meter Burnt / Dead | Display blackened, burnt, or completely unreadable. |
| `METER_TAMPERED_BYPASS` | ⚡ Tampered / Direct Bypass | Meter seal broken, illegal bypass hooking observed. |
| `METER_MISSING_STOLEN` | 🚫 Meter Missing / Stolen | No meter found at connection site. |
| `PREMISES_DEMOLISHED` | 🏚️ Premises Demolished | Building demolished or service line disconnected. |
| `JE_INTERVENTION_REQUIRED` | ⚖️ SDO / JE Dispute Block | Severe legal dispute; requires DISCOM officer on site. |
| `OTHER` | 📝 Other Remark | Custom unworkable block reason. |

---

## 4. Python ADB Integration Client (Copy-Paste Ready)

Save this module directly into your automation project to connect your Phone A ADB script with the Laravel platform:

```python
"""
NBPDCL Field Automation Client
Connects the local ADB script directly to the Laravel Billing API.
"""

import requests

class NbpdclApiClient:
    def __init__(self, base_url: str, api_token: str):
        self.base_url = base_url.rstrip('/')
        self.headers = {
            "Authorization": f"Bearer {api_token}",
            "Accept": "application/json",
            "Content-Type": "application/json"
        }

    def get_pending_bills(self, mru_id: int, month: int, year: int, sort_col: str = "amount", sort_asc: bool = True):
        """Fetch pending accounts sorted according to field preference."""
        url = f"{self.base_url}/bills"
        params = {
            "mru_id": mru_id,
            "month": month,
            "year": year,
            "status": "pending",
            "sort_col": sort_col,
            "sort_asc": "true" if sort_asc else "false",
            "status_sort": "pdcs",
            "per_page": 250
        }
        res = requests.get(url, headers=self.headers, params=params, timeout=10)
        res.raise_for_status()
        return res.json().get("data", [])

    def submit_review(self, ca_number: str, month: int, year: int, status: str, 
                      reason_code: str = None, remark: str = None, working_reading: str = None):
        """Submit field verdict (submitted, doubt, critical) to the cloud ledger."""
        url = f"{self.base_url}/bills/review"
        payload = {
            "ca_number": ca_number,
            "billing_month": month,
            "billing_year": year,
            "status": status,
            "reason_code": reason_code,
            "remark": remark,
            "working_reading": working_reading
        }
        res = requests.patch(url, headers=self.headers, json=payload, timeout=10)
        res.raise_for_status()
        return res.json()

# Quick Usage in Field Step Mode:
if __name__ == "__main__":
    client = NbpdclApiClient("http://localhost/api/v1", "YOUR_SECRET_TOKEN")
    
    # 1. Pull unbilled accounts sorted by lowest amount first
    bills = client.get_pending_bills(mru_id=1, month=4, year=2026, sort_col="amount", sort_asc=True)
    print(f"Loaded {len(bills)} pending consumers.")

    # 2. Example: House is locked
    # client.submit_review(bills[0]["ca_number"], 4, 2026, status="doubt", reason_code="PREMISES_LOCKED", remark="Gate locked")

    # 3. Example: Photo captured normally
    # client.submit_review(bills[1]["ca_number"], 4, 2026, status="submitted", working_reading="850")
```

## 5. Intelligent Rate Limiting Architecture

To balance **smooth field operations** with **server & database protection against runaway loops**, the API implements tiered rate limiting isolated per API key / device:

| Tier | Endpoints | Limit | Purpose & Balance |
|---|---|---|---|
| **General Reads & Queue** | `GET /bills`, `/automation/queue`, `/mrus`, `/consumers` | **240 req / min** (4 req/sec) | Abundant headroom for fast browsing and bot polling without choking. Stops runaway infinite loops. |
| **Review Submissions** | `PATCH /bills/review`, `POST /automation/update-status` | **120 reviews / min** (2 reviews/sec) | 6x faster than human/ADB photo capture capabilities. Prevents DB write-lock exhaustion. |
| **Batch Offline Sync** | `POST /bills/batch-sync`, `POST /sync/readings/batch` | **30 batch calls / min** | Allows processing up to 30,000 consumers per minute in bulk batches. |
| **Login & Token Generation** | `POST /auth/login` | **15 req / min** | Protects against brute-force and credential stuffing. |

### Device Isolation:
Quotas are tracked per **API Key ID** (`apikey_{id}`). Multiple field workers operating from the same office Wi-Fi or cellular hotspot receive their own independent, full quotas.

### Rate Limit Headers:
Every API response includes standard rate limit headers:
* `X-RateLimit-Limit`: Maximum requests permitted in the 60-second window (e.g. `240`).
* `X-RateLimit-Remaining`: Number of remaining requests in the current window.
* `Retry-After`: Seconds to wait before retrying (only sent upon `429 Too Many Requests`).

---

## 6. Standard HTTP Error Codes

| Status Code | Code Meaning | Explanation |
|---|---|---|
| `200 OK` | Success | The request succeeded and returned the requested payload. |
| `401 Unauthorized` | Invalid Token | Missing, expired, or revoked Bearer token. |
| `403 Forbidden` | Access Denied | Tenant violation (attempting to access another user's MRU or CA). |
| `404 Not Found` | Resource Missing | CA number or MRU ID does not exist in the user's workspace. |
| `422 Unprocessable` | Validation Error | Missing required fields or invalid format (e.g. `month` not between 1–12). |
| `429 Too Many Req` | Rate Limited | Exceeded rate limit window. Contains structured `retry_after_seconds`. |

