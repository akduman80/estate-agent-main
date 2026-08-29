@php
    $code = trim($__env->yieldContent('code'));

    [$heading, $description] = match ($code) {
        '401' => ['Oturum açmanız gerekiyor', 'Bu sayfayı görüntülemek için hesabınıza giriş yapmalısınız.'],
        '402' => ['İşlem tamamlanamadı', 'Bu içeriğe şu anda erişilemiyor.'],
        '403' => ['Bu sayfaya erişiminiz yok', 'Görmek istediğiniz içerik için gerekli izne sahip değilsiniz.'],
        '404' => ['Aradığınız sayfayı bulamadık', 'Bağlantı değişmiş, kaldırılmış veya adres hatalı yazılmış olabilir.'],
        '419' => ['Sayfanın süresi doldu', 'Güvenliğiniz için oturum süresi sona erdi. Lütfen sayfayı yenileyip tekrar deneyin.'],
        '429' => ['Biraz yavaşlayalım', 'Kısa sürede çok fazla istek gönderildi. Lütfen biraz bekleyip tekrar deneyin.'],
        '503' => ['Kısa bir bakımdayız', 'Size daha iyi hizmet verebilmek için çalışıyoruz. Lütfen kısa süre sonra tekrar deneyin.'],
        default => ['Beklenmedik bir sorun oluştu', 'İsteğinizi şu anda tamamlayamadık. Ekibimiz sorunu çözmek için çalışıyor.'],
    };
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $code }} | Ziyade Emlak</title>
    <link rel="icon" href="{{ asset('fe-assets/assets/img/logo.png') }}">
    <style>
        :root {
            color-scheme: light;
            --navy: #172554;
            --navy-dark: #0f1d46;
            --orange: #f97316;
            --orange-dark: #ea580c;
            --slate: #64748b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            color: var(--navy);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .page {
            position: relative;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            overflow: hidden;
        }

        .page::before,
        .page::after {
            position: absolute;
            border-radius: 999px;
            content: "";
            filter: blur(1px);
            pointer-events: none;
        }

        .page::before {
            top: -180px;
            right: -120px;
            width: 460px;
            height: 460px;
            background: rgba(249, 115, 22, .10);
        }

        .page::after {
            bottom: -240px;
            left: -160px;
            width: 520px;
            height: 520px;
            background: rgba(23, 37, 84, .08);
        }

        .header {
            position: relative;
            z-index: 1;
            border-bottom: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, .92);
        }

        .header-inner {
            display: flex;
            width: min(1120px, calc(100% - 32px));
            height: 88px;
            margin: 0 auto;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            color: var(--navy);
            font-size: 20px;
            font-weight: 800;
            text-decoration: none;
        }

        .brand img {
            width: auto;
            height: 54px;
            object-fit: contain;
        }

        .header-link {
            color: #475569;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
        }

        .header-link:hover { color: var(--orange); }

        main {
            position: relative;
            z-index: 1;
            display: grid;
            width: min(1120px, calc(100% - 32px));
            flex: 1;
            margin: 0 auto;
            place-items: center;
            padding: 48px 0;
        }

        .card {
            display: grid;
            width: min(900px, 100%);
            overflow: hidden;
            border: 1px solid #e2e8f0;
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .10);
            grid-template-columns: 1.05fr .95fr;
        }

        .content { padding: 64px 56px; }

        .code {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            background: #fff7ed;
            padding: 8px 13px;
            color: var(--orange-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .code::before {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--orange);
            content: "";
        }

        h1 {
            max-width: 520px;
            margin: 22px 0 14px;
            font-size: clamp(30px, 4vw, 46px);
            line-height: 1.08;
            letter-spacing: -.035em;
        }

        .description {
            max-width: 520px;
            margin: 0;
            color: var(--slate);
            font-size: 16px;
            line-height: 1.75;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 30px;
        }

        .button {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 0;
            border-radius: 12px;
            padding: 0 19px;
            cursor: pointer;
            font: inherit;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
        }

        .button-primary {
            background: var(--orange);
            color: #fff;
            box-shadow: 0 8px 22px rgba(249, 115, 22, .24);
        }

        .button-primary:hover { background: var(--orange-dark); }

        .button-secondary {
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #334155;
        }

        .button-secondary:hover { background: #f8fafc; }

        .visual {
            position: relative;
            display: grid;
            min-height: 430px;
            overflow: hidden;
            background: linear-gradient(145deg, var(--navy-dark), #253b78);
            place-items: center;
        }

        .visual::before,
        .visual::after {
            position: absolute;
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 50%;
            content: "";
        }

        .visual::before { width: 330px; height: 330px; }
        .visual::after { width: 245px; height: 245px; }

        .house {
            position: relative;
            z-index: 1;
            width: 210px;
            color: #fff;
        }

        .house svg { display: block; width: 100%; height: auto; }

        .visual-code {
            position: absolute;
            right: 24px;
            bottom: 20px;
            color: rgba(255, 255, 255, .12);
            font-size: 72px;
            font-weight: 900;
            line-height: 1;
        }

        footer {
            position: relative;
            z-index: 1;
            padding: 0 16px 28px;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }

        @media (max-width: 760px) {
            .header-inner { height: 76px; }
            .brand { gap: 8px; font-size: 17px; }
            .brand img { height: 45px; }
            .header-link { display: none; }
            main { padding: 28px 0; }
            .card { grid-template-columns: 1fr; border-radius: 22px; }
            .content { padding: 40px 28px; }
            .visual { min-height: 230px; grid-row: 1; }
            .house { width: 145px; }
            .visual-code { font-size: 54px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <header class="header">
            <div class="header-inner">
                <a class="brand" href="{{ url('/') }}" aria-label="Ziyade Emlak ana sayfa">
                    <img src="{{ asset('fe-assets/assets/img/logo.png') }}" alt="Ziyade Emlak">
                    <span>Ziyade Emlak</span>
                </a>
                <a class="header-link" href="{{ url('/') }}">Ana sayfaya dön →</a>
            </div>
        </header>

        <main>
            <section class="card" aria-labelledby="error-heading">
                <div class="content">
                    <span class="code">HATA {{ $code }}</span>
                    <h1 id="error-heading">{{ $heading }}</h1>
                    <p class="description">{{ $description }}</p>
                    <div class="actions">
                        <a class="button button-primary" href="{{ url('/') }}">
                            <span aria-hidden="true">⌂</span> Ana sayfaya dön
                        </a>
                        <button class="button button-secondary" type="button" onclick="if (history.length > 1) { history.back(); } else { location.href = '{{ url('/') }}'; }">
                            <span aria-hidden="true">←</span> Geri git
                        </button>
                    </div>
                </div>

                <div class="visual" aria-hidden="true">
                    <div class="house">
                        <svg viewBox="0 0 240 210" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M31 105L120 31L209 105" stroke="currentColor" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M51 94V184H189V94" stroke="currentColor" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M96 184V129H144V184" stroke="#F97316" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M164 53V30H187V73" stroke="currentColor" stroke-width="11" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <span class="visual-code">{{ $code }}</span>
                </div>
            </section>
        </main>

        <footer>© {{ date('Y') }} Ziyade Emlak · Güvenilir gayrimenkul platformu</footer>
    </div>
</body>
</html>
