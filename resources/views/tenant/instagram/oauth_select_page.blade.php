<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a Page</title>
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
            padding: 32px 28px;
            max-width: 380px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        h1 {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 6px;
            text-align: center;
        }
        p.sub {
            font-size: 13px;
            color: #6b7280;
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .page-list { display: flex; flex-direction: column; gap: 10px; }
        .page-item {
            display: block;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 14px 16px;
            text-decoration: none;
            transition: border-color .15s, background .15s;
        }
        .page-item:hover { border-color: #6366f1; background: #f5f5ff; }
        .page-name { font-size: 14px; font-weight: 600; color: #111827; }
        .page-ig { font-size: 12px; color: #6b7280; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Choose a Page to connect</h1>
        <p class="sub">Your Facebook account manages more than one Page. Pick the one whose Instagram account you want to link to this CRM.</p>
        <div class="page-list">
            @foreach($pages as $page)
                <a class="page-item"
                   href="{{ route('instagram.oauth.select-page', ['state' => $state, 'page_id' => $page['page_id']]) }}">
                    <div class="page-name">{{ $page['page_name'] }}</div>
                    <div class="page-ig">Instagram Account ID: {{ $page['instagram_account_id'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</body>
</html>
