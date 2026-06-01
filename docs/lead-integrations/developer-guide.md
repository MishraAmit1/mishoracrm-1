# Lead Integrations — Developer Guide

## Overview

Yeh system tenants ko allow karta hai ki woh apne leads automatically import kar sake
external platforms se jaise Meta Lead Ads, IndiaMART, JustDial etc.

**Super Admin** decide karta hai ki kaun sa tenant kaun sa integration use kar sakta hai.
**Tenant** apne credentials khud configure karta hai.

---

## Architecture

```
External Platform
       │
       ▼
/webhook/leads/{token}     ← LeadWebhookController (public, no auth)
       │
       ▼
TenantIntegration (DB)     ← lookup by webhook_token
       │
       ▼
Platform Service           ← MetaLeadService / JustDialLeadService
       │
       ▼
Lead::create()             ← new lead in CRM
```

For **polling** (IndiaMART):
```
SyncIndiaMartLeadsJob (runs every 30 min)
       │
       ▼
IndiaMartLeadService::sync()
       │
       ▼
Lead::create()
```

---

## Database

### `tenant_integrations` table

| Column          | Type     | Description                                      |
|-----------------|----------|--------------------------------------------------|
| `tenant_id`     | FK       | Which tenant                                     |
| `platform`      | string   | `meta_lead_ads`, `indiamart`, `justdial`, etc.   |
| `is_active`     | boolean  | Tenant ne enable kiya hai ya nahi                |
| `credentials`   | text     | **Encrypted** JSON — API keys, tokens           |
| `settings`      | json     | Non-sensitive config — form IDs, field mappings |
| `webhook_token` | string   | Unique 40-char token — webhook URL ka hissa      |
| `last_synced_at`| datetime | Last successful sync                             |
| `leads_imported`| int      | Total leads imported via this integration        |

### Super Admin Permission Storage

Stored in `Tenant.settings['integrations']` JSON:

```json
{
  "integrations": {
    "meta_lead_ads": true,
    "indiamart": false,
    "justdial": true,
    "tradeindia": false,
    "sulekha": false
  }
}
```

---

## Files & Responsibilities

```
app/
├── Models/
│   └── TenantIntegration.php         ← Model, credentials encryption, platform info
│
├── Services/Integrations/
│   ├── MetaLeadService.php           ← Meta webhook verify + lead fetch via Graph API
│   ├── IndiaMartLeadService.php      ← IndiaMART API polling
│   └── JustDialLeadService.php       ← JustDial webhook processing
│
├── Http/Controllers/Web/
│   ├── LeadWebhookController.php     ← PUBLIC: GET/POST /webhook/leads/{token}
│   ├── Tenant/
│   │   └── LeadIntegrationController.php  ← Tenant configures credentials
│   └── SuperAdmin/
│       └── LeadIntegrationController.php  ← SuperAdmin grants/revokes access
│
└── Jobs/
    └── SyncIndiaMartLeadsJob.php     ← Scheduled: pulls IndiaMART leads every 30 min

resources/views/
├── tenant/lead-integrations/
│   ├── index.blade.php               ← Cards showing all integrations + status
│   └── setup.blade.php               ← Config page per platform
└── superadmin/lead-integrations/
    ├── index.blade.php               ← List of all tenants + their access
    └── edit.blade.php                ← Toggle access per platform per tenant
```

---

## Routes

```
# PUBLIC (no auth, no CSRF)
GET  /webhook/leads/{token}   → LeadWebhookController::verify    (Meta webhook verification)
POST /webhook/leads/{token}   → LeadWebhookController::handle    (incoming lead data)

# SUPER ADMIN
GET  /superadmin/lead-integrations               → index   (all tenants)
GET  /superadmin/lead-integrations/{tenant}/edit → edit    (per tenant)
PUT  /superadmin/lead-integrations/{tenant}      → update  (save access)
POST /superadmin/lead-integrations/{tenant}/toggle → toggle (AJAX quick toggle)

# TENANT
GET  /lead-integrations                          → index   (tenant sees all platforms)
GET  /lead-integrations/{platform}/setup         → setup   (configure a platform)
POST /lead-integrations/{platform}/save          → save    (save credentials)
POST /lead-integrations/{platform}/regenerate    → regenerateToken
POST /lead-integrations/{platform}/sync          → syncNow (IndiaMART manual sync)
GET  /lead-integrations/{platform}/test          → testConnection (AJAX)
```

---

## Running the IndiaMART Scheduler

Add to `bootstrap/app.php` (Laravel 11 scheduler):

```php
->withSchedule(function (Schedule $schedule) {
    // Already existing schedules...

    // IndiaMART sync every 30 minutes
    $schedule->job(new \App\Jobs\SyncIndiaMartLeadsJob)->everyThirtyMinutes();
})
```

Or dispatch for a single tenant:
```php
\App\Jobs\SyncIndiaMartLeadsJob::dispatch($integration);
```

---

## Adding a New Integration Platform

### Step 1 — Register in TenantIntegration::PLATFORMS

```php
// app/Models/TenantIntegration.php

public const PLATFORMS = [
    // ... existing ...
    'new_platform' => [
        'label'  => 'New Platform Name',
        'icon'   => 'fas fa-plug',
        'color'  => '#FF5733',
        'type'   => 'webhook',   // or 'polling'
        'source' => 'new_platform',  // maps to Lead.source
    ],
];
```

### Step 2 — Add Lead source

```php
// app/Models/Lead.php
public static function sources(): array
{
    return [
        // ...
        'new_platform' => 'New Platform Name',
    ];
}
```

### Step 3 — Create Service

```php
// app/Services/Integrations/NewPlatformLeadService.php
class NewPlatformLeadService
{
    public function __construct(private TenantIntegration $integration) {}

    public static function forIntegration(TenantIntegration $integration): self
    {
        return new self($integration);
    }

    public function processWebhook(array $payload): bool
    {
        // Parse $payload, create Lead::create([...])
        return true;
    }
}
```

### Step 4 — Handle in LeadWebhookController

```php
// app/Http/Controllers/Web/LeadWebhookController.php
private function handle(...):
    return match ($integration->platform) {
        // ...
        'new_platform' => $this->handleNewPlatform($request, $integration),
    };

private function handleNewPlatform(Request $request, TenantIntegration $integration): JsonResponse
{
    $service = NewPlatformLeadService::forIntegration($integration);
    $service->processWebhook($request->all());
    return response()->json(['ok' => true]);
}
```

### Step 5 — Add credentials form in setup view

In `resources/views/tenant/lead-integrations/setup.blade.php`, add a new `@elseif($platform === 'new_platform')` block.

---

## Security Notes

1. **Credentials are encrypted** using Laravel's `Crypt::encryptString()` before storing in DB.
2. **Webhook tokens** are 40-char random strings — unique per tenant+platform.
3. **Meta webhooks** verify `X-Hub-Signature-256` header using App Secret.
4. **Duplicate prevention** — each service checks for duplicate leads before creating.
5. **CSRF is disabled** only for `/webhook/leads/*` routes (in `bootstrap/app.php` → CSRF exclusions).

### Add CSRF exclusion for webhook routes

In `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'webhook/*',          // existing
        'webhook/leads/*',    // add this
    ]);
})
```

---

## Testing Webhooks Locally

Use [ngrok](https://ngrok.com) or [Expose](https://beyondco.de/docs/expose/introduction):

```bash
# Start your dev server
php artisan serve

# Expose it publicly
ngrok http 8000
# → gives https://abc123.ngrok.io

# Webhook URL format:
# https://abc123.ngrok.io/webhook/leads/{token}
```

Then set this URL in Meta Facebook App or JustDial vendor panel.

---

## Troubleshooting

| Issue | Check |
|-------|-------|
| Meta leads not coming | Verify `hub_verify_token` matches exactly. Check App subscription to `leadgen` field. |
| IndiaMART API returns 403 | API key may be expired. Regenerate from IndiaMART seller panel. |
| Duplicate leads | Dedup logic checks phone/email in last 24h AND unique IDs (IMID, JDID). |
| Webhook token changed | Tenant clicked "Regenerate URL" — update the webhook URL in platform settings. |
| Leads created with no name | Fallback to platform name (e.g. "Meta Lead"). Check field_data from the platform. |
