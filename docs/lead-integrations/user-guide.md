# Lead Integrations — User Guide

## Yeh Feature Kya Hai?

Jab aap **Meta (Facebook/Instagram) Ads**, **IndiaMART**, ya **JustDial** pe apna business list karte ho
ya ads chalate ho, toh wahan se aane wale leads automatically aapke CRM mein aa jayenge —
bina manually copy-paste kiye.

---

## Super Admin: Tenant Ko Access Kaise Dein

Super Admin hi decide karta hai ki kaun sa tenant kaun sa integration use kar sakta hai.

### Steps:

1. **Super Admin Panel** mein login karein
2. Left sidebar mein **Lead Integrations** click karein
3. Jo tenant ko access dena hai uske row mein **"Manage Access"** button click karein
4. Jis platform ka access dena ho uska **toggle ON** karein
5. **"Save Access Settings"** click karein

> **Note:** Access dene ke baad tenant apne panel se credentials khud configure karega.
> Super admin ke paas tenant ki API keys/tokens nahi hoti.

---

## Tenant: Integration Kaise Connect Karein

### Pehle Dekho: Kaunse Integrations Available Hain

1. Apne CRM panel mein login karein
2. Left sidebar mein **Lead Integrations** par click karein
3. Aapko sabhi available platforms ki cards dikhegi
4. **🔒 Locked** wale platforms Super Admin ne abhi enable nahi kiye — unhe contact karein
5. **Active/Inactive** wale aap khud configure kar sakte ho

---

## Meta Lead Ads Connect Karna (Facebook / Instagram Ads)

### Kya Chahiye?
- Facebook Business Account
- Facebook Page (jis pe ads chalate ho)
- Meta for Developers account

### Steps:

**Part A — Facebook Developer App Setup:**

1. [developers.facebook.com](https://developers.facebook.com) pe jaao
2. "My Apps" → "Create App" → App Type: "Business"
3. App create hone ke baad left panel mein **"Webhooks"** product add karo
4. "Webhooks" → **"Subscribe to this object"** → Object type: **Page**
5. **Callback URL**: CRM se apna webhook URL copy karo (setup page pe milega)
6. **Verify Token**: Apni marzi ka koi bhi secret word likho (e.g. `my-secret-2024`)
7. **Fields**: `leadgen` select karo → Subscribe

**Part B — Page Access Token:**

8. **Graph API Explorer** (developers.facebook.com/tools/explorer) pe jaao
9. Apni app select karo, apna Facebook Page select karo
10. **"Generate Access Token"** → permissions mein `pages_read_engagement` + `leads_retrieval` select karo
11. Token copy karo (yeh Page Access Token hai)
12. **Important**: Short-lived token hai → [Long-lived token banana seekho](https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived)

**Part C — CRM mein Enter Karo:**

13. CRM → Lead Integrations → **Meta Lead Ads → Set Up**
14. **Verify Token**: Wahi likho jo Facebook mein daala tha (Step 6)
15. **Page Access Token**: Step 11 wala token paste karo
16. **App Secret**: App Settings → Basic → App Secret
17. **Enable Integration**: Toggle ON karo
18. **Save** karo

✅ **Done!** Ab jab bhi koi Facebook/Instagram Lead Ad form bharta hai, lead automatically CRM mein aa jayega.

---

## IndiaMART Connect Karna

### Kya Chahiye?
- IndiaMART Seller Account
- CRM API key (IndiaMART seller panel se milti hai)

### Steps:

1. [seller.indiamart.com](https://seller.indiamart.com) pe login karo
2. **My Account → Manage Account → CRM → Lead Manager API**
3. **API Key (GLUSR Code)** copy karo (agar nahi bana hai toh "Generate" click karo)
4. Apna registered **mobile number** note karo

5. CRM → Lead Integrations → **IndiaMART → Set Up**
6. **API Key**: Wala code paste karo
7. **Mobile Number**: Wahi number jo IndiaMART account pe register hai
8. **Enable Integration**: Toggle ON
9. **Save Settings**

10. **"Test Connection"** button click karo — confirm karo ki connection ho raha hai
11. **"Sync Now"** se abhi ke leads import karo

✅ **Done!** Ab har 30 minute mein automatically naye leads import hote rahenge.

---

## JustDial Connect Karna

### Kya Chahiye?
- JustDial Verified Business Account
- JustDial Vendor Panel access

### Steps:

1. [vendor.justdial.com](https://vendor.justdial.com) pe login karo
2. **Settings → API Integration / Lead Push** section dhundo
3. **Webhook URL** wala field hoga — usme CRM ka webhook URL daalo
   (CRM → Lead Integrations → JustDial → Setup pe milega)
4. Agar JustDial secret key deta hai toh woh bhi CRM mein enter karo

5. CRM → Lead Integrations → **JustDial → Set Up**
6. Webhook URL copy karo (already shown hoga)
7. **Secret Key**: JustDial se mila tha toh enter karo, nahi mila toh khali chhodo
8. **Enable Integration**: Toggle ON
9. **Save**

✅ **Done!** JustDial se aane wali inquiries ab seedha CRM mein aayengi.

---

## TradeIndia / Sulekha Connect Karna

Yeh platforms bhi same webhook system use karte hain:

1. CRM → Lead Integrations → Platform select karo
2. Apna **Webhook URL** copy karo
3. Platform ke vendor panel mein jaao → Lead Notification / API settings
4. Webhook URL paste karo
5. CRM mein **Enable** karo aur save karo

---

## Leads Kahan Dikhenge?

Har imported lead **Leads section** mein automatically dikhega:
- **Source** field mein platform ka naam hoga (e.g., "IndiaMART", "Facebook / Meta Ads")
- **Status** = "New"
- Notes mein platform-specific info hogi (jaise IndiaMART Query ID, product name)

Filter lagao: **Leads → Source dropdown** se specific platform ke leads dekho.

---

## Webhook URL Regenerate Karna

Agar koi webhook URL leak ho gayi ya security concern hai:

1. Integration Setup page pe jaao
2. **"Regenerate URL"** button click karo
3. ⚠️ **Confirm karo** — purani URL turant band ho jayegi
4. Nayi URL ko jaldi platform mein update karo — warna leads aana band ho jayenge

---

## Common Problems

**Q: Meta se leads nahi aa rahe**
→ Verify Token check karo — CRM mein aur Facebook mein exactly match hona chahiye
→ Page se app ka connection check karo
→ `leadgen` field subscribed hai ya nahi check karo

**Q: IndiaMART test connection fail ho raha hai**
→ API Key galat ho sakta hai — seller panel se dobara copy karo
→ Mobile number 10 digits hona chahiye, bina country code ke

**Q: Duplicate leads aa rahe hain**
→ System 24 ghante mein same phone/email dobara nahi daalta — yeh normal hai
→ Agar duplicate aaya toh leads list mein manually delete karo

**Q: "Locked" platform dikhta hai**
→ Super Admin se contact karo aur request karo ki woh integration enable kare

---

## Support

Koi bhi issue hone pe Super Admin se contact karein ya support pe ticket raise karein.
