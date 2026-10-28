<div class="flex flex-wrap gap-2" aria-label="Hızlı kategori seçimi">
    @foreach([['Arsa','Satılık','Satılık Arsa'],['Daire','Kiralık','Kiralık Daire'],['Daire','Satılık','Satılık Daire'],['Müstakil Ev','Satılık','Satılık Müstakil Ev'],['Dükkan & Mağaza','Kiralık','Kiralık Dükkân & Mağaza']] as [$category,$type,$label])
        <x-filament::button type="button" size="sm" color="gray" :wire:click="'selectQuickCategory('.\Illuminate\Support\Js::from($category).', '.\Illuminate\Support\Js::from($type).')'" wire:confirm="Kategoriye özgü alanlar yeni seçime göre değişir. Ortak bilgiler korunur. Devam edilsin mi?">{{ $label }}</x-filament::button>
    @endforeach
</div>
