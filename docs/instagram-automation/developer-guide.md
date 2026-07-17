# Instagram Automation — Developer Guide

## Overview

Yeh module tenants ko allow karta hai ki woh apne connected Instagram Business account
par automatically react kar sakein — jab koi user unke **post par comment** kare ya
unhe **DM** bheje.

Do tarah ke automations hain:

1. **Automations** (`InstagramAutomation`) — rule-based: "agar comment/DM me ye keyword
   ho, to ye action karo" (DM bhejo ya comment ka reply karo).
2. **Chatbot Flows** (`InstagramChatbotFlow`) — sirf DM ke liye, keyword-based
   auto-reply flows, jisme ek "default" flow bhi ho sakta hai (fallback jab koi
   keyword match na ho).

**Poora processing Laravel backend me hota hai — koi n8n / external workflow engine
involved nahi hai.** (Pehle ek optional n8n escape-hatch tha jo 2026-07 me hata diya
gaya, aur usi cleanup me WhatsApp chatbot module se bhi n8n hata diya gaya — is decision
ka reason niche "Why no n8n" section me hai.)

---

## End-to-end Flow

```
Instagram user comments / DMs
            │
            ▼
   Meta sends webhook POST
            │
            ▼
/webhook/instagram  ← InstagramWebhookController::handle()  (PUBLIC, no auth)
            │
            ├─ payload.entry[].messaging[]  → handleDm()
            │        │
            │        ├─ 1. InstagramAutomation (trigger_type = dm_keyword) match?
            │        │       → matchesDm() → executeAutomation()
            │        │
            │        └─ 2. else: InstagramChatbotFlow match (or default flow)
            │                → sendDm()
            │
            └─ payload.entry[].changes[] (field=comments) → handleComment()
                     │
                     └─ InstagramAutomation (trigger_type = any_post_comment
                         OR specific_post_comment) match?
                             → matchesComment() → executeAutomation()

executeAutomation():
    action_type = send_dm       → InstagramService::sendDm()
    action_type = reply_comment → InstagramService::replyToComment()

Every step writes an InstagramLog row (event_type, status, error_message).
```

Har incoming event (chahe match ho ya na ho) `InstagramLog` me log hota hai — is se
tenant apne "Logs" page par dekh sakta hai kya trigger hua, kya skip hua, kya fail hua.

---

## Files & Responsibilities

```
app/
├── Models/
│   ├── InstagramAutomation.php     ← rule model: matchesComment(), matchesDm(), matchKeywords()
│   ├── InstagramChatbotFlow.php    ← DM-only keyword flows + default fallback
│   ├── InstagramSetting.php        ← per-tenant Graph API credentials, forTenant()
│   └── InstagramLog.php            ← audit trail of every event
│
├── Services/
│   └── InstagramService.php        ← saara Graph API communication yahin se hota hai:
│                                       sendDm(), replyToComment(), getAccountInfo(),
│                                       getRecentMedia(), subscribeWebhook(), verifyWebhookToken()
│
└── Http/Controllers/Web/
    ├── InstagramWebhookController.php        ← PUBLIC: Meta se aane wale events
    └── Tenant/InstagramController.php        ← Tenant-facing: settings, automations CRUD,
                                                 chatbot CRUD, OAuth connect, post picker

resources/views/tenant/instagram/
├── index.blade.php                 ← dashboard (stats + recent logs)
├── settings.blade.php              ← QR connect, webhook URL, verify token
├── automations/
│   ├── index.blade.php             ← list
│   ├── create.blade.php            ← create form + post picker gallery
│   └── edit.blade.php              ← edit form + post picker gallery
├── chatbot/index.blade.php         ← chatbot flow CRUD
├── logs.blade.php                  ← filterable event log
└── guide.blade.php                 ← "how it works" help page for tenant
```

---

## Database

### `instagram_automations`

| Column             | Type    | Notes                                                             |
|---------------------|---------|--------------------------------------------------------------------|
| `tenant_id`         | FK      | multi-tenant scope                                                  |
| `name`              | string  | display name                                                        |
| `trigger_type`      | enum    | `any_post_comment` \| `specific_post_comment` \| `dm_keyword`       |
| `post_id`           | string, nullable | Instagram media ID — only used when trigger = specific_post_comment |
| `trigger_keywords`  | json    | array of keywords, empty = match everything                        |
| `keyword_match`     | enum    | `contains` \| `exact` \| `any`                                     |
| `action_type`       | enum    | `send_dm` \| `reply_comment` (`trigger_n8n` value still exists in DB for old rows, no longer offered in UI) |
| `dm_message`        | text, nullable |                                                                 |
| `comment_reply`     | text, nullable |                                                                 |
| `is_active`         | boolean |                                                                      |
| `triggered_count`   | int     | incremented every time this automation fires                       |

### `instagram_settings`

| Column                  | Type    | Notes                                    |
|--------------------------|---------|-------------------------------------------|
| `tenant_id`              | FK, unique |                                          |
| `access_token`           | text, nullable | long-lived Page access token          |
| `instagram_account_id`   | string  | IG Business Account ID                    |
| `page_id`                | string  | linked Facebook Page ID                   |
| `webhook_verify_token`   | string  | random 32-char, used in Meta webhook verify |
| `is_connected`           | boolean |                                            |

---

## Why no n8n

Pehle do jagah n8n involved tha:

1. Automation ka `action_type = trigger_n8n` — ek automation ka action hi n8n webhook
   call karna tha.
2. Instagram Settings me ek tenant-level "n8n Integration" webhook jo **har** Instagram
   event (match ho ya na ho) automatically fire karta tha.

Decision (2026-07-03): comment→auto-DM/reply automation ko fully Laravel backend me
rakhna hai, koi external workflow engine dependency nahi. Dono jagah se code path hata
diya gaya:

- `InstagramController::storeAutomation/updateAutomation` — `action_type` validation ab
  sirf `send_dm,reply_comment` accept karta hai.
- `InstagramController::saveSettings` — `n8n_webhook_url` ab validate/save nahi hota.
- `InstagramWebhookController::executeAutomation()` — n8n branches (per-automation +
  tenant-level auto-fire) dono delete kar diye gaye.
- Views se "Trigger n8n workflow" option aur "n8n Integration" settings card hata diya.

**Update (2026-07-17):** n8n ab WhatsApp chatbot module se bhi puri tarah hata diya gaya
hai — `WhatsappChatbotService::handleIncomingMessage()` se dono n8n trigger blocks
(per-flow aur tenant-level) remove kar diye gaye. `N8nService` class delete kar di gayi
hai (koi call site nahi bacha). `n8n_webhook_url` column ab `whatsapp_settings`,
`whatsapp_chatbot_flows`, `whatsapp_chatbot_sessions`, `instagram_settings`, aur
`instagram_automations` — paanchon tables se migration ke through drop kar diya gaya hai
(`2026_07_17_120000_drop_n8n_webhook_url_columns.php`). `action_type = trigger_n8n` enum
value DB me legacy rows ke liye reh sakta hai, lekin UI/validation kabhi se ise accept
nahi karta.

---

## Post Picker (drag-and-drop post selection)

**Problem jo solve kiya:** `specific_post_comment` trigger ke liye tenant ko pehle
Instagram Post ID **manually paste** karni padti thi (Graph API se ya post URL se nikal
kar). Ab tenant apne actual posts dekh kar select kar sakta hai.

### Backend

```php
// app/Services/InstagramService.php
public function getRecentMedia(int $limit = 25): array
{
    // GET /{instagram_account_id}/media?fields=id,caption,media_type,
    //     media_url,thumbnail_url,permalink,timestamp&limit=25
    // returns [] on failure (logged), else array of post objects
}
```

```php
// app/Http/Controllers/Web/Tenant/InstagramController.php
public function fetchPosts(): JsonResponse
{
    // InstagramService::forTenant($tenantId)->getRecentMedia()
    // returns {success, posts} — tenant-scoped, auth middleware se protected
}
```

Route: `GET /instagram/automations/posts` → name `tenant.instagram.automations.posts`
(registered inside the existing `instagram.` tenant group, so it auto-inherits auth
middleware).

### Frontend (`create.blade.php` / `edit.blade.php`)

Jab tenant trigger type ko "Comment on SPECIFIC post" par switch karta hai:

1. `loadPosts()` ek baar `fetch()` call karta hai `automations.posts` route ko.
2. `renderPostGallery()` response ko thumbnail cards ke grid me render karta hai
   (`#postGallery`).
3. Har card:
   - `draggable="true"` + `ondragstart` → native HTML5 Drag & Drop API (koi npm
     library nahi use ki — project me pehle se koi drag-drop library installed nahi
     thi; existing pattern follow kiya gaya jo already `field-manager/module.blade.php`
     me use ho raha tha).
   - `onclick` → seedha `post_id` input fill kar deta hai. **Yeh primary interaction
     hai** — native HTML5 drag-and-drop touch/mobile devices par kaam nahi karta, isliye
     click hamesha reliable fallback hai; drag sirf desktop ke liye bonus hai.
4. `post_id` `<input>` khud drop-target hai (`ondragover` + `ondrop`) — dropped card ka
   ID usme fill ho jaata hai.
5. Selected card par `.selected` class + checkmark badge dikhta hai. `edit.blade.php` me
   jo automation ka existing `post_id` hai, uska matching card load hone par
   auto-highlight ho jaata hai.
6. Agar tenant ka Instagram account connect nahi hai ya posts fetch fail ho jaaye, to
   gallery ek graceful empty message dikhata hai — manual paste ab bhi kaam karta hai
   (input plain text field hi hai).

---

## Routes

```
# PUBLIC (no auth) — Meta calls these directly
GET  /webhook/instagram        → webhook.instagram.verify  → verify()
POST /webhook/instagram        → webhook.instagram          → handle()

# TENANT (auth required, prefix: /instagram)
GET  /instagram                        → index      (dashboard)
GET  /instagram/settings                → settings
POST /instagram/settings                → saveSettings
POST /instagram/test-connection         → testConnection (AJAX)

GET    /instagram/automations           → automations (list)
GET    /instagram/automations/create    → createAutomation
GET    /instagram/automations/posts     → fetchPosts   (AJAX — post picker)
POST   /instagram/automations           → storeAutomation
GET    /instagram/automations/{id}/edit → editAutomation
PUT    /instagram/automations/{id}      → updateAutomation
POST   /instagram/automations/{id}/toggle → toggleAutomation
DELETE /instagram/automations/{id}      → destroyAutomation

GET    /instagram/chatbot               → chatbot (list)
POST   /instagram/chatbot               → storeChatbotFlow
PUT    /instagram/chatbot/{id}          → updateChatbotFlow
POST   /instagram/chatbot/{id}/toggle   → toggleChatbotFlow
DELETE /instagram/chatbot/{id}          → destroyChatbotFlow

GET  /instagram/logs                    → logs
GET  /instagram/guide                   → guide

GET  /instagram/oauth/qr                → oauthGenerateQr (authenticated — generates QR)
GET  /instagram/oauth/start             → oauthStart      (PUBLIC — phone browser scans QR)
GET  /instagram/oauth/callback          → oauthCallback   (PUBLIC — Meta redirects here)
GET  /instagram/oauth/status            → oauthStatus     (authenticated — polling)
```

---

## Connecting an Instagram Account (OAuth QR flow)

Tenant apne phone se QR scan karke Instagram connect karta hai (desktop browser session
alag hota hai isliye QR + polling approach use hui hai):

```
Tenant clicks "Connect" (desktop)
        │
        ▼
oauthGenerateQr()  → random `state` cache me store hota hai (10 min TTL)
        │             → QR code me /instagram/oauth/start?state=xxx encode hota hai
        ▼
Tenant scans QR (phone browser)
        │
        ▼
oauthStart()  → redirect to Facebook OAuth dialog (scopes: instagram_basic,
                instagram_manage_messages, instagram_manage_comments, etc.)
        │
        ▼
oauthCallback()  → code → access_token → long-lived token exchange →
                   /me/accounts (page list) → page's instagram_business_account
        │
        ▼
InstagramSetting::save()  → access_token, page_id, instagram_account_id, is_connected=true
        │
        ▼
oauthStatus()  polled every few seconds by desktop tab → shows "Connected!" once done
```

---

## Testing Webhooks Locally

```bash
php artisan serve
ngrok http 8000
# → https://abc123.ngrok.io/webhook/instagram
```

Set this URL + the `webhook_verify_token` (visible on the Instagram Settings page) in
Meta App → Webhooks → Instagram, subscribed fields: `messages`, `comments`, `mention`.

---

## Troubleshooting

| Issue | Check |
|-------|-------|
| Comments not triggering automation | Confirm `is_active=true`, `trigger_type` matches, and for `specific_post_comment` that `post_id` exactly matches the media ID Meta sends in `change.value.media.id`. |
| DM automation not firing | `matchesDm()` only runs for `trigger_type = dm_keyword`; check `InstagramAutomation` is checked **before** chatbot flows — first match wins, `return` stops further processing. |
| Post picker gallery empty | Check `instagram_settings.is_connected` and `access_token` validity — `getRecentMedia()` logs failures via `Log::error('Instagram media fetch failed', ...)`. |
| "Trigger n8n workflow" missing after upgrade | Expected — removed intentionally (see "Why no n8n" above). Existing rows with `action_type=trigger_n8n` will just no longer execute anything in `executeAutomation()`; ask the tenant to edit and pick `send_dm` or `reply_comment` instead. |
| Webhook verify fails | `hub_verify_token` query param must exactly match `instagram_settings.webhook_verify_token` for that tenant. |
