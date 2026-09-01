<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Privacy Policy — Milan CRM</title>
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
    <h1>Privacy Policy</h1>
    <div class="updated">Last updated: {{ now()->format('F d, Y') }}</div>

    <p>This Privacy Policy explains how <strong>Milan CRM</strong> ("we", "us", "our") collects, uses, and protects
    information when you use our CRM platform, including features that connect to your
    <strong>Instagram</strong> and <strong>WhatsApp Business</strong> accounts via the Meta Platform.</p>

    <h2>1. Information We Collect</h2>
    <p>We collect the following categories of information:</p>
    <ul>
        <li><strong>Account information</strong> — name, email, phone number, and business details provided when you sign up.</li>
        <li><strong>CRM data</strong> — leads, contacts, deals, quotations, invoices, tasks, and other records you or your team create in the platform.</li>
        <li><strong>Meta Platform Data</strong> — when you connect your Instagram or WhatsApp Business account, we receive and store:
            <ul>
                <li>Access tokens issued by Meta to authorize API calls on your behalf.</li>
                <li>Your Instagram Business Account ID, connected Facebook Page ID, and WhatsApp Business Account (WABA)/Phone Number ID.</li>
                <li>Incoming Instagram comments and direct messages, and incoming WhatsApp messages, so we can match them against your automation rules and reply on your behalf.</li>
                <li>Basic account/profile info (name, username, phone number) of the customers who message or comment, strictly to display and respond to them inside your CRM.</li>
            </ul>
        </li>
        <li><strong>Usage data</strong> — log-in activity and feature usage within the CRM, used only to operate and improve the product.</li>
    </ul>

    <h2>2. How We Use Meta Platform Data</h2>
    <div class="card">
        <p style="margin:0">Data obtained through the Instagram and WhatsApp APIs is used <strong>solely</strong> to power the automation features you configure — for example, auto-replying to a comment, sending a keyword-triggered DM, or logging a conversation against a CRM contact. We do <strong>not</strong> sell this data, and we do not use it for advertising or share it with data brokers.</p>
    </div>
    <p>Specifically:</p>
    <ul>
        <li>Access tokens are stored securely and used only to make API calls (send DM, reply to comment, send WhatsApp message) on your explicit configuration.</li>
        <li>Message/comment content is matched against the automation rules and chatbot flows you create, and logged in your account's activity log for your own reference.</li>
        <li>Only your organization ("tenant") and its authorized staff can view data connected to your Instagram/WhatsApp accounts — other tenants on the platform cannot see it.</li>
    </ul>

    <h2>3. How We Use Information (General)</h2>
    <ul>
        <li>To provide and operate the CRM, including lead management, messaging automation, invoicing, and reporting.</li>
        <li>To authenticate you and secure your account.</li>
        <li>To send you service-related notifications (e.g. task reminders, follow-up alerts).</li>
        <li>To improve and troubleshoot the platform.</li>
    </ul>

    <h2>4. Data Sharing</h2>
    <p>We do not sell your data. We share information only:</p>
    <ul>
        <li>With Meta (Facebook/Instagram/WhatsApp), strictly as required to make the API calls you've configured.</li>
        <li>With infrastructure providers (hosting, database, email/SMS delivery) who process data only on our instructions.</li>
        <li>When required by law, or to protect the rights and safety of our users.</li>
    </ul>

    <h2>5. Data Security</h2>
    <p>Access tokens and credentials are stored server-side and are never exposed to other tenants or the public. We use industry-standard practices (encrypted connections, access control, tenant data isolation) to protect your data.</p>

    <h2>6. Data Retention &amp; Deletion</h2>
    <p>We retain data for as long as your account is active. If you disconnect your Instagram or WhatsApp account, stored access tokens are no longer used for new API calls. You may request permanent deletion of your account and associated data at any time by contacting us below.</p>

    <h2>7. Your Rights</h2>
    <ul>
        <li>Access, correct, or export the data stored in your CRM account.</li>
        <li>Disconnect your Instagram/WhatsApp integration at any time from your account settings.</li>
        <li>Request deletion of your account and data.</li>
    </ul>

    <h2>8. Children's Privacy</h2>
    <p>Milan CRM is a business tool and is not directed at, or knowingly used by, children under 16.</p>

    <h2>9. Changes to This Policy</h2>
    <p>We may update this Privacy Policy from time to time. Material changes will be reflected by updating the "Last updated" date above.</p>

    <h2>10. Contact Us</h2>
    <p>For privacy questions, data access, or deletion requests, contact us at
        <a href="mailto:octacoretechnologies0@gmail.com">octacoretechnologies0@gmail.com</a>.
    </p>

</div>
</body>
</html>
