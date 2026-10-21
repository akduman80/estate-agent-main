<x-filament-panels::page>
    <div class="dt-manager" x-data x-init="import('/js/documents/template-editor.js?v=documents-20261021-v1').then(m => m.mount($el, $wire))">
        <p>Yalnız boş şablon yükleyin. Doldurulmuş kimlik, telefon ve adres bilgileri yüklemeyin. Mevcut 12 belge korunur.</p>
        <label>Belge adı <input wire:model="titleInput" maxlength="120"></label>
        <label>Kategori <input wire:model="category" maxlength="80"></label>
        <div wire:ignore data-template-editor>
            <label>PDF / DOCX (en fazla 12 MB) <input type="file" accept=".pdf,.docx" data-template-file disabled></label>
            <p>PDF alanlarını sayfada çizin. DOCX şablonundaki değişkenleri @{{ad_soyad}} gibi çift süslü parantezle işaretleyin; alanlar otomatik bulunur. Word ve PDF çıktıları özgün şablondan üretilir. DOCX metni uzarsa sayfa akışı değişebilir; yayınlamadan önce örnek çıktıyı inceleyin.</p>
            <div class="dt-layout">
                <section>
                    <div class="dt-tools"><button type="button" data-template-prev>← Önceki</button><span data-template-page></span><button type="button" data-template-next>Sonraki →</button><button type="button" data-template-add>Yeni alan çiz</button><button type="button" data-template-repeat>Seçili alanı tekrar çiz</button></div>
                    <div class="dt-canvas"><canvas data-template-canvas aria-label="Şablon alan haritası"></canvas></div>
                    <p data-template-status role="status" aria-live="polite">Dosya seçin veya sürüm geçmişinden açın. Alan için sayfada sol üstten sağ alta sürükleyin.</p>
                </section>
                <section data-template-fields aria-label="Alan özellikleri"></section>
            </div>
        </div>
        <div class="dt-tools">
            <button type="button" wire:click="saveTemplate" wire:loading.attr="disabled">Yeni taslak sürüm kaydet</button>
            <button type="button" data-template-test>Kaydedilen sürümü test et</button>
            <button type="button" wire:click="publishVersion" wire:loading.attr="disabled" @disabled(!$tested)>Test edilen sürümü yayınla</button>
            <button type="button" data-template-new>Yeni şablon</button>
        </div>
        <p role="status">{{ $message }}</p>
        @if ($errors->any()) <ul role="alert">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul> @endif
        <h2>Sürüm geçmişi</h2>
        @foreach($this->versions() as $id => $entry)
            <article wire:key="template-{{ $id }}">
                <h3>{{ last($entry['versions'])['title'] }} — {{ $entry['active'] ? 'Yayında: sürüm '.$entry['active'] : 'Yayında değil' }}</h3>
                <button type="button" wire:click="withdraw('{{ $id }}')">Yayından kaldır</button>
                @foreach(array_reverse($entry['versions']) as $revision)
                    <button type="button" data-template-version="{{ $revision['version'] }}" data-template-id="{{ $id }}">Sürüm {{ $revision['version'] }} · {{ $revision['saved_at'] }} · Aç / yeni sürüme temel al</button>
                @endforeach
            </article>
        @endforeach
    </div>
    <style>
        .dt-manager{display:grid;gap:14px;max-width:100%;min-width:0}.dt-manager label{display:block}.dt-manager input,.dt-manager select{max-width:100%;width:100%;min-height:44px;color:#172554;background:white;border:1px solid #94a3b8;border-radius:6px;padding:8px}.dt-manager input[type=checkbox]{width:24px;min-height:24px}.dt-manager button{min-height:44px;padding:8px 12px;border:1px solid #94a3b8;border-radius:6px}.dt-tools{display:flex;flex-wrap:wrap;gap:8px}.dt-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(240px,1fr);gap:16px}.dt-canvas{overflow:auto;max-height:70vh;background:#cbd5e1}.dt-canvas canvas{display:block;max-width:100%;touch-action:auto;background:white}.dt-field{padding:12px;margin:8px 0;border:1px solid #94a3b8;border-radius:8px}.dt-field.active{border:3px solid #f97316}.dt-manager p{overflow-wrap:anywhere}.dt-manager article{padding:12px;border:1px solid #94a3b8}.dt-manager [data-template-fields]{max-height:75vh;overflow:auto}
        @media(max-width:767px){.dt-layout{grid-template-columns:minmax(0,1fr)}.dt-manager [data-template-fields]{max-height:none}}
    </style>
</x-filament-panels::page>
