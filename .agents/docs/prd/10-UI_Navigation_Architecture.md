# UI Navigation Architecture — Working Mode, User Panel, Admin Panel
**Product:** NBPDCL SaaS Billing Platform
**Purpose:** Organize navigation across all built systems into clean, properly
calibrated menus — separating what an Agent/Admin uses CONSTANTLY (top-level
menu) from what they use OCCASIONALLY (sidebar only, one click deeper).

---

## Design Principle Applied Throughout

```
MENU (top nav / primary tabs) = things touched DAILY or WEEKLY, during
                                  actual meter-reading operational work

SIDEBAR-ONLY (secondary, one extra click) = things touched MONTHLY or
                                  RARELY — account settings, reports
                                  checked periodically, admin config
                                  screens set up once and rarely revisited

Nothing should be in the top menu "just because it exists." A cluttered
top nav with 15 items defeats the purpose of a menu. If in doubt, an
item goes in the sidebar, not the menu.
```

---

## 1. Working Mode — The Daily Operational Workspace

This is what an Agent sees when actually DOING their job — MRU management, cycle processing, review workflow. This is the highest-frequency surface in the entire platform and must be the cleanest.

### 1.1 Top Menu (Working Mode) — Only What's Touched Every Session

```
┌────────────────────────────────────────────────────────────┐
│  [Logo]   MRUs   Processing   Dashboard          🔔  👤▾    │
└────────────────────────────────────────────────────────────┘

MRUs         → List/create/manage MRU workspaces (the starting
                point of all work)
Processing    → Select MRU + cycle → download/extract PDFs
                (the "do the work" screen)
Dashboard      → Card/Table view review workflow (4-box ledger,
                Submitted/Critical/Doubt/Pending, Tag pills) —
                this is where an Agent spends the MOST time
```

**Why only 3 items:** Per your own description of the actual workflow (MRU → Cycle → Processing → Dashboard review), these ARE the entire loop an Agent repeats every single cycle. Nothing else belongs at this level — everything else is either setup-once or occasional-check.

### 1.2 Within Each Section — Secondary Navigation (Tabs, Not Sidebar)

```
MRUs section:
  [All MRUs] [Create New] [Locked MRUs ⚠️]
  → "Locked MRUs" as its own tab, not buried, since an Agent
    needs to notice and act on locked MRUs quickly

Processing section:
  [Select Cycle] → [Download] → [Extract] (step-based, matches
  your actual described workflow, shown as a simple stepper)

Dashboard section:
  [Card View] [Table View]  |  Filters: [Status ▾] [Tag ▾] [MRU ▾]
  → Matches what's already built (card/table toggle + filters)
```

### 1.3 What Does NOT Belong in Working Mode's Menu

```
These exist, but belong in the USER PANEL instead (see section 2),
NOT cluttering the daily operational view:

❌ Wallet balance / top-up
❌ Subscription/plan management
❌ Usage reports (Status & Tag Report, Quota Usage Report)
❌ Referral dashboard
❌ Notification preferences
❌ Payment history

Rationale: an Agent mid-processing-100-consumers does not need
"Refer & Earn" competing for attention in the same nav bar as
their actual meter-reading work. Keep Working Mode singularly
focused on the operational loop.
```

### 1.4 What SHOULD Persist in Working Mode's Header (Always Visible, Small)

```
🔔 Notification bell — CRITICAL alerts (suspension, grace period)
   must be visible even while an Agent is deep in operational
   work, since these directly affect whether they CAN keep working

👤 Account menu (small dropdown, top-right) — quick links to:
   - Wallet balance (shown as a small live number, e.g. "₹850")
   - My Subscription
   - User Panel (full link)
   - Logout

This is the ONE deliberate bridge between Working Mode and the
User Panel — a small, unobtrusive presence, not a full menu item.
```

---

## 2. User Panel — Account, Billing, and Business Management

This is where an Agent goes to manage their ACCOUNT, not their day-to-day meter-reading work. Accessed via the account dropdown from Working Mode, or as its own full section.

### 2.1 Sidebar Structure

```
┌─────────────────────────┐
│  💰 Wallet               │  ← frequent-ish (top-up, check balance)
│  📋 My Subscription      │  ← frequent-ish (renewal, upgrade/downgrade)
│  ─────────────────────   │
│  📊 Reports               │  ← occasional (monthly check-in)
│     ├ Monthly Summary    │
│     ├ Status & Tag       │
│     └ Quota Usage        │
│  🎁 My Referrals          │  ← occasional
│  ─────────────────────   │
│  🔔 Notification Prefs    │  ← rare (set once)
│  💳 Payment History        │  ← rare (occasional lookup)
│  ⚙️  Account Settings       │  ← rare (set once)
└─────────────────────────┘
```

### 2.2 Grouping Logic

```
TOP GROUP (Wallet, Subscription) — these involve MONEY and are
checked reasonably often (before renewal, when balance is low,
when considering a plan change). Kept at the top, ungrouped,
for fastest access.

MIDDLE GROUP (Reports, Referrals) — informational, checked
periodically (monthly summary, referral earnings), not daily.
Grouped together as "things I check sometimes."

BOTTOM GROUP (Notification Prefs, Payment History, Settings) —
configured once or rarely referenced. Correctly de-prioritized
to the bottom, still reachable but not competing for attention.
```

### 2.3 What Happens on Each Page (Brief, Confirming Existing Systems Map Cleanly Here)

```
Wallet               → balance, top-up (routes to the NOW-DEDICATED
                        wallet top-up page per your recent redesign),
                        transaction ledger, CSV export
My Subscription        → current plan, usage vs quota, upgrade/
                        downgrade (routes to the NEW dedicated
                        subscription purchase confirmation page,
                        not the old generic payment form), renewal
                        status/banner if RENEWAL_DUE or GRACE_PERIOD
Reports                 → the 3 Usage Tracking System reports,
                        exactly as built
My Referrals             → code, share link, stats, regenerate button
Notification Preferences  → per-category Email toggle, as built
Payment History            → full payment ledger across all 3 modes
Account Settings             → profile, password change, business
                        details (GSTIN if/when Invoicing System
                        is eventually built)
```

---

## 3. Admin Panel — Full Platform Management

This has grown across 8 systems. Needs the clearest grouping of the three, since Admin genuinely has the most distinct sections to manage.

### 3.1 Sidebar Structure — Grouped by System, Not Flat List

```
┌──────────────────────────────┐
│  📊 Dashboard                  │  ← overview/home
│  ─────────────────────────    │
│  👥 Agents                      │  ← user management
│  ─────────────────────────    │
│  PLANS & BILLING                │  (section header, not clickable)
│    📋 Plans                      │
│    💳 Subscriptions               │
│    💰 Wallets                      │
│    🏦 Payments                      │
│    🎟️  Coupons                       │
│    🎁 Referrals                       │
│  ─────────────────────────    │
│  REPORTS                        │  (section header)
│    📈 Usage & ROI                 │
│    🏷️  Status & Tags                │
│    📊 Quota & Overage              │
│  ─────────────────────────    │
│  SYSTEM                         │  (section header)
│    🏷️  Tag Config                  │
│    ✉️  Notification Templates       │
│    📧 Email Providers                │
│    ⚙️  Platform Settings              │
└──────────────────────────────┘
```

### 3.2 Why This Grouping (Not Alphabetical, Not Flat)

```
PLANS & BILLING group — these are all "money and subscription"
concerns an Admin touches during actual support/operations work
(approving a manual payment, checking a wallet, migrating a plan,
reviewing referral payouts). Grouped because they're used together
in real support scenarios ("Agent says their payment didn't work"
→ Admin checks Payments → Wallet → Subscription, all in one group).

REPORTS group — pure visibility, no action taken here beyond
looking at numbers. Separated from the action-oriented Billing
group intentionally, since the mental mode is different (reviewing
vs. doing).

SYSTEM group — configuration screens set up once, rarely touched
after initial setup (Tag Config, Notification Templates, Email
Providers, Platform Settings). Correctly placed at the bottom,
lowest frequency of access.

Agents (user management) sits alone near the top, since "find and
manage a specific Agent" is probably Admin's single most common
action — searching for a specific person to help.
```

### 3.3 Section Headers Are Non-Clickable Dividers

```
"PLANS & BILLING", "REPORTS", "SYSTEM" are visual group labels,
not links themselves — this avoids the confusion of "what does
clicking PLANS & BILLING even do" and keeps the sidebar scannable
at a glance even with 13 total destination items underneath 3 groups.
```

### 3.4 Top Bar (Persistent, Same as Working Mode Pattern)

```
🔔 Failed Critical Notifications badge — if any critical
   notification failed all 3 retry attempts (per Notification
   System PRD), Admin should see this as an ALWAYS-VISIBLE badge,
   not buried in the sidebar, since it means an Agent may not know
   their account is suspended
```

---

## 4. Cross-Cutting Consistency Rules

```
1. Every sidebar (User Panel, Admin Panel) uses the SAME visual
   pattern: grouped sections with headers, consistent icon style,
   consistent spacing — a User Panel and Admin Panel built at
   different times by different execution prompts should not look
   like two different products glued together.

2. Working Mode's top menu stays MINIMAL (3 items) permanently —
   resist the urge to add a 4th item later just because a new
   feature exists. New features almost always belong in User
   Panel or Admin sidebar, not the operational top menu.

3. Notification bell behavior is IDENTICAL across Working Mode,
   User Panel, and Admin Panel — same component, same dropdown,
   just filtered to relevant events per context. Do not build 3
   different notification UI implementations.

4. Mobile/responsive: Working Mode's 3-item top menu should
   collapse to a bottom tab bar on mobile (common pattern for
   field workers on phones) rather than a hamburger menu, since
   Agents are likely using this in the field — a bottom tab bar
   is faster to tap than opening a hamburger drawer.
```

---

## 5. What This Document Does NOT Cover

```
❌ Visual design (colors, exact spacing, component library choice)
   — that's a separate design pass, this document is purely
   information architecture (what goes where, and why)
❌ Mobile app navigation (if a native app is ever built) — this
   covers the web/PWA interface only
```

---

## 6. Open Items

| Item | Status |
|---|---|
| Whether "Dashboard" (Admin) should show cross-system KPIs (revenue, active Agents, overage trends) as a real home page, or just redirect to Agents list | ❌ Not decided — recommend a real KPI summary page, since Admin needs a "state of the platform" view, but confirm |
| Whether Working Mode's account dropdown should show live wallet balance or just a "Wallet" link | ❌ Not decided — recommend showing the live number, small but visible, since low balance awareness during active work is valuable |
