<div data-listing-workspace wire:poll.45s="saveDraft(true)" x-data x-init="const u = new URL(location.href); u.searchParams.delete('yeni'); u.searchParams.set('taslak', @js($this->draftId)); history.replaceState({}, '', u);" class="rounded-xl border border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-gray-900">
    <p class="font-semibold">İlanınızı tek sayfada hazırlayın</p>
    <p class="mt-1 text-sm">Bilgileri aşağı kaydırarak girin. Taslağı istediğiniz zaman kaydedebilir, önizleyebilir ve yönetici onayına gönderebilirsiniz.</p>
    <nav aria-label="Form bölümleri" class="mt-3 flex flex-wrap gap-3 text-sm">
        @foreach(['category'=>'Kategori','description'=>'İlan','property'=>'Taşınmaz','address'=>'Adres','features'=>'Özellikler','media'=>'Medya','note'=>'Not','contact'=>'İletişim','verification'=>'Doğrulama'] as $id=>$label)
            <a href="#listing-{{ $id }}" class="underline focus-visible:outline focus-visible:outline-2">{{ $label }}</a>
        @endforeach
    </nav>
    <p role="status" aria-live="polite" class="mt-3 text-sm">{{ $this->draftStatus }} @if($this->lastDraftSavedAt) · Son kayıt: {{ $this->lastDraftSavedAt }} @endif <span wire:loading wire:target="saveDraft"> · Kaydediliyor…</span></p>
    <details class="mt-3 text-sm"><summary class="cursor-pointer">Kaydedilmiş ilan taslaklarım</summary><ul class="mt-2 space-y-2">
        @foreach(\App\Models\ListingFormDraft::query()->where('user_id', auth()->id())->latest('updated_at')->limit(10)->get() as $savedDraft)
            <li><a class="underline" href="{{ \App\Filament\Portal\Resources\ListingResource::getUrl($savedDraft->listing_id ? 'edit' : 'create', array_filter(['record'=>$savedDraft->listing_id,'taslak'=>$savedDraft->id])) }}">{{ $savedDraft->payload['title'] ?? 'İlan taslağı' }} · {{ $savedDraft->updated_at->format('d.m.Y H:i') }}</a></li>
        @endforeach
        <li><a class="underline" href="{{ \App\Filament\Portal\Resources\ListingResource::getUrl('create', ['yeni'=>1]) }}">Yeni boş taslak aç</a></li>
    </ul></details>
    @error('draft') <p role="alert" class="mt-2 text-sm text-danger-600">{{ $message }}</p> @enderror
    @if($errors->any())<div role="alert" class="mt-3 text-sm text-danger-600"><p class="font-semibold">Lütfen işaretli alanları kontrol edin:</p><ul class="list-disc ps-5">@foreach(array_unique($errors->all()) as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <style>[data-listing-workspace]~*{min-width:0}[id^="listing-"]{scroll-margin-top:6rem}</style>
</div>
