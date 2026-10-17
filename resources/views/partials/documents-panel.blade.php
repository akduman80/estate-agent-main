@if($headerDocuments->isNotEmpty())
<link rel="stylesheet" href="{{ asset('css/documents-panel.css') }}">
<dialog id="documents-panel" class="documents-panel" aria-labelledby="documents-title">
    <div class="documents-heading">
        <div><h2 id="documents-title">Dokümanlar</h2><p data-documents-count aria-live="polite">{{ $headerDocuments->count() }} belge</p></div>
        <button type="button" data-documents-close aria-label="Dokümanları kapat">✕</button>
    </div>
    <div class="documents-search"><label for="documents-search">Belge ara</label><input id="documents-search" type="search" placeholder="Belge adı yazın…" autocomplete="off"></div>
    <div class="documents-list" tabindex="0" role="region" aria-label="Belge listesi">
        @foreach($headerDocuments as $index => $document)
            <article class="documents-entry" data-document-title="{{ $document['title'] }}">
                <a class="documents-link" href="{{ route('documents.download', ['index' => $index]) }}" @if($documentDownload) download @endif aria-label="{{ $document['title'] }} — Orijinali İndir"><span class="documents-icon" aria-hidden="true">↓</span><span>{{ $document['title'] }}</span></a>
                <div class="documents-actions" role="group" aria-label="{{ $document['title'] }} işlemleri">
                    <button type="button" data-document-action="download">Orijinali İndir</button>
                    <button type="button" data-document-action="fill">Belgeyi Doldur</button>
                    <button type="button" data-document-action="edit">Belgeyi Düzenle</button>
                </div>
            </article>
        @endforeach
        <div class="documents-empty" hidden><p>Aramanızla eşleşen belge bulunamadı.</p><button type="button" data-documents-clear>Aramayı temizle</button></div>
    </div>
</dialog>
@include('partials.document-workspace')
<script src="{{ asset('js/documents-panel.js') }}" defer></script>
@endif
