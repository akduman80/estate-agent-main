@props(['footer' => false])

<a href="https://icticaret.ticaret.gov.tr/haberler/elektronik-ilan-dogrulama-sistemi-eids-yetki-dogrulama-uygulamasi-hayata-gecirildi"
   target="_blank" rel="noopener noreferrer"
   @class(['eids-info', 'home-overview-card home-eids' => !$footer, 'eids-footer' => $footer])>
    <img src="{{ asset('fe-assets/assets/img/eids-logo.jpg') }}" alt="EİDS — Elektronik İlan Doğrulama Sistemi" width="800" height="363" @if($footer) loading="lazy" @endif>
    <span>EİDS hakkında <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></span>
    <span class="sr-only">Ticaret Bakanlığı bilgilendirmesi, yeni sekmede açılır</span>
</a>
