# Database Schema & Model Invariants

The database maintains strict referential integrity across 13 core tables tracked by the **Universal Migration Engine**.

---

## 📊 Entity Relationship Diagram (Core Models)

```
┌──────────────┐       1:N       ┌─────────────────────┐
│    users     ├────────────────►│  consumer_accounts   │
└───┬──────┬───┘                 └──────────┬──────────┘
    │      │                                │ 1:N
    │ 1:1  │ 1:N                            ▼
    │      │                     ┌─────────────────────┐
    │      └────────────────────►│    bill_records     │
    ▼                            └──────────┬──────────┘
┌──────────────┐                            │ 1:1
│   wallets    │                            ▼
└───┬──────────┘                 ┌─────────────────────┐
    │ 1:N                        │    bill_statuses    │
    ▼                            └─────────────────────┘
┌──────────────┐
│ transactions │
└──────────────┘
```

---

## 🗄️ Core Tables Reference

### 1. `users`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `name`: VARCHAR(255)
* `email`: VARCHAR(255) UNIQUE
* `phone`: VARCHAR(20) NULLABLE
* `password`: VARCHAR(255) (Hashed)
* `status`: ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
* `plan_tier`: VARCHAR(50) DEFAULT 'starter'
* `storage_limit_mb`: INT DEFAULT 500
* `is_wallet_frozen`: BOOLEAN DEFAULT 0
* `wallet_frozen_reason`: TEXT NULLABLE
* `wallet_frozen_at`: TIMESTAMP NULLABLE
* `wallet_frozen_by`: BIGINT UNSIGNED NULLABLE
* `shortcuts`: JSON NULLABLE
* `remember_token`: VARCHAR(100) NULLABLE
* `created_at`, `updated_at`: TIMESTAMP

### 2. `consumer_accounts`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED (FK -> users.id)
* `ca_number`: VARCHAR(50) INDEX (Consumer Account Number)
* `consumer_name`: VARCHAR(255)
* `mru_code`: VARCHAR(50) INDEX (Book code e.g. M-101)
* `tariff`: VARCHAR(50) (DS-II, NDS-I, etc.)
* `connected_load`: DECIMAL(8,2)
* `address`: TEXT NULLABLE
* `mobile_no`: VARCHAR(20) NULLABLE

### 3. `bill_records`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED (FK -> users.id)
* `ca_number`: VARCHAR(50) INDEX
* `bill_month`: VARCHAR(20) INDEX (e.g. '2026-09')
* `previous_reading`: DECIMAL(10,2)
* `current_reading`: DECIMAL(10,2) NULLABLE
* `working_reading`: DECIMAL(10,2) NULLABLE (Calculated or manual field reading)
* `units_consumed`: DECIMAL(10,2) NULLABLE
* `net_amount`: DECIMAL(10,2) NULLABLE
* `total_due`: DECIMAL(10,2) NULLABLE
* `pdf_path`: VARCHAR(255) NULLABLE (Relative to storage/app/public/)
* `reading_source`: ENUM('official', 'manual', 'projected') DEFAULT 'official'

### 4. `bill_statuses`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `bill_record_id`: BIGINT UNSIGNED (FK -> bill_records.id)
* `status`: ENUM('Pending', 'Submitted', 'Approved', 'Doubt', 'Critical') INDEX
* `remark`: TEXT NULLABLE
* `review_tag`: VARCHAR(50) NULLABLE
* `verified_by`: BIGINT UNSIGNED NULLABLE

### 5. `wallets` (Bavix Wallet Package)
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `holder_type`: VARCHAR(255) (App\Models\User)
* `holder_id`: BIGINT UNSIGNED INDEX
* `name`: VARCHAR(255)
* `slug`: VARCHAR(255) INDEX
* `description`: VARCHAR(255) NULLABLE
* `balance`: BIGINT (Stored in minor units / cents)
* `decimal_places`: SMALLINT DEFAULT 2

### 6. `transactions`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `payable_type`: VARCHAR(255)
* `payable_id`: BIGINT UNSIGNED
* `wallet_id`: BIGINT UNSIGNED (FK -> wallets.id)
* `type`: ENUM('deposit', 'withdraw') INDEX
* `amount`: BIGINT
* `confirmed`: BOOLEAN INDEX
* `meta`: JSON NULLABLE

### 7. `system_settings`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `key`: VARCHAR(100) UNIQUE INDEX
* `value`: LONGTEXT NULLABLE
* `type`: VARCHAR(50) DEFAULT 'string'
* `group`: VARCHAR(50) INDEX

### 8. `api_keys`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED (FK -> users.id)
* `name`: VARCHAR(255)
* `key_hash`: VARCHAR(64) UNIQUE INDEX (SHA-256)
* `key_prefix`: VARCHAR(16)
* `last_used_at`: TIMESTAMP NULLABLE
* `expires_at`: TIMESTAMP NULLABLE

### 9. `issue_reports`
* `id`: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
* `user_id`: BIGINT UNSIGNED NULLABLE
* `code`: VARCHAR(20) UNIQUE INDEX (e.g. ISS-9482)
* `title`: VARCHAR(255)
* `description`: TEXT
* `status`: ENUM('pending', 'verified', 'spam', 'resolved') DEFAULT 'pending'
* `screenshot_path`: VARCHAR(255) NULLABLE
