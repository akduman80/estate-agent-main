<x-filament-panels::page>
    <p>Bugünkü istek: {{ $this->counts()['requests'] }} · Hata: {{ $this->counts()['errors'] }}. Sayaçlar önbellek süresince tutulur; mesaj içerikleri kaydedilmez.</p>
    <p>Sağlayıcı: {{ config('assistant.endpoint') ? 'Sunucu ortamından yapılandırılmış niyet servisi' : 'Henüz seçilmedi; yerel komut araçları çalışır' }}. Model: {{ config('assistant.model') ?: 'Belirtilmedi' }}. API anahtarı: {{ config('assistant.key') ? 'Tanımlı (gizli)' : 'Tanımlı değil' }}. Doğrulanmış fiyat ve kullanım kaydı olmadan maliyet tahmini gösterilmez.</p>
    <p>Bugünkü geri bildirimler — Yararlı: {{ $this->counts()['helpful'] }} · Yararlı değil: {{ $this->counts()['unhelpful'] }}. Geri bildirimlere mesaj veya kişisel veri eklenmez.</p>
    <form wire:submit="save">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">Ayarları kaydet</x-filament::button>
    </form>
</x-filament-panels::page>
