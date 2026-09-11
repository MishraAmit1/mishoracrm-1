<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Data Deletion Status</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f6f8; color: #1a1a1a; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.08); padding: 32px 36px; max-width: 420px; text-align: center; }
        .icon { font-size: 40px; margin-bottom: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        p { color: #555; font-size: 14px; line-height: 1.5; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="card">
        @if($done)
            <div class="icon">✅</div>
            <h1>Data Deletion Complete</h1>
            <p>Your Instagram account connection and stored access data associated with this app have been deleted.</p>
        @else
            <div class="icon">⏳</div>
            <h1>Request Not Found</h1>
            <p>We could not find a data deletion request matching this confirmation code, or it has expired.</p>
        @endif
        <p>Confirmation code: <code>{{ $code }}</code></p>
    </div>
</body>
</html>
