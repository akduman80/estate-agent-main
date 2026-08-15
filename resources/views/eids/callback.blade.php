<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EİDS Callback</title>
    <style>
        body {
            align-items: center;
            background: #0f1115;
            color: #f5f5f5;
            display: flex;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            justify-content: center;
            margin: 0;
            min-height: 100vh;
        }

        main {
            background: #181b22;
            border: 1px solid #2a2f3a;
            border-radius: 8px;
            max-width: 720px;
            padding: 32px;
            width: calc(100% - 32px);
        }

        h1 {
            font-size: 24px;
            margin: 0 0 12px;
        }

        p {
            color: #c9ced8;
            line-height: 1.55;
            margin: 0 0 18px;
        }

        code {
            background: #0b0d11;
            border: 1px solid #303744;
            border-radius: 6px;
            display: block;
            overflow-wrap: anywhere;
            padding: 14px;
        }

        .success {
            color: #72e0a2;
        }

        .error {
            color: #ff8d8d;
        }
    </style>
</head>
<body>
    <main>
        <h1 class="{{ $status === 'success' ? 'success' : 'error' }}">
            {{ $status === 'success' ? 'EİDS Kullanıcı Kodu Alındı' : 'EİDS İşlemi Tamamlanamadı' }}
        </h1>

        <p>{{ $message }}</p>

        @if ($envLine)
            <code>{{ $envLine }}</code>
        @endif
    </main>
</body>
</html>
