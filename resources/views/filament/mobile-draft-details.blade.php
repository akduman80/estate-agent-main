<div class="space-y-4">
    <p>Taslak aktarımı EİDS veya yayın onayı vermez. İlan fiyatını ve eksik alanları web panelinde tamamlayın.</p>
    <dl>
        <dt>Adres</dt><dd>{{ data_get($record->payload, 'place.tamAdres') }}</dd>
        <dt>Konum</dt><dd>{{ data_get($record->payload, 'place.lat') }}, {{ data_get($record->payload, 'place.lng') }}</dd>
        <dt>Taşınmaz numarası</dt><dd>{{ data_get($record->payload, 'tasinmazNo') }}</dd>
        <dt>Telefon</dt><dd>{{ data_get($record->payload, 'telefon') }}</dd>
        @foreach($record->payload['ekBilgiler'] ?? [] as $field)
            <dt>{{ $field['ad'] }}</dt><dd>{{ $field['deger'] }}</dd>
        @endforeach
    </dl>
    <p>Aktarılan bütün bilgiler:</p>
    <pre class="overflow-auto whitespace-pre-wrap text-xs">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    @foreach($record->media ?? [] as $index => $file)
        <p><a class="text-primary-600 underline" href="{{ route('mobile-draft-media', [$record, $index]) }}">{{ $file['disk'] === 'public' ? 'Fotoğraf' : 'Video' }} {{ $index + 1 }} — indir</a></p>
    @endforeach
    <p class="text-sm">Videolar bu güvenli alanda tutulur. “Seçimleri mevcut kayda uygula” işlemi seçilmiş videoları sitenin mevcut video kurallarıyla denetleyip kayda ekler. İlanda mevcut 60 saniye ve MP4/WebM kuralları korunur.</p>
</div>
