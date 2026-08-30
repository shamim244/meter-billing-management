# Refer & Earn System — PRD
**Product:** NBPDCL SaaS Billing Platform
**Module:** Refer & Earn System
**Version:** 1.0
**Status:** Draft for Development
**Depends on:** Coupon Code Management System (done), Wallet System (done), Billing & Subscription System (done)

> **Terminology:** Agent / User (`user_id`) throughout. No "Tenant" anywhere.

---

## 1. Purpose

Every Agent gets an auto-generated referral code. When a new Agent registers using that code and later makes a qualifying payment, the REFERRER earns a reward — a one-time payout, protected against refund/downgrade exploitation, extending the Coupon Code Management System rather than duplicating it.

---

## 2. Core Design Decisions (Confirmed Across This Conversation)

```
✅ ONE-TIME payout, not truly recurring — protects against the
   refund/downgrade clawback problem identified earlier
✅ Default trigger: referee's FIRST SUBSCRIPTION PAYMENT
   (not top-up) — a subscription proves real platform usage,
   not just a wallet deposit that could be reversed with nothing
   to show for it
✅ WHICH trigger (subscription vs top-up) is a DYNAMIC ADMIN
   SETTING — platform-wide, switchable anytime, not hardcoded
✅ Reward = fixed % or flat amount, admin-configurable platform-wide
✅ Admin can OVERRIDE the reward for a SPECIFIC Agent (custom %
   or fixed amount different from the platform default)
✅ Referral code auto-generated per Agent at registration —
   no admin action needed to create it
✅ Reward protected by a HOLD PERIOD before actual payout, plus
   CLAWBACK if a refund/downgrade happens after payout
```

---

## 3. How This Extends the Coupon Engine (Not a Parallel System)

Per the Coupon Code Management System PRD's section 9, these extension points were deliberately left open. This system now uses them:

```
coupon_codes.type gets a new value: 'referral'
   (no migration touching existing rows — the column was always
   a flexible string, exactly as designed)

coupon_codes.owner_user_id — NEW column added now, per the plan:
   set to the referring Agent's user_id for referral-type codes,
   NULL for admin-created coupon codes (subscription_discount /
   topup_bonus types remain unaffected — this column is simply
   unused for them)

WalletService::credit() — same method used by Coupon System's
   topup_bonus, now also used here with source =
   'referral_bonus_pending' and later 'referral_bonus_paid'
   (see section 5 on hold/clawback state)
```

This system does **not** duplicate coupon validation, redemption logging, or admin CRUD patterns — it reuses `CouponService` and `CouponRedemptionService` from the existing system, adding referral-specific logic only where genuinely new behavior is needed (auto-generation, hold period, clawback, dynamic trigger setting).

---

## 4. Referral Code Lifecycle

```
Agent registers → system automatically calls
CouponService::createCoupon() with:
   type = 'referral'
   owner_user_id = new Agent's user_id
   code = auto-generated (e.g. "REF-A7X9K2", readable/shareable format)
   discount_kind / discount_value = read from PLATFORM DEFAULT
      referral settings at the moment of generation (see section 6)
   usage_limit_per_user = 1 (a person can only be referred once,
      ever, platform-wide — enforced by checking if the REFEREE
      has ever redeemed ANY referral code before, not just this one)
   usage_limit_total = null (unlimited — a referrer's code can be
      used by many different new Agents)
   is_active = true (referral codes never expire by default, admin
      can still deactivate a specific Agent's code if needed, e.g.
      abuse)

Agent sees this code in their dashboard, with a shareable link:
   yourdomain.com/register?ref=REF-A7X9K2
```

---

## 5. Referee Flow — What the New Agent Gets

```
New Agent registers with a valid referral code in the URL/field
→ System validates: code exists, is_active, type = 'referral',
  AND this new Agent has never redeemed a referral code before
  (prevents someone using multiple referral links across several
  fake accounts, or the same person being "referred" twice)
  AND the new Agent is NOT the same person as the referrer
  (basic self-referral check — reject if referrer_user_id would
  equal the new registering user's identity; where feasible, also
  flag if the same device/IP created both accounts as a fraud
  signal for Admin review, though this is NOT full fraud detection)

Referee-side benefit (confirm with Admin — see section 8):
   Optionally, the referee ALSO gets a small discount/bonus on
   their qualifying first payment, same as any subscription_
   discount coupon — reusing the exact same mechanism already
   built. This is OPTIONAL and admin-configurable (can be ₹0/0%
   if you only want to reward the referrer, not the referee).
```

---

## 6. Referrer Reward — Hold Period + Clawback

### 6.1 Trigger (Dynamic, Admin-Configurable)

```
referral_settings (platform-wide, single row or key-value):
├── reward_trigger              -- 'subscription' | 'topup'
│                                  (default: 'subscription')
├── reward_kind                  -- 'percentage' | 'flat'
├── reward_value                  -- e.g. 10 (%) or ₹50 (flat)
├── minimum_qualifying_amount       -- e.g. ₹100 — a subscription
│                                    payment or top-up below this
│                                    amount does NOT trigger any
│                                    referral payout at all,
│                                    prevents trivial ₹1 payments
│                                    from farming referral rewards
├── hold_period_days               -- e.g. 7 (default), admin-editable
├── referee_discount_kind           -- nullable, optional bonus for
│                                     referee too (see section 5)
└── referee_discount_value

This is ONE settings record Admin edits from a simple form —
NOT per-coupon, since it governs the DEFAULT behavior for ALL
auto-generated referral codes. Per-Agent overrides (section 7)
sit on top of this default.
```

### 6.2 Payout Flow

```
Referee makes their FIRST qualifying payment (per reward_trigger
setting — subscription or top-up)
→ System checks: did this Agent register via a referral code?
→ If yes: create a referral_payouts record with status = 'pending'
  and calculate the reward amount (using the referrer's override
  if one exists, otherwise the platform default from
  referral_settings)
→ Reward is NOT credited to the referrer's wallet yet

After hold_period_days has passed with NO refund/downgrade event
affecting the qualifying payment:
→ Scheduled job (reuses the existing daily scheduler pattern from
  Billing & Subscription System) finds 'pending' payouts whose
  hold period has expired
→ Credits the referrer's wallet via WalletService::credit(),
  source = 'referral_bonus_paid'
→ Updates referral_payouts.status = 'paid'
→ Fires a notification to the referrer (new event type,
  wired into existing Notification System, ROUTINE priority)
```

### 6.3 Clawback

```
If a refund OR mid-cycle downgrade occurs on the referee's
qualifying payment:

CASE A — Payout still 'pending' (within hold period):
   → Simply cancel it. Update referral_payouts.status =
     'cancelled'. No wallet action ever happened, nothing to
     reverse. Referrer notified their pending referral bonus
     was cancelled due to a refund/downgrade on the referred
     Agent's account.

CASE B — Payout already 'paid' (hold period had passed):
   → Reverse via WalletService::debit() (or adminAdjust() if
     the referrer's balance can't cover it — reuse existing
     admin-forced-negative mechanism from Wallet System, since
     this is exactly the scenario that mechanism exists for)
   → Update referral_payouts.status = 'clawed_back'
   → Referrer notified of the reversal with a clear reason

CASE C — Referrer's account is DELETED before a 'pending' payout
matures (distinct from suspension — see section 13 open items
for the suspension case, which is handled differently):
   → Update referral_payouts.status = 'cancelled', reason =
     'referrer_account_deleted'
   → No wallet action attempted, since there's no account left
     to credit
```

### 6.4 Referral Code Regeneration

```
An Agent (or Admin acting on their behalf) can request their
referral code be regenerated — e.g. if it was shared somewhere
unintended, or leaked.

ReferralService::regenerateCode(user_id):
   → Deactivates the old coupon_codes record (is_active = false)
   → Any 'pending' referral_payouts already tied to the OLD code
     are unaffected and continue to mature normally — regeneration
     only stops the OLD code from being used for NEW signups,
     it does not retroactively cancel referrals already in progress
   → Generates a fresh code via the same createCoupon() flow used
     at registration
```

---

## 7. Admin Per-Agent Override

```
Admin can, for a SPECIFIC Agent, override:
   - Their reward_kind / reward_value (different % or flat amount
     than the platform default)
   - Deactivate their referral code specifically (e.g. suspected abuse)

This override is stored directly on that Agent's coupon_codes
record (type = 'referral'), since discount_kind/discount_value
already exist as columns on coupon_codes from the base Coupon
System — no new table needed, just Admin editing an individual
Agent's auto-generated code same as they'd edit any coupon.
```

---

## 8. Database Schema

```sql
-- Extends existing coupon_codes table (no new table for the code itself)
ALTER TABLE coupon_codes ADD COLUMN:
  owner_user_id  -- nullable FK to users, set only for type = 'referral'

-- Platform-wide referral configuration (single settings record)
referral_settings
├── id
├── reward_trigger            -- 'subscription' | 'topup'
├── reward_kind                 -- 'percentage' | 'flat'
├── reward_value
├── hold_period_days
├── referee_discount_kind         -- nullable
├── referee_discount_value
└── updated_at

-- Every referral payout, tracked through its full lifecycle
referral_payouts
├── id
├── referral_coupon_code_id    -- FK coupon_codes (the referrer's code)
├── referrer_user_id
├── referee_user_id
├── qualifying_payment_reference_type   -- 'subscription_payment' | 'topup'
├── qualifying_payment_reference_id
├── reward_amount
├── status                      -- 'pending' | 'paid' | 'cancelled' | 'clawed_back'
├── hold_expires_at
├── paid_at                      -- nullable
├── clawed_back_at                -- nullable
├── clawback_reason                -- nullable
├── wallet_transaction_id           -- FK, nullable until paid
└── created_at
```

---

## 9. Service Layer

```
ReferralService
├── generateCodeForNewAgent(user_id) — called automatically on
│     registration, uses CouponService::createCoupon() with
│     type='referral', reading current referral_settings as defaults
├── regenerateCode(user_id) — deactivates old code, generates new
│     one, does not affect already-in-progress payouts (section 6.4)
├── validateReferralCode(code, new_user_id) — checks code validity,
│     that new_user_id has never redeemed any referral code before
│     (platform-wide one-time-referred rule), AND that new_user_id
│     is not the same person as the code's owner_user_id
│     (self-referral check, section 5)
├── recordReferralSignup(code, new_user_id) — links referee to
│     referrer at registration, before any payment has happened yet
├── checkAndCreatePendingPayout(user_id, payment_reference) —
│     called from a listener on subscription/top-up success events,
│     checks if this Agent was referred, that the qualifying
│     payment meets minimum_qualifying_amount, and this is their
│     QUALIFYING first payment (matching reward_trigger setting),
│     creates the 'pending' referral_payouts record
├── processExpiredHoldPeriods() — scheduled job, finds 'pending'
│     payouts past hold_expires_at, credits wallet, marks 'paid'
├── handleClawback(payment_reference, reason) — called from
│     existing refund/downgrade event listeners, finds any
│     referral_payouts tied to that payment, cancels or reverses
│     depending on current status
├── getAdminOverride(user_id) / setAdminOverride(user_id, kind, value)
```

## 9.1 Notification Events (Explicit Naming — Wired to Existing Notification System)

```
referral.reward_pending    -- fired when referee's qualifying
                              payment creates a pending payout
                              (ROUTINE, notifies the referrer:
                              "Your referral is pending, reward
                              arrives in N days if no issues")
referral.reward_paid        -- fired when hold period passes and
                              wallet is credited (ROUTINE)
referral.reward_cancelled    -- fired if payout cancelled during
                              hold period, or due to referrer
                              account deletion (ROUTINE)
referral.reward_clawed_back   -- fired if a reversal happens after
                              payout (ROUTINE — but the message
                              should be clear and specific, since
                              this involves money being taken back)

All four are ROUTINE priority (not CRITICAL) — these are
informational, not account-threatening events, consistent with
how the base Notification System PRD classifies non-urgent
financial updates (e.g. wallet.credited, wallet.debited).
```

---

## 10. Admin Panel

```
✅ Referral Settings page — edit reward_trigger, reward_kind/value,
   hold_period_days, referee_discount (the platform-wide defaults)
✅ Per-Agent override — from the existing Agent detail/wallet
   admin view, add a "Referral Reward Override" section
✅ Referral activity log — all referral_payouts across the
   platform: pending, paid, cancelled, clawed back — filterable
   by referrer, status, date range
✅ Top Referrers view — simple leaderboard (count of successful
   'paid' payouts per Agent) — useful for identifying who's
   actually driving acquisition for potential recognition/support
✅ Deactivate a specific Agent's referral code (abuse handling)
```

---

## 11. Agent-Facing UI

```
"My Referrals" dashboard section:
├── Their referral code + shareable link (WhatsApp share button,
│     given your Agent base's likely daily WhatsApp usage)
├── [Regenerate Code] button — deactivates old code, issues a
│     new one, with a confirmation warning that the old link
│     will stop working for new signups (existing pending
│     payouts are unaffected, per section 6.4)
├── Stats: total referred, pending rewards, paid rewards, total
│     earned lifetime
├── List of individual referrals with status (pending/paid/
│     clawed back) — transparency on why a reward hasn't landed
│     yet if it's still in hold period
```

---

## 12. What This System Does NOT Do (Out of Scope)

```
❌ True recurring payouts (rejected earlier in favor of one-time
   + hold/clawback, for the exact financial-exposure reasons
   already discussed)
❌ Referral tiers/gamification (e.g. "refer 5, get bonus multiplier")
   — not requested, clean future addition on top of
   referral_payouts data if wanted later
❌ Fraud detection beyond the basic "one referral redemption per
   new Agent, ever" rule — e.g. IP-based abuse detection, device
   fingerprinting — not built this pass
```

---

## 13. Open Items Requiring Confirmation

| Item | Status |
|---|---|
| Exact default hold_period_days value | ❌ Not decided — recommend 7 days as a starting default, admin-editable |
| Exact default reward_value and reward_kind | ❌ Not decided — you'll set this from the Admin Referral Settings page directly, no code-level default needed beyond a safe placeholder (e.g. 10%) |
| Whether referee gets any discount at all, or reward is referrer-only | ❌ Not decided — architecture supports either, confirm your preference before launch |
| What happens if the referring Agent's OWN account gets suspended before a pending payout matures — does the payout still process? | ❌ Not decided — recommend: pending payouts still mature normally (referrer earned it before suspension), but confirm this matches your intent |
