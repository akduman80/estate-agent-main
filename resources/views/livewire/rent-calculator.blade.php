<main class="mx-auto w-full max-w-3xl bg-slate-50 px-4 py-8 text-slate-800">
    <h1 class="text-2xl font-bold text-blue-950">Kira Artışı Hesapla</h1>
    <p class="my-3 text-sm">Döneme ait TÜİK on iki aylık ortalama oranıyla hesaplanır. Bu araç standart yıllık yenilemeler için bilgilendirme sağlar; kesin mahkeme sonucu değildir.</p>
    <form wire:submit="calculate" class="grid gap-4 sm:grid-cols-2 print:hidden">
        <label>Mevcut aylık kira (TL)<input wire:model="rent" type="number" step="0.01" min="0.01" required class="block w-full rounded-xl border p-3"></label>
        <label>Taşınmaz türü<select wire:model="kind" class="block w-full rounded-xl border p-3"><option value="home">Konut</option><option value="workplace">Çatılı işyeri</option></select></label>
        <label>Sözleşme yenileme tarihi<input wire:model="renewal_date" type="date" required class="block w-full rounded-xl border p-3"></label>
        <label>Sözleşmedeki sabit artış oranı (%)<input wire:model="contract_rate" type="number" min="0" max="999" step="0.01" class="block w-full rounded-xl border p-3"><span class="text-xs text-slate-500">Boş bırakılırsa yalnız üst sınır hesaplanır. TÜFE şartı için boş bırakabilirsiniz; farklı formüller uzman teyidi gerektirir.</span></label>
        <label class="sm:col-span-2"><input wire:model="special" type="checkbox"> Beş yılı aşan kira ilişkisi, dövizli kira veya özel hukuki durum var.</label>
        <div class="sm:col-span-2">@foreach($errors->all() as $error)<p role="alert" class="text-red-700">{{ $error }}</p>@endforeach</div>
        <button class="rounded-xl bg-orange-500 p-3 font-bold text-white" wire:loading.attr="disabled">Hesapla</button><span wire:loading role="status">Hesaplanıyor…</span>
    </form>
    @if($result)
        <section data-rent-result class="my-5 rounded-2xl border bg-white p-5" aria-live="polite">
            <h2 class="text-lg font-bold">{{ $result['date'] }} yenileme dönemi</h2>
            @if($result['ceiling_only'])<p class="my-3 font-bold">Sözleşme şartı belirtilmediği için bu sonuç üst sınır örneğidir; kendiliğinden uygulanacak kesin kira değildir.</p>@endif
            <dl class="my-3 grid grid-cols-2 gap-3">
                @foreach(['cpi'=>'TÜFE 12 aylık ortalama','cap'=>'Yasal oran üst sınırı','applied'=>'Hesapta uygulanan oran'] as $key=>$label)<dt>{{ $label }}</dt><dd>%{{ number_format($result[$key],2,',','.') }}</dd>@endforeach
                @foreach(['rent'=>'Mevcut aylık kira','increase'=>'Artış tutarı / aylık fark','new_rent'=>'Yeni aylık kira','annual_difference'=>'Yıllık fark'] as $key=>$label)<dt>{{ $label }}</dt><dd class="font-bold">{{ number_format($result[$key],2,',','.') }} TL</dd>@endforeach
            </dl>
            <p class="text-sm">TÜİK yayın tarihi: {{ $result['published_on'] }} · <a href="{{ $result['source'] }}" target="_blank" rel="noopener noreferrer" class="underline">Resmî dönem kaynağı</a> · <a href="{{ \App\Services\RentIncreaseCalculator::LAW }}" target="_blank" rel="noopener noreferrer" class="underline">TBK 344 ve geçici maddeler</a></p>
            <p class="my-3 text-sm">Sözleşme hükümleri, beş yılı aşan ilişkiler ve özel koşullar sonucu değiştirebilir. Konutlarda 11.06.2022–01.07.2024 arası yenilemeler için geçici %25 sınırı dikkate alınır; bu sınır çatılı işyerlerine uygulanmaz.</p>
            <div class="flex flex-wrap gap-3 print:hidden"><button type="button" wire:click="download" wire:loading.attr="disabled" class="rounded-xl border p-3">PDF İndir</button><button type="button" onclick="window.print()" class="rounded-xl border p-3">Yazdır</button>
                <a href="{{ 'https://wa.me/?text='.rawurlencode('ZYDEMLAK kira artışı üst sınır hesabı — '.$result['date'].'; mevcut: '.number_format($result['rent'],2,',','.').' TL; uygulanan oran: %'.$result['applied'].'; yeni aylık: '.number_format($result['new_rent'],2,',','.').' TL. Bilgilendirme amaçlıdır. '.route('tools.rent')) }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border p-3">WhatsApp'ta Paylaş</a></div>
        </section>
    @endif
</main>
