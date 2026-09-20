<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Terms of Service — Milan CRM</title>
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
    <h1>Terms of Service</h1>
    <div class="updated">Last updated: {{ now()->format('F d, Y') }}</div>

    <p>These Terms of Service govern your use of <strong>Milan CRM</strong> ("we", "us", "our"), a multi-tenant CRM platform. By creating an account or using the platform you agree to these terms.</p>

    <h2>1. The Service</h2>
    <p>Milan CRM provides lead and contact management, deals, quotations, invoicing, tasks, and messaging automation, including optional integrations with Instagram, WhatsApp Business and other third-party services.</p>

    <h2>2. Your Account</h2>
    <ul>
        <li>You must provide accurate information and keep your login credentials secure.</li>
        <li>You are responsible for all activity under your organization's account and for the staff you invite.</li>
        <li>You must be authorized to bind the business you register.</li>
    </ul>

    <h2>3. Acceptable Use</h2>
    <ul>
        <li>Do not use the platform for unlawful, fraudulent, or abusive purposes, or to send spam or unsolicited bulk messages.</li>
        <li>When using Instagram, WhatsApp, or other Meta features, you must comply with the applicable Meta Platform Terms and Policies and obtain any consent required from your customers.</li>
        <li>Do not attempt to disrupt, reverse-engineer, or gain unauthorized access to the platform or other tenants' data.</li>
    </ul>

    <h2>4. Your Data</h2>
    <p>You retain ownership of the data you put into Milan CRM. You grant us permission to process it only to provide the service. See our <a href="{{ route('privacy-policy') }}">Privacy Policy</a> for details.</p>

    <h2>5. Subscriptions &amp; Billing</h2>
    <p>Paid plans are billed as shown at purchase. Access to paid features may be limited or suspended when a subscription expires or is cancelled. Applicable taxes are charged as required by law.</p>

    <h2>6. Third-Party Services</h2>
    <p>Integrations depend on third parties (such as Meta). We are not responsible for changes, outages, or policy decisions of those services.</p>

    <h2>7. Availability &amp; Liability</h2>
    <p>We work to keep the service available but provide it "as is" without warranties. To the extent permitted by law, we are not liable for indirect or consequential losses.</p>

    <h2>8. Termination</h2>
    <p>You may stop using the service at any time. We may suspend or terminate accounts that violate these terms. On termination you may request deletion of your data (see <a href="{{ url('/data-deletion') }}">Data Deletion</a>).</p>

    <h2>9. Changes</h2>
    <p>We may update these terms from time to time. Continued use after changes means you accept the updated terms.</p>

    <h2>10. Contact</h2>
    <p>Questions about these terms: <a href="mailto:octacoretechnologies0@gmail.com">octacoretechnologies0@gmail.com</a>.</p>

</div>
</body>
</html>
