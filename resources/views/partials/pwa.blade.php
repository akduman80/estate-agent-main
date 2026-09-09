@php($pwaKind = ($pwaAdmin ?? false) ? 'admin' : 'site')
<link rel="manifest" href="/pwa/{{ $pwaKind }}.webmanifest">
<meta name="theme-color" content="#172554">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ $pwaKind === 'admin' ? 'ZYDEMLAK Yönetim' : 'ZYDEMLAK' }}">
<link rel="apple-touch-icon" href="/pwa/{{ $pwaKind }}-192.png">
<script src="/pwa/install.js" defer></script>
