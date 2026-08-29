# Service & Manufacturing Company — Gaps Tracker

Yeh file un gaps ko track karti hai jo service-based aur manufacturing-based
tenants ke liye is CRM mein identify hue the (2026-08-26 ko). Jo already
build ho chuke hain unko "Done" maaka gaya hai, baaki "Pending" hain — jab
bhi kaam start karna ho, yeh file share kar dena.

## ✅ Done (2026-08-29)

### 9. Proper GST — CGST/SGST/IGST split
Pehle ek flat `tax_percent` + `tax_amount` tha. Ab har tax-bearing doc
(Quotation, Invoice, Purchase Order, Vendor Bill) pe:

- `place_of_supply` (recipient state code), `is_inter_state`, `cgst_amount`,
  `sgst_amount`, `igst_amount` columns (`tax_amount` = total = cgst+sgst+igst).
- `App\Services\GstService` — `breakdown($taxAmount, $supplierState, $recipientState)`:
  same state = CGST+SGST (aadha-aadha), alag state = full IGST. `stateCode()`
  code / naam / GSTIN (first 2 digits) — teeno se resolve. Dono unknown → intra
  (chhote single-state business ka common case), aur breakdown surface hota hai
  taaki user correct kar sake.
- **Sales** (Quotation/Invoice): supplier = tenant company state
  (`settings['state']`), recipient = contact state / GSTIN.
  **Purchase** (PO/Bill): supplier = vendor state, recipient = tenant state.
- Compute: QuotationService, InvoiceController, PurchaseOrderService,
  VendorBillService, VendorQuoteService::select, Quotation→Invoice convert,
  new-version clone — sab wire hue.
- `App\HasGstBreakdown` trait → `gstLines()` (CGST/SGST | IGST | fallback GST),
  `placeOfSupplyName()`. Show + PDF (Invoice/Quotation/PO/Bill) is trait use
  karte hain.
- Vendor form ka state ab dropdown (`config('crm.states')`). Migration existing
  `vendors`/`contacts` ka free-text state ("Karnataka") ko code ("KA") normalize
  karta hai. Contact form dropdown NAHI (complex JS form) — state text ya GSTIN
  se resolve hota hai.
- Tests: `tests/Feature/Tenant/GstBreakdownTest.php`

**Files:** `app/Services/GstService.php` (new), `app/HasGstBreakdown.php` (new),
`app/Models/{Quotation,Invoice,PurchaseOrder,VendorBill,Tenant}.php`,
`app/Services/{QuotationService,PurchaseOrderService,VendorBillService,VendorQuoteService}.php`,
`app/Http/Controllers/Web/Tenant/InvoiceController.php`,
`resources/views/tenant/{invoices,quotations,purchase-orders,vendor-bills}/{show,pdf}.blade.php`,
`resources/views/tenant/vendors/_form.blade.php`,
`database/migrations/2026_08_29_200000_add_gst_breakdown_columns.php`

### 2. Production routing / stages
WO ke andar multi-step stages (Cutting → Welding → QC → Packing).

- `work_order_stages` table (sequence, name, assigned_to, status
  pending/in_progress/done/skipped, started/completed at).
- WO create/edit form pe stage rows editor (`_stages-editor` partial). Blank
  rows drop; sequence auto.
- Stages sirf tab actionable jab WO in_progress (materials issued). Start/
  Complete/Skip/Reopen per stage. WO complete BLOCKED jab tak koi stage
  pending/in_progress ho (`WorkOrder::allStagesFinished()`).
- Stage-less WO exactly pehle jaisa (single start→complete).
- `WorkOrderService::{startStage,completeStage,skipStage,reopenStage}`,
  controller `stageAction`, route `work-orders/{id}/stages/{stageId}`.
- Tests: `tests/Feature/Tenant/WorkOrderStagesTest.php`

**Files:** `app/Models/{WorkOrder,WorkOrderStage}.php`,
`app/Services/WorkOrderService.php`, `app/Http/{Controllers/Web/Tenant/WorkOrderController,Requests/WorkOrderRequest}.php`,
`resources/views/tenant/work-orders/{create,edit,show,_stages-editor}.blade.php`,
`routes/web.php`, `database/migrations/2026_08_29_180000_*`

### 5. Scrap / yield tracking
WO complete pe actual `produced_quantity` (good output) + `scrap_quantity` +
`scrap_reason`. `quantity` = planned run (material consumption drive karta hai);
sirf produced qty finished-good stock mein credit hoti hai. Poore run ka
material+labor+machine cost good units absorb karte hain — scrap se cost/unit
badhta hai. Total scrap (0 produced) bhi complete hota hai (materials still
consumed). WorkOrder cost accessors ab `yieldQuantity()` use karte hain.

**Files:** `app/Models/WorkOrder.php`, `app/Services/{WorkOrderService,StockService}.php`,
`app/Http/Controllers/Web/Tenant/WorkOrderController.php`,
`resources/views/tenant/work-orders/show.blade.php`,
`database/migrations/2026_08_29_190000_add_scrap_fields_to_work_orders_table.php`
(tests in `WorkOrderWipTest`)

### 6. Work Order material reservation + WIP (issue-at-start)
Pehle WO create/start pe kuch nahi hota tha, material sirf `complete()` pe
consume hota tha (all-or-nothing). Do pending WO dono feasible dikhte the,
phir ek stock kha leta, doosra fail.

- `products.reserved_stock` column + `Product::reserve()/releaseReservation()/
  availableStock()` (atomic, `available = current_stock - reserved_stock`).
- `work_orders.materials_issued_at` timestamp.
- **WO create** → `StockService::reserveForProduction()` BOM materials earmark
  karta hai (physical stock nahi hilta). **Edit (pending)** → reservation
  re-point. **Start Production** → `issueForProduction()` — reservation release
  + FEFO consume, `materials_issued_at` set, material cost yahin freeze hota
  hai (pehle complete pe hota tha). **Complete** → agar issued: sirf finished
  good credit; agar nahi (legacy straight-to-complete): purana behaviour
  (shortfall check + consume + credit). **Cancel** → issued ho to material
  wapas stock mein (`returnIssuedMaterials`), warna sirf reservation release.
  **Delete (pending)** → `releaseOnDelete()`.
- WO show: "Available" column + "Materials Issued" timestamp; shortfall check
  issued WO pe skip.
- Tests: `tests/Feature/Tenant/WorkOrderWipTest.php`

**Files:** `app/Models/{Product,WorkOrder}.php`, `app/Services/{WorkOrderService,StockService}.php`,
`app/Http/Controllers/Web/Tenant/WorkOrderController.php`,
`resources/views/tenant/work-orders/show.blade.php`,
`database/migrations/2026_08_29_170000_*`, `2026_08_29_170100_*`

### 5. GRN + Quality Inspection (accept / reject on receipt)
Pehle PO receive karte hi poori qty stock mein chali jaati thi. Ab:

- `goods_receipt_notes` table (GRN-YYYYMMDD-0001, items json with received/
  accepted/rejected/reason/batch/expiry per line).
- PO receive ab **event-based** (har delivery = 1 GRN, incremental qty — pehle
  cumulative set-model tha). `GoodsReceiptService::record()` — sirf **accepted**
  qty stock + weighted-average cost mein jaati hai; rejected qty GRN pe aur PO
  line ke `rejected_quantity` pe accumulate hoti hai (vendor follow-up / debit
  note ke liye — auto stock movement nahi).
- PO line ka `received_quantity` ab cumulative ACCEPTED hai; status usi se
  derive. `PurchaseOrderService::receive()` + `deriveReceivedStatus()` hata diye
  (GoodsReceiptService ne replace kiya).
- PO show: "Goods Receipt (GRN)" form (Received Now / Accepted / Reject Reason
  columns) + "Goods Receipts" list; GRN detail page.
- Tests: `tests/Feature/Tenant/GoodsReceiptTest.php`

**Files:** `app/Models/{GoodsReceiptNote,PurchaseOrder}.php`,
`app/Services/{GoodsReceiptService,PurchaseOrderService}.php`,
`app/Http/{Controllers/Web/Tenant/PurchaseOrderController,Requests/PurchaseOrderReceiveRequest}.php`,
`resources/views/tenant/purchase-orders/{show,grn}.blade.php`, `routes/web.php`,
`database/migrations/2026_08_29_160000_*`

### 4. Accounts Payable — Vendor Bills + Payments
Purchase side pe koi paisa tracking nahi thi. Ab:

- `vendor_bills` + `vendor_bill_payments` tables (Invoice/InvoicePayment ka
  mirror). `vendors` pe `payment_terms_days` + `bank_details`.
- Vendor Bill PO se pre-fill (received/partial), ya standalone. Payment record →
  `amount_paid` + status (unpaid/partially_paid/paid). Overdue = due_date past &
  not paid/cancelled (badge + filter; koi cron notification abhi nahi).
- Bill purely financial — stock nahi hilata (wo GRN ka kaam). Price-variance /
  landed cost abhi nahi.
- Permissions: `vendor_bills.{view_own,view_all,create,edit,delete,record_payment}`.
  Sidebar → Purchase → "Vendor Bills". Vendor show pe outstanding + bills list.
  PO show pe "Create Vendor Bill" + linked bills.
- Full CRUD views + `VendorBillPolicy` + `VendorBillService`.
- Tests: `tests/Feature/Tenant/VendorBillTest.php`

**Files:** `app/Models/{VendorBill,VendorBillPayment,Vendor,PurchaseOrder}.php`,
`app/Policies/VendorBillPolicy.php`, `app/Services/VendorBillService.php`,
`app/Http/Controllers/Web/Tenant/{VendorBillController,VendorController,PurchaseOrderController}.php`,
`app/Http/Requests/{VendorBillRequest,VendorRequest}.php`,
`resources/views/tenant/vendor-bills/*`, `resources/views/tenant/vendors/{_form,show}.blade.php`,
`resources/views/components/sidebar.blade.php`, `config/crm.php`,
`database/seeders/RolesAndPermissionsSeeder.php`, `routes/web.php`,
`database/factories/{VendorFactory,VendorBillFactory}.php`,
`database/migrations/2026_08_29_150000_*`, `150100_*`, `150200_*`

### 3. Raw-material costing / inventory valuation (weighted moving average)
Pehle Product pe sirf ek `rate` (selling price) tha, aur BOM/Work Order costing
usi `rate` ko material cost maan raha tha — yaani raw material ka bech-ne wala
daam hi uska cost. PO pe jo actual rate pay hota tha wo kahin feed nahi hota
tha. Ab:

- `products.cost_price` column (nullable, existing rows `= rate` backfill) —
  item ka current **weighted-average acquisition cost**. `Product::costBasis()`
  isko use karta hai, `rate` pe fallback.
- `product_batches.unit_cost` column — us batch ka actual per-unit cost
  (traceability + future FIFO ke liye).
- **PO receive**: line ka `rate` us receipt ka acquisition cost hai —
  `StockService::receiveBatch()` batch pe stamp karta hai aur product ka
  `cost_price` weighted-average se roll-forward karta hai
  (`(oldQty*oldCost + inQty*inCost) / newQty`). Rate 0 ho to average chhua
  nahi jaata.
- **BOM / Work Order material cost** ab `costBasis()` use karta hai (pehle
  `rate`). WO complete hone pe finished good ka per-unit cost
  (material_snapshot + labor + machine) / qty finished-good ke `cost_price`
  mein roll hota hai, batch pe `unit_cost` set hota hai.
- **UI**: Product create/edit pe "Purchase Cost / Unit" field; Products list
  pe "Cost" column + "Stock Value (at cost)" total; Batches page pe "Unit
  Cost" column; PO receive table pe per-line "Unit Cost"; PO item rows ab
  product pick karne pe `cost_price` (last known purchase cost) prefill karte
  hain, selling rate nahi.

**Files touched:** `app/Models/{Product,ProductBatch,WorkOrder}.php`,
`app/Services/{StockService,PurchaseOrderService,WorkOrderService}.php`,
`app/Http/Controllers/Web/Tenant/{ProductController,PurchaseOrderController}.php`,
`resources/views/tenant/products/{create,edit,index,batches}.blade.php`,
`resources/views/tenant/purchase-orders/{show,create,edit}.blade.php`,
`database/migrations/2026_08_29_140000_add_cost_price_to_products_table.php`,
`database/migrations/2026_08_29_140100_add_unit_cost_to_product_batches_table.php`,
`tests/Feature/Tenant/InventoryCostingTest.php` (new)

---

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
