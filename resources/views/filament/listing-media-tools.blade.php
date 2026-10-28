@once
    <script src="/js/listing-media.js?v={{ filemtime(public_path('js/listing-media.js')) }}"></script>
@endonce
<div class="space-y-2 text-sm">
    <p role="status">Fotoğraf adedi: {{ count($this->data['uploaded_images'] ?? []) }} / {{ config('listings.photos.max_files') }}</p>
    <details><summary class="cursor-pointer font-medium">Klavyeyle fotoğraf sıralaması ve kapak seçimi</summary>
        <ol class="mt-2 space-y-2">
            @foreach(($this->data['uploaded_images'] ?? []) as $key=>$photo)
            <li class="flex flex-wrap items-center gap-2" wire:key="photo-order-{{ $key }}">
                <span>{{ $loop->iteration }}. fotoğraf {{ $loop->first ? '(Kapak)' : '' }}</span>
                <x-filament::button type="button" size="xs" color="gray" :wire:click="'moveListingPhoto('.\Illuminate\Support\Js::from((string) $key).', -1)'">Öne taşı</x-filament::button>
                <x-filament::button type="button" size="xs" color="gray" :wire:click="'moveListingPhoto('.\Illuminate\Support\Js::from((string) $key).', 1)'">Arkaya taşı</x-filament::button>
                <x-filament::button type="button" size="xs" color="gray" :wire:click="'moveListingPhoto('.\Illuminate\Support\Js::from((string) $key).', 0)'">Kapak yap</x-filament::button>
            </li>
            @endforeach
        </ol>
    </details>
</div>
