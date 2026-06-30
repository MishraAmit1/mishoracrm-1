# AI & Workflow Automation Feature

**Added:** 2026-06-27  
**Purpose:** Tenants ko AI/n8n automation workflows showcase karna aur unse custom workflow requests lena — ek built-in upsell/lead generation mechanism.

---

## Overview

Is feature ka main goal yeh hai ki jo client CRM use kar raha hai, use dikhao ki unke business ke liye kaunse AI automations possible hain. Agar use pasand aaye, to wo "Request" kar sakta hai — aur super admin ko notification milti hai ki ek potential custom automation client hai.

```
Super Admin → Templates banao (n8n workflows showcase)
     ↓
Tenant → AI Automation page dekhta hai
     ↓
Tenant → "Request This" ya "Request Custom Workflow" click karta hai
     ↓
Super Admin → Request dekh ke contact karta hai → custom workflow build karta hai
```

---

## Database Tables

### `workflow_templates`
Super admin yahan templates create karta hai jo tenants ko dikhte hain.

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `title` | string | Template name (e.g. "Lead Auto-Qualifier AI") |
| `description` | text | Kya karta hai yeh workflow |
| `category` | string | lead / whatsapp / invoice / crm / hr / email / custom |
| `suitable_for` | string (nullable) | Konse business ke liye (e.g. "Real Estate, EdTech") |
| `features` | json | Bullet points (array of strings) |
| `icon_type` | string | Card icon: zap / chat / invoice / users / bell / star / ai |
| `color` | string | Card color: blue / purple / green / orange / pink / teal / yellow |
| `is_active` | boolean | Active templates hi tenants ko dikhte hain |
| `sort_order` | integer | Display order (ascending) |

### `workflow_requests`
Tenant ke requests yahan store hoti hain.

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Primary key |
| `tenant_id` | FK → tenants | Konse tenant ne request ki |
| `user_id` | FK → users | Konse user ne submit kiya |
| `workflow_template_id` | FK → workflow_templates (nullable) | Agar specific template request ki ho; null = custom |
| `business_type` | string | Tenant ka business type (user ne fill kiya) |
| `problem_description` | text | Kya automate karna chahte hain |
| `contact_preference` | enum: whatsapp / email | Preferred contact method |
| `contact_value` | string | Phone number ya email |
| `status` | enum | new / in_progress / completed / rejected |
| `admin_notes` | text (nullable) | Super admin ke internal notes |

---

## Models

### `App\Models\WorkflowTemplate`
- **File:** `app/Models/WorkflowTemplate.php`
- `features` column auto-cast to array
- `scopeActive()` — only active templates, sorted by `sort_order`

### `App\Models\WorkflowRequest`
- **File:** `app/Models/WorkflowRequest.php`
- Relations: `tenant()`, `user()`, `template()`
- `scopeNew()` — status = 'new' wali requests

---

## Controllers

### SuperAdmin

| Controller | File | Routes |
|---|---|---|
| `WorkflowTemplateController` | `app/Http/Controllers/Web/SuperAdmin/WorkflowTemplateController.php` | CRUD + toggle active |
| `WorkflowRequestController` | `app/Http/Controllers/Web/SuperAdmin/WorkflowRequestController.php` | List, show, update status |

### Tenant

| Controller | File | Routes |
|---|---|---|
| `AutomationController` | `app/Http/Controllers/Web/Tenant/AutomationController.php` | index (page), request (form submit via AJAX) |

---

## Routes

### SuperAdmin Routes (prefix: `/superadmin`)

```
GET    /superadmin/workflow-templates              → Templates list
GET    /superadmin/workflow-templates/create       → Create form
POST   /superadmin/workflow-templates              → Store
GET    /superadmin/workflow-templates/{id}/edit    → Edit form
PUT    /superadmin/workflow-templates/{id}         → Update
DELETE /superadmin/workflow-templates/{id}         → Delete
POST   /superadmin/workflow-templates/{id}/toggle  → Toggle active/inactive

GET    /superadmin/workflow-requests               → Requests list (filter by status)
GET    /superadmin/workflow-requests/{id}          → Request detail + status update form
PATCH  /superadmin/workflow-requests/{id}/status   → Update status + admin notes
```

### Tenant Routes

```
GET    /automation          → AI Automation page (templates + my requests)
POST   /automation/request  → Submit request (AJAX/JSON)
```

---

## Views

```
resources/views/
├── tenant/
│   └── automation/
│       └── index.blade.php          ← Tenant ka main page
└── superadmin/
    ├── workflow-templates/
    │   ├── index.blade.php          ← Templates list
    │   ├── create.blade.php         ← Create form
    │   └── edit.blade.php           ← Edit form
    └── workflow-requests/
        ├── index.blade.php          ← Requests list with filter
        └── show.blade.php           ← Request detail + status update
```

---

## Sidebar

- **Tenant sidebar:** "Automation" section → "AI Automation" link
- **SuperAdmin sidebar:** "Automation" section → "Templates" + "Requests" links  
  Requests link pe red badge dikhta hai agar koi `new` request ho

---

## Tenant Page Flow

1. User `/automation` page open karta hai
2. Template cards dikhte hain (super admin ne banaye hue)
3. Har card pe "Request This" button hota hai
4. Button click → modal open hota hai (pre-filled template name)
5. User form bharta hai: business type, problem description, contact
6. AJAX POST `/automation/request` → JSON response
7. Success → modal band, flash message, page reload
8. "My Requests" table bottom pe — submitted requests ka status dikhta hai

---

## Adding a New Template (Super Admin)

1. Login as super admin → Sidebar → Automation → Templates
2. "Add Template" click karo
3. Fill:
   - **Title:** Workflow ka naam
   - **Description:** Kya karta hai (1-2 lines)
   - **Category:** lead / whatsapp / invoice etc.
   - **Suitable For:** Target industry (optional)
   - **Features:** 4-6 bullet points
   - **Icon Type:** Card pe konsa icon
   - **Color:** Card ka accent color
   - **Sort Order:** 0 = pehle dikhega
4. "Active" checkbox checked rakho → tenant ko dikhe

---

## Status Workflow (Requests)

```
new → in_progress → completed
                  → rejected
```

Super admin request open kare → right side panel se status change kare → admin notes likhe (internal use).

---

## Key Design Decisions

- **No per-tenant n8n config** — Is feature me sirf request capture hota hai. Actual n8n integration alag feature hai.
- **AJAX form submit** — Modal band hone ka UX better rahta hai vs full page reload on submit.
- **Template-less requests** — `workflow_template_id` nullable hai — "Request Custom Workflow" button se bhi request aa sakti hai bina kisi template ke.
- **Sidebar badge** — Super admin ko pata chale bina refresh ke ki naye requests hain.
