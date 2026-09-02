# Subscription Module — Complete Documentation

**Last updated:** 2026-08-29
**Scope:** Platform (SaaS) subscription — how a tenant workspace subscribes to a Plan and pays the platform via Razorpay.
**Status:** Launch-ready. 4 launch-blocker fixes + real free tier shipped 2026-08-29. Recurring auto-billing, GST invoicing and a few hardening items are deferred (see [§8 Pending Work](#8-pending-work)).

> **How to use this doc in future:** share this file (`docs/subscription-module.md`) in a prompt and say which pending item to build. Everything in [§8](#8-pending-work) is written so it can be picked up directly from here.

---

## Table of Contents

1. [Two different "subscription" concepts](#1-two-different-subscription-concepts)
2. [Key files](#2-key-files)
3. [Data model](#3-data-model)
4. [The full flow](#4-the-full-flow)
5. [What was added on 2026-08-29](#5-what-was-added-on-2026-08-29)
6. [Operations & setup](#6-operations--setup)
7. [Testing checklist](#7-testing-checklist)
8. [Pending work (future)](#8-pending-work)
9. [Changelog](#9-changelog)

---

## 1. Two different "subscription" concepts

The codebase has **two** unrelated things both called "subscription". Do not mix them.

| | **Platform Subscription** (this doc) | **Service Subscription** |
|---|---|---|
| Table | `subscriptions` | `service_subscriptions` |
| Model | `App\Models\Subscription` | `App\Models\ServiceSubscription` |
| Meaning | A tenant workspace pays **us** (the SaaS) for a Plan | A tenant's **own customer** subscribes to a service the tenant sells |
| Money flow | Tenant → Platform (Razorpay) | Tenant's customer → Tenant (tracked, invoiced) |
| Gate | `CheckSubscription` middleware locks the whole workspace | `module:subscriptions` feature flag |
| Cron | `subscriptions:check-platform` | `subscriptions:auto-renew`, `subscriptions:remind-expiry` |

Everything below is about the **Platform Subscription**.

---

## 2. Key files

| Area | File |
|---|---|
| Model | `app/Models/Subscription.php` |
| Plan model | `app/Models/Plan.php` |
| Coupon model | `app/Models/Coupon.php` |
| Access gate (middleware) | `app/Http/Middleware/CheckSubscription.php` (alias `subscription`) |
| Tenant billing controller | `app/Http/Controllers/Web/Tenant/SubscriptionController.php` |
| Razorpay webhook | `app/Http/Controllers/Web/SubscriptionWebhookController.php` |
| Razorpay API wrapper | `app/Services/RazorpayService.php` |
| Superadmin plan CRUD | `app/Http/Controllers/Web/SuperAdmin/PlanController.php` |
| Superadmin manual control | `app/Http/Controllers/Web/SuperAdmin/TenantController.php` → `updateSubscription()` |
| Expiry / reminder cron | `app/Console/Commands/CheckPlatformSubscriptions.php` |
| Signup (creates trial / free sub) | `RegisterController.php` (web), `Api/Auth/AuthController.php` (API) |
| Routes | `routes/web.php` (search `subscription`), `routes/console.php` (schedule) |
| Tenant views | `resources/views/tenant/subscription/*` (`plans`, `checkout`, `current`, `success`, `expired`) |
| Superadmin view | `resources/views/superadmin/tenants/show.blade.php` |
| Notification types | `config/notifications.php` |

---

## 3. Data model

### `subscriptions` table

| Column | Notes |
|---|---|
| `tenant_id` | FK, cascade delete |
| `plan_id` | FK to `plans` |
| `coupon_id` | nullable, set when a coupon is applied at checkout |
| `original_amount`, `discount_amount` | rupee amounts, for reporting |
| `razorpay_order_id` | created at checkout |
| `razorpay_payment_id`, `razorpay_signature` | set after successful payment |
| `razorpay_subscription_id` | reserved for future recurring billing — **currently always null** |
| `status` | enum: `trial`, `active`, `cancelled`, `expired`, `past_due`, `pending_payment` |
| `billing_cycle` | `monthly` \| `yearly` |
| `trial_ends_at` | when the free trial ends — length is `plans.trial_days` (0 = no trial) |
| `started_at` | when the current paid term started |
| `ends_at` | when the current term expires (`null` = free plan / no expiry) |
| `cancelled_at` | set when tenant cancels |
| `renewal_reminder_sent_at` | **added 2026-08-29** — de-dupes the "expiring soon" email; reset to `null` on every activation |

`Tenant::subscription()` = `hasOne(Subscription::class)->latestOfMany()` — a tenant can have many rows historically; the latest one is "current".

### Status meanings

| Status | Access? | Meaning |
|---|---|---|
| `trial` | ✅ (until `trial_ends_at`) | free trial (`plans.trial_days`); a paid no-trial plan gets `trial_ends_at = now()` and signup routes to checkout |
| `active` | ✅ (until `ends_at`, or forever if free) | Paid & current, or free plan |
| `pending_payment` | ❌ | Checkout started, payment not completed |
| `past_due` | ❌ | Payment failed |
| `cancelled` | ✅ **until `ends_at`**, then ❌ | Tenant cancelled; keeps access for the paid period |
| `expired` | ❌ | Term ended; set by the daily cron |

### Model helpers (`Subscription.php`)

- `isFree()` — plan's `monthly_price == 0`. Free subs never expire, never lock out.
- `isActive()` — `status === 'active'` **and** (free OR `ends_at` null OR future).
- `isTrial()` — `status === 'trial'` and `trial_ends_at` in the future.
- `isExpired()` — the gate check. Returns `false` for free plans. `cancelled` counts as expired **only after** `ends_at` passes.
- `daysLeft()`, `trialDaysLeft()`, `getMrrAttribute()`.

### `plans` table

Global (no tenant scope). `features` is a JSON map used for module gating and quotas:

```json
{ "leads": 500, "users": 5, "whatsapp": true, "reports": true,
  "service": true, "manufacturing": true, "subscriptions": true, ... }
```

- `-1` = unlimited.
- A `false`/absent key = feature off (superadmin can still force-on per tenant — see `Tenant::hasModuleEnabled()`).
- Seeded plans (`PlanSeeder`): **Free Trial** (₹0), **Starter** (₹999/mo), **Pro** (₹2499/mo).

### Coupons

Applied at checkout via AJAX. `type` = `percentage` \| `flat`, optional `max_discount` cap, `max_uses`, `expires_at`, `applicable_to` (`all` \| `specific_user`). `used_count` increments **only on successful payment** (`verify()`).

---

## 4. The full flow

### 4.1 Diagram

```mermaid
flowchart TD
    A[Tenant signs up] --> B{plans.trial_days}
    B -->|0, ₹0 plan| C[status = active<br/>ends_at = null<br/>no lockout, ever]
    B -->|> 0| D[status = trial<br/>trial_ends_at = +trial_days]
    B -->|0, paid plan| F[status = trial<br/>trial_ends_at = now<br/>redirect to checkout]

    C --> E[Uses workspace]
    D --> E
    F --> E

    E --> F{CheckSubscription<br/>middleware on every<br/>tenant route}
    F -->|isExpired = false| E
    F -->|isExpired = true| G[Redirect to<br/>/subscription/expired]

    E --> H[Tenant opens /subscription/plans]
    G --> H
    H --> I[Picks plan + cycle<br/>ADMIN ONLY]
    I --> J[GET /subscription/checkout/-plan-/-cycle-<br/>creates Razorpay Order +<br/>subscription row status = pending_payment]
    J --> K[Optional: apply coupon AJAX<br/>re-creates Order with discount]
    K --> L[Razorpay Checkout popup<br/>tenant pays]
    L --> M[POST /subscription/verify<br/>signature verified]
    M -->|valid| N[Old subs -> cancelled<br/>this row -> active<br/>renewal_reminder_sent_at = null<br/>coupon used_count++]
    M -->|invalid| O[error -> back to plans]
    N --> P[/subscription/success]
    P --> E

    L -.browser closed.-> Q[Razorpay webhook<br/>payment.captured]
    Q --> R[pending_payment/past_due -> active]

    E --> S[Daily cron 07:30<br/>subscriptions:check-platform]
    S --> T{Each active/trial sub}
    T -->|lapsed| U[status -> expired<br/>notify admins:<br/>subscription.platform_expired]
    T -->|expires within 7 days<br/>& not yet reminded| V[email admins:<br/>subscription.platform_expiring<br/>set renewal_reminder_sent_at]

    E --> W[Tenant clicks Cancel<br/>ADMIN ONLY]
    W --> X[status -> cancelled<br/>cancelled_at = now<br/>access stays until ends_at]
    X --> S
```

### 4.2 Step-by-step

1. **Signup** (`RegisterController::subscriptionWindow()` / API `AuthController`) — driven by `plans.trial_days`:
   - `trial_days > 0` → `status = trial`, `trial_ends_at = ends_at = now + trial_days`.
   - `trial_days = 0` **and ₹0 plan** → `status = active`, `trial_ends_at = null`, `ends_at = null`. Permanent, no lockout.
   - `trial_days = 0` **and paid plan** → `status = trial`, `trial_ends_at = now()`; web signup redirects straight to that plan's checkout.

2. **Daily use** — every tenant route runs through `CheckSubscription`:
   ```php
   if (!$subscription || $subscription->isExpired()) → redirect('tenant.subscription.expired')
   ```
   (JSON requests get HTTP 402.)

3. **View plans** — `/subscription/plans` (any logged-in user can view). `monthly_billing_enabled` platform setting controls whether monthly pricing is shown.

4. **Checkout** — `/subscription/checkout/{plan}/{cycle}` — **`tenant.admin` only**.
   - Computes amount (plan discount applied), creates a Razorpay **Order**, upserts a `pending_payment` subscription row with `ends_at` pre-computed.
   - Renders the Razorpay checkout page.

5. **Coupon (optional)** — `POST /subscription/apply-coupon` (AJAX, admin only) — validates the coupon, recreates the Razorpay Order at the discounted amount, stores `coupon_id` + `discount_amount`.

6. **Payment** — Razorpay popup. On success the browser posts back to:

7. **Verify** — `POST /subscription/verify` (admin only)
   - Verifies the Razorpay signature.
   - In a DB transaction: all `active`/`trial`/`pending_payment` rows → `cancelled`; the matched row → `active`, stores `razorpay_payment_id`, `started_at = now`, `renewal_reminder_sent_at = null`; increments coupon `used_count`.
   - Redirects to `/subscription/success`.

8. **Webhook fallback** — `POST /webhook/razorpay` (no auth, signature-verified, CSRF-exempt)
   - `payment.captured` → `pending_payment`/`past_due` row → `active`.
   - `payment.failed` → `pending_payment` → `past_due`.
   - `subscription.activated` / `subscription.cancelled` → reserved for future recurring billing.

9. **Expiry** — daily cron `subscriptions:check-platform` at 07:30:
   - Any `active`/`trial` sub whose term has passed → `status = expired` + `subscription.platform_expired` notification.
   - Any sub expiring within 7 days (configurable) that hasn't been reminded → `subscription.platform_expiring` email + set `renewal_reminder_sent_at`.
   - Free plans are skipped entirely.

10. **Cancel** — `POST /subscription/cancel` (admin only) → `status = cancelled`, `cancelled_at = now`. Access **continues until `ends_at`**, then the cron expires it.

11. **Re-subscribe** — from `/subscription/plans` or `/subscription/expired`, tenant checks out again → new `pending_payment` row → `active`.

---

## 5. What was added on 2026-08-29

Five changes. For each: **what / why / when / how**.

### 5.1 Platform-subscription expiry & reminder cron  (#2)

- **What:** New command `app/Console/Commands/CheckPlatformSubscriptions.php` (`subscriptions:check-platform`), scheduled daily 07:30 in `routes/console.php`. New column `subscriptions.renewal_reminder_sent_at` (migration `2026_08_29_120000_...`). New notification types `subscription.platform_expiring` and `subscription.platform_expired` in `config/notifications.php`.
- **Why:** Before this, an expired workspace was still `status = active` in the DB — only `isExpired()` computed it at read-time. So: (a) tenants got **zero warning** before lockout, (b) superadmin dashboards counted stale "active" subs and wrong MRR.
- **When it runs:** automatically every day at 07:30 (needs the Laravel scheduler cron — see [§6](#6-operations--setup)). Can also be run manually any time.
- **How to use:**
  ```bash
  php artisan subscriptions:check-platform                 # default: warn 7 days ahead
  php artisan subscriptions:check-platform --reminder-days=14
  ```
  Output: `Platform subscriptions — expired: N, reminded: M.`
  Tenant admins receive in-app + email notifications; reminders link to `/subscription/plans`.

### 5.2 Billing routes are admin-only  (#4)

- **What:** `checkout`, `verify`, `cancel`, `apply-coupon` routes wrapped in the `tenant.admin` middleware group in `routes/web.php`. View-only routes (`plans`, `current`, `expired`, `success`) stay open to all staff.
- **Why:** Previously **any** staff user could purchase or **cancel the whole company's subscription**. Billing is an owner/admin action.
- **When:** enforced on every request to those routes. A non-admin hitting them gets `403`.
- **How to use / extend:** if you add a new billing action route, put it inside the same `Route::middleware('tenant.admin')->group(...)` block (`routes/web.php`, ~line 255).

### 5.3 Cancel no longer locks the tenant out immediately  (#3)

- **What:** `Subscription::isExpired()` now treats `cancelled` as expired **only after `ends_at` has passed**. Added a "cancelled — access until DATE" banner to `resources/views/tenant/subscription/current.blade.php`.
- **Why:** The old code returned `true` for any `cancelled` sub, so a tenant who paid for a full year and then cancelled was locked out **the same second** — contradicting the on-screen message ("you can use until …").
- **When:** takes effect immediately on cancel; the daily cron finishes the job when `ends_at` passes.
- **How to use:** nothing to do — `cancel()` already sets `cancelled_at`. The cron handles final expiry.

### 5.4 Real free tier  (#5)

- **What:**
  - `Subscription::isFree()` helper — plan `monthly_price == 0`.
  - `isExpired()` returns `false` for free subs; `isActive()` returns `true` with a null `ends_at`.
  - Signup (web + API) creates free-plan subs as `status = active`, `trial_ends_at = null`, `ends_at = null`.
  - "You're on the free plan — no expiry" banner in `current.blade.php`; the Cancel button and "Days Remaining" row are hidden for free.
- **Why:** The "Free Trial" plan (₹0) used to get a 14-day `ends_at` and then lock out — and the `/expired` page only lists **paid** plans. So there was effectively **no free tier** despite one existing.
- **When:** applies to every new free-plan signup. Existing stuck free rows are also covered because `isExpired()` now short-circuits on `isFree()`.
- **How to use:** to make a plan behave as free, set its `monthly_price` to `0` in the superadmin Plans screen. To convert an existing tenant to free, use the superadmin manual control (below) — pick the free plan, status `active`, clear both date fields.

### 5.5 Superadmin manual subscription control  (#12)

- **What:** `TenantController::updateSubscription()` + route `superadmin.tenants.update-subscription` + a "Manage Subscription" form card on the tenant detail page (`superadmin/tenants/show.blade.php`).
- **Why:** Support had **no way** to extend a trial, move a tenant between plans, comp a plan (partnership / goodwill), or fix a stuck payment without editing the database by hand.
- **When to use:**
  - Customer paid out-of-band (bank transfer) → set plan + status `active` + `ends_at`.
  - Extend a trial → bump `trial_ends_at`.
  - Comp Pro for a partner → pick Pro, status `active`, set `ends_at` (or leave blank for open-ended).
  - Downgrade / move to free → pick free plan, status `active`, clear dates.
  - Wrongly-expired tenant → status `active` + correct `ends_at`.
- **How to use:** Superadmin → Tenants → open a tenant → **Manage Subscription** card → set Plan / Status / Billing cycle / Expires on / Trial ends on / Note → **Save**. Every change writes an `AuditLog` row (`action = subscription_updated`) including the note. `renewal_reminder_sent_at` is auto-reset so the "expiring soon" email can fire again for the new term.

---

## 6. Operations & setup

### 6.1 Environment variables (`.env`)

```
RAZORPAY_KEY_ID=rzp_live_xxxxxxxx
RAZORPAY_KEY_SECRET=xxxxxxxx
RAZORPAY_WEBHOOK_SECRET=xxxxxxxx
```
(Config: `config/services.php` → `razorpay`.)

### 6.2 Razorpay dashboard

- Create a **Webhook** pointing at `https://<your-domain>/webhook/razorpay` with the same secret as `RAZORPAY_WEBHOOK_SECRET`.
- Subscribe to events: `payment.captured`, `payment.failed`. (`subscription.*` events are for the future recurring-billing work.)

### 6.3 Laravel scheduler (required for expiry & reminders)

One system cron entry runs all scheduled commands:
```
* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1
```
Verify our command is registered:
```bash
php artisan schedule:list | grep check-platform
# 30 7 * * *  php artisan subscriptions:check-platform
```

### 6.4 Plans

Superadmin → Plans. Set `monthly_price = 0` for the free tier. `features` JSON drives module access and quotas. "Monthly billing enabled" toggle controls whether the public pricing page shows monthly prices.

### 6.5 Migration

```bash
php artisan migrate     # applies 2026_08_29_120000_add_renewal_reminder_sent_at_to_subscriptions_table
```

---

## 7. Testing checklist

- [ ] New signup on a **0-day / ₹0** plan → can use workspace indefinitely, no "trial ends" banner, `/subscription/current` shows "No expiry".
- [ ] New signup on a plan with **trial_days > 0** → trial for that many days, banner counts down.
- [ ] New signup on a **paid, 0-day** plan → lands on that plan's checkout page.
- [ ] Trial lapses (set `trial_ends_at` in the past) → next request redirects to `/subscription/expired`.
- [ ] Run `php artisan subscriptions:check-platform` → lapsed sub becomes `expired`, admin gets notification.
- [ ] Sub expiring in 5 days → command emails admin once; second run does **not** re-email.
- [ ] Checkout as a **non-admin** staff user → `403`.
- [ ] Full paid checkout in Razorpay test mode → `verify` activates, old sub cancelled, `/success` shows.
- [ ] Apply a valid coupon at checkout → amount drops, `used_count` increments only after payment.
- [ ] Cancel an active paid sub → still has access, banner shows "access until DATE", cron expires it after `ends_at`.
- [ ] Superadmin **Manage Subscription** → change plan / extend date → reflected on tenant side + `AuditLog` row created.
- [ ] Razorpay webhook with a bad signature → `400`, logged.

---

## 8. Pending work

Not done yet. Ordered by priority. Each item is self-contained — share this doc and name the item to have it built.

### 8.1 Recurring auto-billing  (priority: high, effort: large)

- **Problem:** Only Razorpay **Orders** (one-time) are used. `razorpay_subscription_id` and the `subscription.activated/cancelled` webhook handlers exist but nothing creates a Razorpay Subscription. Every renewal is a manual re-purchase by the tenant → churn.
- **Build:** Razorpay **Subscriptions API** with a mandate (UPI AutoPay / e-NACH / card). Store `razorpay_subscription_id`. On `subscription.charged` webhook, extend `ends_at` and record the payment. Add dunning (retry + notify) on `subscription.pending`/`halted`.
- **Touches:** `RazorpayService` (add `createSubscription`, `cancelSubscription`), `SubscriptionWebhookController` (flesh out `subscription.*`), `SubscriptionController::checkout` (mandate flow), `Plan` (`razorpay_monthly_plan_id` / `razorpay_yearly_plan_id` already exist — wire them up).

### 8.2 GST tax invoice / payment receipt  (priority: high if GST-registered, effort: medium)

- **Problem:** No downloadable invoice/receipt for platform payments. Only `razorpay_payment_id` is stored. India law requires a GST tax invoice per payment.
- **Build:** `platform_invoices` table (number, tenant, subscription, base amount, GST %, CGST/SGST/IGST split by tenant state, total, PDF path). Generate on `verify()` + webhook activation. Download link on `/subscription/current` and superadmin tenant page. Reuse the existing invoice-PDF tooling.

### 8.3 Webhook / verify consistency + idempotency  (priority: medium, effort: medium)

- **Problem:** `onPaymentCaptured` doesn't cancel old subs, doesn't set `ends_at`, doesn't bump coupon `used_count` — so browser-closed-after-payment leaves two `active` rows. No idempotency: Razorpay retries the same event.
- **Build:** Extract the "activate this order" logic into one method called by **both** `verify()` and the webhook. Add a `processed_at` / event-id guard (or a `webhook_events` table) so repeats are no-ops.

### 8.4 `verify()` edge case — paid customer locked out  (priority: medium, effort: small)

- **Problem:** `verify()` cancels **all** active/trial/pending subs, then activates the row matching `razorpay_order_id`. If that row isn't found, the tenant is left with **nothing active** despite paying.
- **Build:** Look up + validate the target row **before** cancelling anything; if not found, fetch the payment from Razorpay by `order_id` and reconstruct, or abort without cancelling. Log loudly.

### 8.5 Coupon hardening  (priority: medium — only if running campaigns, effort: small–medium)

- Race condition: concurrent redemptions can exceed `max_uses` (check-then-increment gap). → atomic conditional increment, or a `coupon_redemptions` table with a unique constraint.
- No tenant scoping: `Coupon.tenant_id` exists but `isValid()` ignores it — Tenant A's coupon works for Tenant B. → add the check.
- No rate limit on `apply-coupon` → coupon-code brute force. → `throttle` middleware.

### 8.6 `checkout` is a GET with side effects  (priority: low, effort: small)

- **Problem:** `GET /subscription/checkout/...` creates a DB row **and** a Razorpay Order. Prefetch / crawler / refresh creates junk orders.
- **Build:** Split — GET renders a confirm page; `POST` creates the Order + row.

### 8.7 Proration on upgrade/downgrade  (priority: low, effort: medium)

- **Problem:** Mid-cycle plan change forfeits the remaining paid days and money; new `ends_at = now + 1 cycle`.
- **Build:** Credit unused days of the old plan against the new plan's price at checkout; show the prorated amount.

### 8.8 Minor items  (priority: low)

- `resources/views/components/layouts/navbar.blade.php:187` — `$user->tenant->subscription->status` has no null guard → page crashes if a tenant somehow has no subscription. Add `?->`.
- `resources/views/components/sidebar.blade.php` — `now()->diffInDays($trial_ends_at, false)` returns a float in Carbon 3 → shows "13.6 days left". Cast to `int`.
- Prices are cast `decimal:2` but `checkout()` casts to `(int)` → can't charge ₹499.50. Use paise/float end-to-end if fractional pricing is ever needed.
- `PlanController::destroy` blocks deletion when there are **active** subs but not **trial** subs → a plan with trial users can be deleted.
- `payment.failed` only sets `status = past_due` — no dunning email, no retry.
- Currency hard-coded `INR` throughout (fine while India-only).

---

## 9. Changelog

| Date | Change |
|---|---|
| 2026-08-29 | **Audit + 4 launch blockers + free tier.** Added `subscriptions:check-platform` cron (expiry + 7-day reminder), `renewal_reminder_sent_at` column, `platform_expiring`/`platform_expired` notifications. Billing routes (`checkout`/`verify`/`cancel`/`apply-coupon`) restricted to `tenant.admin`. `isExpired()` fixed so `cancelled` keeps access until `ends_at`. Real free tier: `isFree()`, free signups created as permanent `active`. Superadmin `updateSubscription()` manual control with audit logging. |
| (earlier) | Base module: Plan CRUD, trial on signup, Razorpay Order checkout, coupon support, `CheckSubscription` gate, webhook for `payment.captured`/`failed`. |
