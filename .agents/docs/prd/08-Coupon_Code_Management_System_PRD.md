# Coupon Code Management System — PRD
**Product:** NBPDCL SaaS Billing Platform
**Module:** Coupon Code Management System
**Version:** 1.0
**Status:** Draft for Development
**Depends on:** Wallet System (done), Plan Management System (done), Billing & Subscription System (done)
**Designed to support later:** Refer & Earn System (NOT built in this pass — see section 9)

> **Terminology:** Agent / User (`user_id`) throughout. No "Tenant" anywhere.

---

## 1. Purpose

This system lets Admin create and manage discount/bonus codes that Agents can redeem. Two coupon types are supported in this version:

```
1. Subscription Discount — % or flat amount off a plan purchase
2. Top-Up Bonus         — % bonus credited on a wallet top-up
```

The system is architected so that a future **Refer & Earn** system can reuse the exact same redemption engine, ledger, and admin patterns — adding a third coupon type and an auto-generation trigger — without any rework of what's built here. This PRD does not build Refer & Earn itself; it only avoids painting that feature into a corner.

---

## 2. Coupon Type 1 — Subscription Discount

### 2.1 Configuration Fields (Admin sets per coupon)

```
code                  -- e.g. "WELCOME20" (unique, case-insensitive match)
discount_kind         -- 'percentage' | 'flat'
discount_value        -- e.g. 20 (for 20%) or 100 (for ₹100 flat)
plan_restriction       -- nullable: specific plan_id(s), or null = all plans
minimum_amount          -- nullable: coupon only valid if subscription
                          price meets this minimum (rarely needed for
                          subscription type, but supported for consistency)
usage_limit_per_user     -- integer, default 1 (most subscription coupons
                          are one-time-per-Agent)
usage_limit_total         -- nullable, platform-wide cap on total redemptions
expires_at                -- nullable
starts_at                  -- nullable (schedule a future campaign)
is_active
```

### 2.2 Discount Application Logic — Stacking With Duration Discounts

Since Plan Management System already has duration-based discounts (1/2/3/6/12 month pricing), a coupon discount **stacks on top of the already-discounted duration price**, not the original base price:

```
Example:
  Base Starter price:          ₹299/month
  12-month duration price:     ₹239.20/month  (20% duration discount)
  Coupon "WELCOME20" (20% off): applies to ₹239.20 → ₹191.36/month

This avoids double-dipping on the same base number while honoring
both discounts. The order is always: base price → duration discount
→ coupon discount, applied sequentially, never both calculated
independently off the original base price.
```

### 2.3 First-Time-Only Default

```
Default behavior: usage_limit_per_user = 1, and applies only to
an Agent's NEXT subscription purchase (their very next payment,
whether that's their first-ever subscription or a renewal at the
time of redemption). Admin CAN set usage_limit_per_user higher for
special cases, but 1 is the sensible default for acquisition-style
coupons.
```

---

## 3. Coupon Type 2 — Top-Up Bonus

### 3.1 Configuration — Tiered Slabs

One coupon can define **multiple amount-range slabs**, so an Agent enters ONE code and the system determines their bonus % based on how much they actually top up:

```
coupon_topup_slabs (belongs to one coupon_code)
├── min_amount
├── max_amount     -- nullable = no upper bound
└── bonus_percent

Example — coupon "TOPUP2026":
  ₹0 – ₹100        → 0% bonus
  ₹101 – ₹1,000    → 5% bonus
  ₹1,001 – ₹5,000  → 10% bonus
  ₹5,001+           → 15% bonus

Agent tops up ₹2,000 using this code → falls in the ₹1,001–₹5,000
slab → receives ₹200 bonus → wallet receives ₹2,200 total.
```

### 3.2 Other Configuration Fields (Shared With Subscription Type Where Applicable)

```
usage_limit_per_user
usage_limit_total
expires_at
starts_at
is_active
```

Minimum amount is implicitly handled by the slab structure itself (a slab with `min_amount = 0, bonus_percent = 0` effectively means "no bonus below this amount," so no separate minimum_amount field is needed for this type).

---

## 4. Shared Redemption Rules (Both Types)

```
Code matching: case-insensitive
Usage limits: enforced BEFORE redemption completes — if a user
              has already hit their per-user limit, or the coupon
              has hit its total limit, redemption is rejected with
              a clear reason, no partial application
Expiry/start date: enforced at redemption time, not at creation
              time — a coupon can be created now and scheduled to
              activate later
Stacking between coupon types: an Agent CANNOT apply two coupons
              to the same single transaction (e.g. can't stack a
              subscription discount coupon with a top-up bonus
              coupon on one action, since they apply to different
              action types anyway — this is naturally prevented by
              coupon type matching the action type it's redeemed against)
```

---

## 5. Database Schema

```sql
-- Master coupon record — shared across both types
coupon_codes
├── id
├── code                    -- unique, stored uppercase for
│                              case-insensitive matching
├── type                     -- 'subscription_discount' | 'topup_bonus'
│                              (extensible — Refer & Earn adds
│                              'referral' here later, see section 9)
├── discount_kind             -- 'percentage' | 'flat', nullable
│                              (only used by subscription_discount type)
├── discount_value             -- nullable (subscription_discount type)
├── plan_restriction_id          -- nullable FK to plans
├── usage_limit_per_user
├── usage_limit_total
├── times_used_total             -- running counter
├── starts_at
├── expires_at
├── is_active
├── created_by_admin_id
└── timestamps

-- Only populated for type = 'topup_bonus'
coupon_topup_slabs
├── id
├── coupon_code_id            -- FK coupon_codes
├── min_amount
├── max_amount                 -- nullable
├── bonus_percent
└── timestamps

-- Every redemption event, either type
coupon_redemptions
├── id
├── coupon_code_id
├── user_id                    -- who redeemed it
├── redeemed_for_type           -- 'subscription_payment' | 'topup'
├── redeemed_for_reference_id    -- FK to the payment/subscription/
│                                  wallet_transaction this applied to
├── original_amount               -- amount before discount/bonus
├── discount_or_bonus_amount       -- actual ₹ value applied
├── final_amount                    -- amount after discount/bonus
├── wallet_transaction_id            -- FK, if this resulted in a
│                                      wallet credit (topup_bonus type)
└── redeemed_at
```

---

## 6. Service Layer

```
CouponService (admin-facing)
├── createCoupon(data) — validates type-specific required fields
│     (e.g. subscription_discount requires discount_kind +
│     discount_value; topup_bonus requires at least one slab)
├── updateCoupon(coupon_id, data)
├── deactivateCoupon(coupon_id)
├── deleteCoupon(coupon_id) — blocked if it has existing
│     redemptions (preserve audit history; deactivate instead)
├── getUsageAnalytics(coupon_id) — times used, total ₹ given out,
│     conversion (issued vs redeemed, where applicable)

CouponRedemptionService (Agent-facing)
├── validateCode(code, user_id, action_type, amount)
│     → checks: exists, active, within date range, matches
│       action_type (subscription vs topup), usage limits not
│       exceeded, plan_restriction matches (if subscription type),
│       minimum_amount met
│     → returns the CALCULATED discount/bonus amount without
│       applying it yet (so the UI can show "you'll save ₹X /
│       get ₹X bonus" before final confirmation)
├── redeemForSubscription(code, user_id, subscription_payment_id,
│     original_amount)
│     → applies discount, records coupon_redemptions, integrates
│       with existing subscription purchase flow (the discounted
│       final_amount is what actually gets charged/debited)
├── redeemForTopup(code, user_id, topup_amount)
│     → determines correct slab, calculates bonus, credits wallet
│       via WalletService::credit() with source = 'coupon_topup_bonus',
│       records coupon_redemptions
```

---

## 7. Admin Panel

```
✅ Coupon list — all codes, type, usage stats, active/expired status
✅ Create coupon — type-specific form (subscription_discount shows
   discount kind/value/plan restriction; topup_bonus shows the
   slab table editor)
✅ Edit / Deactivate coupon
✅ Delete coupon — blocked if redemptions exist, deactivate instead
✅ Usage analytics per coupon — redemption count, total ₹ given out,
   list of Agents who redeemed it
✅ Bulk deactivate — select multiple coupons, deactivate at once
```

---

## 8. Agent-Facing UI

```
Subscription purchase confirmation page (per your recent payment
flow redesign): add an optional "Have a coupon code?" input field
→ on entry, calls validateCode() → shows the resulting discounted
  price live, before the Agent confirms payment

Wallet top-up page (/payments/create): add an optional "Have a
coupon code?" input field → on entry + amount entry, calls
validateCode() → shows the resulting bonus amount live
  ("You'll receive ₹2,200 total: ₹2,000 + ₹200 bonus")

Agent's transaction history: coupon redemptions are visible in
their existing wallet ledger (via the wallet_transaction_id link)
and/or payment history, tagged clearly as a coupon-driven amount
```

---

## 9. Designed for Future Refer & Earn — What This Enables Without Building It

This section documents WHY certain design choices were made, so future work can build on this cleanly rather than needing to rework it.

```
✅ coupon_codes.type is a simple string, not a rigid enum constraint
   at the database level — adding 'referral' as a third value later
   requires no migration touching existing rows

✅ coupon_codes.owner_user_id — NOT built in this pass, but the
   table is structured so this column can be added later (a
   referral code is fundamentally a coupon tied to a specific
   Agent as its owner, auto-generated rather than admin-created)

✅ coupon_redemptions already separates "who redeemed" from
   "what type of action it applied to" — a future referral reward
   payout (crediting the REFERRER, not just the redeemer) is a
   natural extension: a second redemption-like record type, or an
   additional field linking back to an owner, without restructuring
   this table

✅ The hold-period + clawback pattern discussed for referral
   safety (protecting against refunds/downgrades reversing an
   already-paid reward) is NOT built in this pass, since regular
   coupon discounts are applied instantly at time of purchase and
   don't carry that same delayed-payout risk. When Refer & Earn is
   built, it will need its own pending/hold state — this is
   correctly scoped as future work, not something this system
   needs to solve now.

✅ WalletService::credit() with a flexible `source` string
   (already true since the Wallet System PRD) means both
   'coupon_topup_bonus' (this system) and a future
   'referral_bonus' source value coexist without any wallet
   schema change.
```

**Nothing above is built in this pass.** It's documented so that when you're ready to build Refer & Earn, the engineer picking that up (whether that's you, an agent, or someone else) understands why the schema looks the way it does and doesn't need to guess at extension points.

---

## 10. What This System Does NOT Do (Out of Scope)

```
❌ Referral code auto-generation per Agent — future system
❌ Referrer reward payout logic — future system
❌ Hold period / clawback mechanism — future system, not needed
   for straightforward coupon redemption
❌ Bulk code generation (e.g. 500 unique codes for a print
   campaign) — not requested for this version, can be added later
   as a simple loop calling createCoupon() repeatedly
❌ Geographic/plan-tier targeting beyond simple plan_restriction —
   not requested for this version
```

---

## 11. Open Items Requiring Confirmation

| Item | Status |
|---|---|
| Exact wording/UI for where the coupon input field appears on the subscription confirmation page (inline vs. expandable "have a code?" link) | ❌ Not decided — recommend a collapsed/expandable link to avoid cluttering the confirmation page for the majority of Agents without a code |
| Whether Admin needs a "campaign" grouping/tag across multiple coupon codes for reporting purposes | ❌ Not decided — not built in this version, straightforward addition later if needed |
| Whether deleting an unused (zero-redemption) coupon should be a hard delete or always soft-deleted for audit consistency with the rest of the platform | ❌ Not decided — recommend soft delete for consistency with Plan Management System's pattern, but confirm |
