<x-layouts.modern title="Arsa ve Parsel Sorgula | ZYDEMLAK">
    <main class="mx-auto w-full max-w-3xl bg-slate-50 px-4 py-8 text-slate-800">
        <h1 class="text-2xl font-bold text-blue-950">Arsa ve Parsel Sorgula</h1>
        <p class="my-4">Bilgilerinizi hazırlayıp resmî TKGM sitesinde sorgulayabilirsiniz. Bilgiler resmî siteye otomatik aktarılmaz.</p>
        <form data-parcel-form data-location-selects class="grid gap-4 sm:grid-cols-2">
            <label>İl<select name="city" data-city-select data-districts-url="{{ route('locations.districts') }}" data-neighborhoods-url="{{ route('locations.neighborhoods') }}" class="block w-full rounded-xl border p-3"><option value="">İl seçin</option>@foreach(\App\Services\TurkeyAddressService::getCities() as $key => $city)<option value="{{ $key }}">{{ $city }}</option>@endforeach</select></label>
            <label>İlçe<select name="district" data-district-select disabled class="block w-full rounded-xl border p-3"><option value="">Önce il seçin</option></select></label>
            <label>Mahalle / Köy<select name="neighborhood" data-neighborhood-select disabled class="block w-full rounded-xl border p-3"><option value="">Önce ilçe seçin</option></select></label>
            <label>Ada<input name="block" inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" class="block w-full rounded-xl border p-3"></label>
            <label>Parsel<input name="parcel" inputmode="numeric" pattern="[0-9]{1,12}" maxlength="12" class="block w-full rounded-xl border p-3"></label>
            <button type="submit" class="rounded-xl bg-orange-500 p-3 font-bold text-white">Bilgileri Kopyala</button>
        </form>
        <textarea data-parcel-copy readonly hidden aria-label="Kopyalanacak parsel bilgileri" class="my-3 w-full rounded-lg border p-3"></textarea>
        <p data-parcel-status role="status" class="my-3"></p>
        <a href="https://parselsorgu.tkgm.gov.tr/" target="_blank" rel="noopener noreferrer" class="my-4 inline-block rounded-xl bg-blue-950 p-3 font-bold text-white">Resmî Parsel Sorgulamayı Aç ↗</a>
        <p class="text-sm text-slate-600">Yeni sekmede açılır. Parsel bilgileri kesin imar veya mülkiyet onayı değildir ve EİDS doğrulamasının yerine geçmez. Bu araç TKGM'den veri çekmez veya sorgu bilgilerinizi saklamaz.</p>
    </main>
    @push('scripts')<script>
        document.querySelector('[data-parcel-form]').addEventListener('submit', async event => {
            event.preventDefault(); const form=event.currentTarget, output=document.querySelector('[data-parcel-copy]'), status=document.querySelector('[data-parcel-status]');
            const labels={city:'İl',district:'İlçe',neighborhood:'Mahalle / Köy',block:'Ada',parcel:'Parsel'};
            output.value=Object.entries(labels).map(([key,label])=>label+': '+(form.elements[key].value||'Belirtilmedi')).join('\n');
            try { await navigator.clipboard.writeText(output.value); status.textContent='Bilgiler kopyalandı. Resmî sitedeki alanlara kendiniz girebilirsiniz.'; }
            catch { output.hidden=false; output.focus(); output.select(); status.textContent='Otomatik kopyalanamadı. Seçili metni kopyalayabilirsiniz.'; }
        });
    </script>@endpush
</x-layouts.modern>
