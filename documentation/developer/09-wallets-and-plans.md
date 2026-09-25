# 09 — Wallets, Subscription Plans & Payment Gateways

The financial core uses the **Bavix Laravel-Wallet** double-entry bookkeeping ledger to manage agent balances, credit deductions for bill processing, subscription plans, and multi-gateway payment processing.

---

## 💳 Double-Entry Wallet Ledger

Every financial event (deposit, withdrawal, fee, rebate) is recorded in immutable debit/credit transaction rows:

* **Table `wallets`**: Holds the current balance and float balance for each user.
* **Table `transactions`**: Immutable ledger entries (`type = deposit | withdraw`, `amount`, `confirmed`, `meta`).

### Wallet Freezing
Administrators can freeze an agent's wallet from the Admin Panel (`/admin/wallets/{user}`):
* `is_wallet_frozen`: Boolean flag preventing further withdrawals or bill deductions.
* `wallet_frozen_reason`: Audit explanation for compliance.
* `wallet_frozen_by`: Administrator ID who ordered the hold.

---

## 📋 Subscription Plans & Duration Console

Billing agents subscribe to tiered plans:

| Plan Tier | MRU Limit | Monthly Bills Quota | Storage Limit |
| :--- | :--- | :--- | :--- |
| **Starter** | 2 MRUs | 500 Bills | 250 MB |
| **Professional** | 10 MRUs | 5,000 Bills | 2 GB |
| **Enterprise** | Unlimited | Unlimited | 20 GB |

### Dedicated Plan Duration Console (`/admin/plans/{plan}/durations`)
Plans support customizable durations (e.g. 1 Month, 3 Months, 1 Year) with volume discount percentages and coupon code attachments.

---

## 🧪 Payment Simulator & Webhook Testing

To verify payments without charging real credit cards or UPI accounts, administrators have access to the **Payment Gateway Simulator** (`/admin/payments/simulator`):

* Simulates **Razorpay** and **Cashfree** checkout flows.
* Emits cryptographic webhook events (`payment.captured`, `payment.failed`).
* Validates wallet balance top-ups in an isolated sandbox environment.
