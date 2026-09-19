<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Data Deletion — Milan CRM</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{
    --bg:#0B0E14; --surface:#131720; --border:#242938;
    --text-100:#F2F4F8; --text-300:#9AA3B5; --accent:#378ADD;
}
@media (prefers-color-scheme: light){
    :root{ --bg:#F7F8FA; --surface:#FFFFFF; --border:#E5E8EE; --text-100:#1A1D26; --text-300:#5B6272; --accent:#185FA5; }
}
*{box-sizing:border-box;}
body{
    margin:0; background:var(--bg); color:var(--text-100);
    font-family:'DM Sans',sans-serif; line-height:1.7; font-size:15px;
}
.wrap{ max-width:820px; margin:0 auto; padding:56px 24px 100px; }
h1{ font-family:'Outfit',sans-serif; font-size:32px; font-weight:700; margin:0 0 6px; }
.updated{ color:var(--text-300); font-size:13px; margin-bottom:40px; }
h2{ font-family:'Outfit',sans-serif; font-size:19px; font-weight:600; margin:40px 0 12px; padding-top:8px; border-top:1px solid var(--border); }
h2:first-of-type{ border-top:none; padding-top:0; }
p, li{ color:var(--text-300); }
strong{ color:var(--text-100); }
ul{ padding-left:20px; }
li{ margin-bottom:6px; }
a{ color:var(--accent); }
.card{ background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:16px 20px; margin:16px 0; }
.back{ display:inline-block; margin-bottom:24px; color:var(--text-300); text-decoration:none; font-size:13px; }
</style>
</head>
<body>
<div class="wrap">

    <a href="{{ route('home') }}" class="back">← Milan CRM</a>
    <h1>Data Deletion Instructions</h1>
    <div class="updated">Last updated: {{ now()->format('F d, Y') }}</div>

    <p>You can ask <strong>Milan CRM</strong> to delete your data, including data received through the Instagram, WhatsApp, and Facebook (Meta) integrations, at any time.</p>

    <h2>Option 1 — Disconnect the integration</h2>
    <ol>
        <li>Log in to your Milan CRM account.</li>
        <li>Open the Instagram or WhatsApp settings page.</li>
        <li>Click <strong>Disconnect</strong>. Stored access tokens are removed and no further API calls are made on your behalf.</li>
    </ol>
    <p>You can also remove Milan CRM from your Facebook/Instagram account under <em>Settings → Business Integrations</em>. Meta will notify us and we will delete the data linked to that connection.</p>

    <h2>Option 2 — Request full deletion by email</h2>
    <div class="card">
        <p style="margin:0">Email <a href="mailto:octacoretechnologies0@gmail.com?subject=Data%20Deletion%20Request">octacoretechnologies0@gmail.com</a> with the subject <strong>"Data Deletion Request"</strong>. Include the email address of your Milan CRM account and, if relevant, your Instagram username or WhatsApp number.</p>
    </div>

    <h2>What gets deleted</h2>
    <ul>
        <li>Access tokens and account identifiers from Meta (Instagram / WhatsApp / Page IDs).</li>
        <li>Instagram comments and direct messages, and WhatsApp messages stored in your account.</li>
        <li>Your account, contacts, and CRM records, if you request full account deletion.</li>
    </ul>

    <h2>Timeline</h2>
    <p>We confirm your request and complete deletion within 30 days. Some records may be kept longer only where the law requires (for example, tax invoices).</p>

    <p>See also our <a href="{{ route('privacy-policy') }}">Privacy Policy</a>.</p>

</div>
</body>
</html>
