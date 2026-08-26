# Service & Manufacturing Company — Gaps Tracker

Yeh file un gaps ko track karti hai jo service-based aur manufacturing-based
tenants ke liye is CRM mein identify hue the (2026-08-26 ko). Jo already
build ho chuke hain unko "Done" maaka gaya hai, baaki "Pending" hain — jab
bhi kaam start karna ho, yeh file share kar dena.

## ✅ Done (2026-08-26)

### 1. Subscription auto-renewal + auto-invoice
Pehle sirf expiry reminder jaata tha (`subscriptions:remind-expiry`), renewal
manual tha — staff ko "Renew" button dabana padta tha.

- `service_subscriptions` table mein `auto_renew` boolean column add kiya.
- `App\Services\ServiceSubscriptionService::renew()` — shared renewal logic
  (extend expiry + draft invoice create), controller ka manual Renew/Bulk
  Renew aur naya command dono isi ko use karte hain.
- Naya command: `subscriptions:auto-renew` — daily 06:00 AM pe chalta hai
  (`routes/console.php`), auto-renew=true aur expiry pahunch chuki
  subscriptions ko renew karke draft invoice banata hai, tenant admins ko
  notify karta hai (`subscription.auto_renewed` notification type,
  `config/notifications.php`).
- UI: Create/Edit Subscription form mein "Auto-renew" checkbox, list page pe
  "⟳ Auto" badge.

**Files touched:** `app/Models/ServiceSubscription.php`,
`app/Services/ServiceSubscriptionService.php` (new),
`app/Console/Commands/AutoRenewServiceSubscriptions.php` (new),
`app/Http/Controllers/Web/Tenant/ServiceSubscriptionController.php`,
`resources/views/tenant/subscriptions/{create,edit,index}.blade.php`,
`config/notifications.php`, `routes/console.php`,
`database/migrations/2026_08_26_100000_add_auto_renew_to_service_subscriptions_table.php`

### 2. Work Order — assigned worker/technician
Work Order mein sirf flat `labor_cost` tha, koi track nahi tha ki kaunsa
staff member us production job pe laga tha (Appointment module mein
`assigned_to` tha, WorkOrder mein nahi).

- `work_orders` table mein `assigned_to` (nullable FK → users) column add
  kiya.
- Model relation `WorkOrder::assignedTo()`.
- Controller mein `staffList()` helper (Appointment module jaisa pattern),
  Create/Edit form mein technician dropdown, Show page pe "Assigned To",
  Index list mein "Assigned To" column.

**Files touched:** `app/Models/WorkOrder.php`,
`app/Http/Controllers/Web/Tenant/WorkOrderController.php`,
`app/Http/Requests/WorkOrderRequest.php`,
`resources/views/tenant/work-orders/{create,edit,show,index}.blade.php`,
`database/migrations/2026_08_26_100001_add_assigned_to_to_work_orders_table.php`

---

## ⏳ Pending — Manufacturing

Priority order (highest value/effort ratio pehle):

1. **Multi-level BOM (sub-assemblies)** — abhi BOM ek hi level explode
   karta hai. Agar koi raw material khud ek manufactured item hai (apna BOM
   rakhta ho), woh recursively handle nahi hota.
2. **Work order routing/stages** — cutting → assembly → QC jaisa
   multi-step production process track nahi hota, sirf ek single status
   (pending/in_progress/completed) hai.
3. **Multi-warehouse/location stock tracking** — stock sirf ek global
   number per product hai (`current_stock`), alag godown/location ke liye
   nahi.
4. **MRP-style aggregation** — `StockService::suggestedMaterialsFor()` ek
   product ke liye kaam karta hai; multiple pending work orders ke across
   combined material requirement plan nahi banta.
5. **Scrap/rejection/wastage tracking** in production.
6. **Serial number tracking** — sirf batch number (FEFO) hai, high-value
   items ke liye serial-level traceability nahi.

## ⏳ Pending — Service

1. **AMC/contract-level structure** — recurring service contracts
   ServiceSubscription se hi handle ho rahe hain; ek dedicated
   contract/SLA layer (multiple services ek contract ke under) nahi hai.
2. **Job-card/checklist per service visit** — Appointment/WorkOrder level
   pe koi checklist ya parts-used-on-visit tracking nahi.
3. **Technician scheduling/route optimization** — sirf ek `assigned_to`
   field hai (ab WorkOrder mein bhi), calendar-load balancing ya
   multi-technician dispatch view nahi hai.

---

*Note: yeh sab speculative/future items hain — sirf tab build karna jab
koi actual tenant demand kare, jaisi pehle discuss hui thi.*
