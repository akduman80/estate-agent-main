<section class="listing-share" data-listing-share data-title="{{ $shareTitle }}" data-url="{{ $shareUrl }}" aria-label="Paylaşım seçenekleri">
    <div class="listing-share__platforms" aria-label="Paylaşım uygulamaları">
        <a class="listing-share__platform listing-share__whatsapp" href="https://wa.me/?text={{ rawurlencode($shareTitle.' '.$shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" title="WhatsApp"><svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.7 14.9L2 22l5.2-1.3A10 10 0 1 0 12 2Zm0 18a8 8 0 0 1-4.1-1.1l-.4-.2-2.7.7.7-2.6-.3-.5A8 8 0 1 1 12 20Zm-3.8-13c-.3 0-.7.2-.9.5-.5.6-.8 1.2-.7 2 .2 2.7 3.1 5.6 5.8 6.4 1.3.4 2.4.2 3.1-.4.4-.4.7-1.1.6-1.4l-2.3-1.1c-.2-.1-.4 0-.5.2l-.8 1c-.2.2-.4.2-.6.1-1.4-.6-2.5-1.6-3.1-2.8-.1-.2-.1-.4.1-.5l.6-.8c.2-.2.2-.4.1-.6L8.7 7.3c-.1-.2-.3-.3-.5-.3Z"/></svg>
        </a>
        <a class="listing-share__platform listing-share__facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" title="Facebook"><svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M14 22v-9h3l.5-4H14V6.5c0-1.2.4-2 2-2H18V1.2A26 26 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9Z"/></svg>
        </a>
        <a class="listing-share__platform listing-share__telegram" href="https://t.me/share/url?url={{ rawurlencode($shareUrl) }}&amp;text={{ rawurlencode($shareTitle) }}" target="_blank" rel="noopener noreferrer" aria-label="Telegram" title="Telegram"><svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="m21.5 3-3.4 17c-.2.9-.9 1.1-1.6.6l-5.2-3.8-2.5 2.4c-.3.3-.5.5-1 .5l.4-5.3 9.6-8.7c.4-.4-.1-.6-.6-.3L5.4 12.9.5 11.4c-.9-.3-.9-.9.2-1.3L20 2.7c.9-.3 1.7.2 1.5.3Z"/></svg>
        </a>
        <a class="listing-share__platform listing-share__x" href="https://twitter.com/intent/tweet?url={{ rawurlencode($shareUrl) }}&amp;text={{ rawurlencode($shareTitle) }}" target="_blank" rel="noopener noreferrer" aria-label="X" title="X"><svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 14.6 5.5 22H2.3l8.2-9.4L.8 2h6.5l5.7 7.1L18.9 2ZM17.7 20h1.8L6.2 4H4.3l13.4 16Z"/></svg>
        </a>
        <button class="listing-share__platform listing-share__other" type="button" data-share-native aria-label="BİP" title="BİP"><svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12 2C6.5 2 2 5.8 2 10.5c0 2.3 1.1 4.5 3 6L4 22l5.4-3.3c.9.2 1.7.3 2.6.3 5.5 0 10-3.8 10-8.5S17.5 2 12 2Z"/><text x="4.5" y="13" fill="white" font-family="Arial,sans-serif" font-size="8" font-weight="bold">BiP</text></svg>
        </button>
        <button class="listing-share__platform listing-share__facebook" type="button" data-share-native aria-label="Messenger" title="Messenger"><svg viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12 2C6.5 2 2 6.1 2 11.2c0 2.9 1.4 5.4 3.6 7.1V22l3.4-1.9c1 .3 2 .4 3 .4 5.5 0 10-4.1 10-9.3S17.5 2 12 2Z"/><path d="m6 14 5-5 3 2 4-2-5 5-3-2-4 2Z" fill="white"/></svg>
        </button>
        <button class="listing-share__platform listing-share__instagram" type="button" data-share-native aria-label="Instagram" title="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
        </button>
        <button class="listing-share__platform listing-share__copy" type="button" data-share-copy aria-label="Bağlantıyı kopyala" title="Bağlantıyı kopyala"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h3"/></svg>
        </button>
        <button class="listing-share__platform listing-share__native" type="button" data-share-native aria-label="Diğer uygulamalarda paylaş" title="Diğer uygulamalarda paylaş">        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a3 3 0 1 0-2.8-4L8.8 7.7a3 3 0 1 0 0 4.6l6.4 3.7A3 3 0 1 0 17 14l-6.4-3.7a3 3 0 0 0 0-.6L17 6a3 3 0 0 0 1 2Z" fill="currentColor"/></svg></button>
    </div>
    <p class="listing-share__status" data-share-status role="status" aria-live="polite"></p>
    <dialog class="listing-share__dialog" data-share-dialog aria-labelledby="listing-share-dialog-heading">
        <h3 id="listing-share-dialog-heading">Bağlantıyı paylaş</h3>
        <p>Yukarıdaki uygulama logolarını kullanabilir veya bağlantıyı kopyalayıp istediğiniz uygulamaya yapıştırabilirsiniz.</p>
        <label for="listing-share-link">Bağlantı</label>
        <input id="listing-share-link" data-share-link value="{{ $shareUrl }}" readonly>
        <button class="listing-share__primary" type="button" data-share-copy>Bağlantıyı kopyala</button>
        <p data-share-status role="status" aria-live="polite"></p>
        <button class="listing-share__close" type="button" data-share-close>Kapat</button>
    </dialog>
    <noscript><p>Paylaşım için yukarıdaki uygulama logolarına dokunun.</p></noscript>
</section>
@once
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/listing-share.css') }}?v={{ filemtime(public_path('css/listing-share.css')) }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/listing-share.js') }}?v={{ filemtime(public_path('js/listing-share.js')) }}" defer></script>
    @endpush
@endonce
