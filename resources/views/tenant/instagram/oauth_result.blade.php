<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Connected!' : 'Connection Failed' }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            max-width: 360px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }
        .icon.success { background: #dcfce7; }
        .icon.error   { background: #fee2e2; }
        h1 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 10px;
        }
        p {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.6;
        }
        .badge {
            display: inline-block;
            margin-top: 20px;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge.success { background: #dcfce7; color: #16a34a; }
        .badge.error   { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="card">
        @if($success)
            <div class="icon success">
                <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="width:36px;height:36px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1>Connected!</h1>
            <p>{{ $message }}</p>
            <span class="badge success">Instagram Business Account Linked</span>
        @else
            <div class="icon error">
                <svg viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="width:36px;height:36px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1>Connection Failed</h1>
            <p>{{ $message }}</p>
            <span class="badge error">Please try again</span>
        @endif
    </div>
</body>
</html>
