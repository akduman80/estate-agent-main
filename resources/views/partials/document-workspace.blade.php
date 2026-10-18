<link rel="stylesheet" href="{{ asset('css/document-workspace.css') }}?v=documents-20261018-stage4">
<dialog id="document-workspace" class="documents-workspace" aria-labelledby="document-workspace-title" data-csrf="{{ csrf_token() }}" data-pdf-base="{{ asset('vendor/pdfjs') }}/">
    <header class="dw-heading">
        <div><h2 id="document-workspace-title">Belge çalışma alanı</h2><p data-workspace-subtitle>Orijinal belge korunur.</p></div>
        <button type="button" data-workspace-close aria-label="Belge çalışma alanını kapat">✕</button>
    </header>
    <div class="dw-modes" role="group" aria-label="Belge çalışma modu">
        <button type="button" data-workspace-mode="fill" aria-pressed="false">Belgeyi Doldur</button>
        <button type="button" data-workspace-mode="edit" aria-pressed="false">Belgeyi Düzenle</button>
        <a class="dw-download" data-workspace-download download>Orijinali İndir</a>
    </div>
    <p class="dw-status" data-workspace-status role="status" aria-live="polite">Belge hazırlanıyor…</p>
    <div class="dw-mobile-tabs" role="tablist" aria-label="Çalışma alanı bölümleri">
        <button type="button" role="tab" aria-selected="true" data-workspace-tab="form">Form</button>
        <button type="button" role="tab" aria-selected="false" data-workspace-tab="preview">Ön İzleme</button>
        <button type="button" role="tab" aria-selected="false" data-workspace-tab="actions">İşlemler</button>
    </div>
    <div class="dw-body">
        <section class="dw-controls" aria-label="Belge işlemleri">
            <p class="dw-voice-info">Sesli giriş isteğe bağlıdır. Tarayıcının konuşma hizmeti sesi hizmet sağlayıcısına iletebilir. Bu site ses dosyası oluşturmaz veya saklamaz. Mikrofon yalnız dokunduğunuzda açılır.</p>
            <p class="dw-voice-status" data-workspace-voice-status role="status" aria-live="polite"></p>
            <div class="dw-history">
                <button type="button" data-workspace-undo disabled>Geri al</button>
                <button type="button" data-workspace-redo disabled>Yeniden yap</button>
                <button type="button" data-workspace-reset disabled>Orijinale dön</button>
            </div>
            <div data-workspace-fill>
                <h3>Belgeye özel alanlar</h3>
                <p data-workspace-fill-notice class="dw-notice">Şablon ve alan eşlemesi kontrol ediliyor.</p>
                <form data-workspace-fields autocomplete="off"></form>
            </div>
            <div data-workspace-edit hidden>
                <h3>Belge metinleri ve ek içerik</h3>
                <p class="dw-notice">Özgün metni değiştirin veya boş bırakarak silin. Değişiklikler yalnız çalışma kopyasına uygulanır. Uzun metinleri ek içerik olarak ekleyebilirsiniz.</p>
                <details><summary>Bu sayfanın özgün metinlerini düzenle</summary><div data-workspace-original-text></div></details>
                <div class="dw-tools" aria-label="Eklenecek içerik">
                    <button type="button" data-workspace-add="paragraph">Paragraf ekle</button>
                    <button type="button" data-workspace-add="article">Madde ekle</button>
                    <button type="button" data-workspace-add="note">Açıklama ekle</button>
                    <button type="button" data-workspace-add="dateAmount">Tarih / tutar ekle</button>
                    <button type="button" data-workspace-add="table">Tablo ekle</button>
                    <button type="button" data-workspace-add="signature">İmza alanı ekle</button>
                    <button type="button" data-workspace-add="field">Bilgi alanı ekle</button>
                </div>
                <div data-workspace-blocks></div>
            </div>
            <p class="dw-unsaved">Çalışmanız yalnız bu açık pencerede tutulur. Kapatıldığında silinir. İndirdiğiniz veya paylaştığınız dosyalar cihazınızda kalır. Elektronik imza oluşturulmaz.</p>
        </section>
        <section class="dw-preview" aria-label="Gerçek belge önizlemesi">
            <div class="dw-page-toolbar" hidden data-workspace-paging>
                <button type="button" data-workspace-prev aria-label="Önceki sayfa">←</button>
                <span data-workspace-page></span>
                <button type="button" data-workspace-next aria-label="Sonraki sayfa">→</button>
                <button type="button" data-workspace-zoom-out aria-label="Uzaklaştır">−</button>
                <span data-workspace-zoom>100%</span>
                <button type="button" data-workspace-zoom-in aria-label="Yakınlaştır">+</button>
            </div>
            <div class="dw-paper" hidden data-workspace-paper><canvas aria-label="Orijinal PDF ve doldurulan alanların önizlemesi" role="img"></canvas></div>
            <p class="dw-preview-message" data-workspace-preview-message>Orijinal belge yükleniyor…</p>
        </section>
        <section class="dw-output" aria-label="Belge çıktıları">
            <h3>Belge işlemleri</h3>
            <button type="button" data-workspace-generate>Belgeyi Oluştur</button>
            <button type="button" data-workspace-pdf disabled>PDF İndir</button>
            <button type="button" data-workspace-word disabled>Word İndir</button>
            <button type="button" data-workspace-print disabled>Yazdır</button>
            <button type="button" data-workspace-share disabled>Paylaş</button>
            <button type="button" data-workspace-return>Tekrar Düzenle</button>
            <p>Paylaşım menüsünde cihazınızda bulunan WhatsApp, Telegram, BiP veya e-posta uygulamasını seçebilirsiniz. Dosya paylaşımı desteklenmiyorsa PDF’yi indirip uygulamaya ekleyin. Dosya otomatik gönderilmez.</p>
            <p data-workspace-output-status role="status" aria-live="polite">Önce belgeyi oluşturun. Düzenleme modunda eklenen içerikler ayrı ek sayfalarda yer alır.</p>
        </section>
    </div>
</dialog>
<script type="module" src="{{ asset('js/documents/workspace.js') }}?v=documents-20261018-all2"></script>

