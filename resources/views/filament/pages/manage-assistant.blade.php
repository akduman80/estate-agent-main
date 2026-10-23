<x-filament-panels::page>
    <p>Bugünkü istek: {{ $this->counts()['requests'] }} · Hata: {{ $this->counts()['errors'] }}. Sayaçlar önbellek süresince tutulur; mesaj içerikleri kaydedilmez.</p>
    <p>Sağlayıcı: Google Gemini · Model: {{ config('assistant.gemini.model') }} · Bağlantı: {{ app(\App\Services\Assistant\GeminiClient::class)->configured() ? 'Yapılandırılmış (hizmet erişimi ayrıca test edilmelidir)' : 'Anahtar tanımlı değil; yerel araçlar çalışır' }}. Anahtar yalnız sunucudaki GEMINI_API_KEY değişkeninden okunur, bu ekranda gösterilmez.</p>
    @php($gemini = $this->geminiCounts())
    <p>Bugünkü Gemini çağrısı: {{ $gemini['calls'] }} / {{ config('assistant.gemini.server_daily_limit') }} · Hata: {{ $gemini['errors'] }} · Giriş token: {{ $gemini['input'] }} · Çıkış token: {{ $gemini['output'] }} · Düşünme token: {{ $gemini['thought'] }} · Güvenli bütçe rezervasyonu: {{ $gemini['reserved'] }} / {{ config('assistant.gemini.daily_token_budget') }}.</p>
    <p>Tahmini API maliyeti: {{ $gemini['estimated_usd'] === null ? 'Güncel model birim fiyatı sunucuda tanımlanmadığı için hesaplanmadı.' : '$'.number_format($gemini['estimated_usd'], 6).' USD (yaklaşık; fatura değildir)' }}</p>
    <p>Bugünkü geri bildirimler — Yararlı: {{ $this->counts()['helpful'] }} · Yararlı değil: {{ $this->counts()['unhelpful'] }}. Geri bildirimlere mesaj veya kişisel veri eklenmez.</p>
    <form wire:submit="save">
        {{ $this->form }}
        <x-filament::button type="submit" class="mt-4">Ayarları kaydet</x-filament::button>
    </form>
</x-filament-panels::page>
