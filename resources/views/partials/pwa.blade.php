@php($pwaKind = $pwaKind ?? (($pwaAdmin ?? false) ? 'admin' : 'site'))
<link rel="manifest" href="/pwa/{{ $pwaKind }}.webmanifest">
<meta name="theme-color" content="#172554">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="{{ match ($pwaKind) { 'admin' => 'ZYDEMLAK Yönetim', 'projects' => 'ZYDEMLAK Projeler', default => 'ZYDEMLAK' } }}">
<link rel="apple-touch-icon" href="{{ $pwaKind === 'projects' ? '/pwa/admin-192.png' : "/pwa/{$pwaKind}-192.png" }}">
<script>window.zydPwaKind = @js($pwaKind);</script>
<script src="/pwa/install.js" defer></script>
