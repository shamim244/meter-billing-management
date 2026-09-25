# 08 — Notification Engine & Multi-Provider Email Hub

The **Notification Hub** provides transactional email delivery, billing alerts, OTP verifications, and failed-queue redundancy with multi-provider failover.

---

## ⚡ Multi-Provider Priority & Failover Architecture

Rather than hardcoding a single SMTP service, the system maintains a live registry of email providers with automatic failover:

```
                      Dispatch Notification Job
                                  │
                                  ▼
                    Check Highest Priority Provider
                                  │
                 ┌────────────────┴────────────────┐
                 ▼                                 ▼
         Brevo API (Priority 1)            Resend API (Priority 2)
                 │                                 │
                 ├─ SUCCESS: Dispatched!           ├─ SUCCESS: Dispatched!
                 │                                 │
                 └─ FAILED (Quota/Timeout)         └─ FAILED (Network/Key)
                         │                                 │
                         └────────────────┬────────────────┘
                                          │
                                          ▼
                               Hostinger SMTP (Priority 3)
                                          │
                                          ├─ SUCCESS: Dispatched!
                                          │
                                          └─ FAILED:
                                                  │
                                                  ▼
                                       Failed Critical Queue
                                    (Logged for manual re-try)
```

---

## 📑 Notification Templates

Templates are configured with dynamic placeholder variables (e.g. `{name}`, `{amount}`, `{due_date}`, `{ca_number}`):

* `bill_extracted`: Dispatched when a new PDF bill is processed.
* `payment_receipt`: Sent upon approved wallet top-up or bill payment.
* `plan_expiring`: Triggered 3 days prior to subscription renewal.
* `low_wallet_balance`: Sent when agent balance falls below threshold.

---

## 🚨 Failed Critical Queue & Live Mailbox

Accessible in the Admin Panel under **Admin > Notifications Hub**:

1. **Email Providers**: Configure API keys, daily quotas, and test delivery with live ping.
2. **Failed Queue (`/admin/notifications/failed-queue`)**: Inspect undelivered critical emails, review provider error responses, and trigger 1-click batch re-sends.
3. **Live Mailbox Inspector (`/admin/notifications/mailbox`)**: Directly inspect incoming IMAP mailboxes on Hostinger to view bounce notices, delivery receipts, and consumer replies.
