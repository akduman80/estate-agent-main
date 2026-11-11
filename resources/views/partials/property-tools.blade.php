<div class="mt-3 flex flex-wrap items-center gap-3 text-sm" data-voice-search data-endpoint="{{ route('search.voice') }}">
    <button type="button" data-voice-start class="rounded-xl bg-white px-4 py-3 font-bold text-blue-950"><i class="fa-solid fa-microphone" aria-hidden="true"></i> Sesle Ara</button>
    <a href="{{ route('tools.map') }}" class="rounded-xl bg-white px-4 py-3 font-bold text-blue-950">Haritada Ara</a>
    <a href="{{ route('tools.parcel') }}" class="rounded-xl bg-white px-4 py-3 font-bold text-blue-950">Arsa ve Parsel Sorgula</a>
    <a href="{{ route('tools.rent') }}" class="rounded-xl bg-white px-4 py-3 font-bold text-blue-950">Kira Artışını Hesapla</a>
    <dialog data-voice-dialog aria-labelledby="voice-search-title" class="m-auto max-h-[85dvh] w-full max-w-lg overflow-y-auto rounded-2xl p-5 text-slate-800">
        <h2 id="voice-search-title" class="text-lg font-bold">Sesle ilan arama</h2>
        <p class="my-3 text-sm">İzin verirseniz tarayıcınızın konuşma tanıma hizmeti sesinizi sağlayıcısına gönderebilir. Sağlayıcı ve veri işleme koşulları kullandığınız tarayıcıya bağlıdır. Sitemiz ses kaydı oluşturmaz. Tanınan metin, aramayı uyguladığınızda siteye gönderilir. Yazılı girişle de devam edebilirsiniz.</p>
        <button type="button" data-voice-record class="rounded-lg bg-blue-950 p-3 text-white">İzin ver ve dinle</button>
        <label class="my-3 block">Algılanan metin<textarea data-voice-text rows="3" maxlength="1000" class="block w-full rounded-lg border p-3" placeholder="Örn: Kadirli’de kiralık işyeri göster"></textarea></label>
        <p data-voice-status role="status" aria-live="polite" class="my-3 text-sm"></p>
        <a data-voice-results hidden class="my-3 block font-bold text-blue-900">Filtreleri kontrol et ve ilanları göster</a>
        <button type="button" data-voice-apply class="rounded-lg bg-orange-500 p-3 text-white">Metni aramaya dönüştür</button>
        <button type="button" data-voice-close class="rounded-lg border p-3">Kapat</button>
    </dialog>
</div>
@once @push('scripts')<script src="{{ asset('js/property-voice.js') }}" defer></script><script src="{{ asset('js/property-search-voice.js') }}" defer></script>@endpush @endonce
