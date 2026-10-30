<x-filament-panels::page>
    <x-filament::section>
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}
            <x-filament::button type="submit">Ayarları kaydet</x-filament::button>
        </form>
    </x-filament::section>
    <x-filament::section heading="WhatsApp bağlantısı">
        @php($connection = $this->connection())
        <p>Bağlantı: {{ ['open'=>'Bağlı','close'=>'Bağlı değil','connecting'=>'Bağlanıyor','unchecked'=>'Kontrol edilmedi','unavailable'=>'Servise ulaşılamıyor'][$connection->connection_state] ?? 'Bilinmiyor' }}</p>
        <p>Son kontrol: {{ $connection->checked_at?->timezone('Europe/Istanbul')->format('d.m.Y H:i:s') ?? 'Henüz yok' }}</p>
        <div class="flex flex-wrap gap-3 mt-4">
            <x-filament::button wire:click="checkConnection" wire:loading.attr="disabled">Bağlantıyı kontrol et</x-filament::button>
            <x-filament::button wire:click="connect" wire:loading.attr="disabled">Güvenli QR ile bağla</x-filament::button>
            <x-filament::button wire:click="testMessage" wire:confirm="Kaydedilmiş yönetici numarasına bir test mesajı gönderilsin mi?" wire:loading.attr="disabled">Test bildirimi gönder</x-filament::button>
        </div>
        <p class="mt-4">WhatsApp → Bağlı cihazlar → Cihaz bağla yolundan QR kodunu okutun. Kodun ekran görüntüsünü paylaşmayın. Sonra bağlantıyı kontrol edin.</p>
        <p>QR/Baileys bağlantısı resmî WhatsApp Cloud API değildir; bağlantı kesintisi veya hesap kısıtlaması olabilir.</p>
        @if($qr)
            <img src="{{ $qr }}" alt="WhatsApp cihaz bağlama QR kodu" style="width:280px;max-width:100%;height:auto" />
            <x-filament::button wire:click="$set('qr', null)">QR kodunu gizle</x-filament::button>
        @endif
    </x-filament::section>
    <x-filament::section heading="Son 30 bildirim">
        <p>Kabul edildi: API mesaj kimliği verdi; telefona teslim veya okunma kanıtı değildir. Sonucu belirsiz kayıtlar tekrar gönderilmez. Tamamlanmış kayıtlar 30 gün sonra temizlenir.</p>
        <div style="overflow-x:auto;max-width:100%">
            <table class="w-full text-sm" style="min-width:620px">
                <thead><tr><th>No / Olay</th><th>Durum</th><th>Deneme</th><th>Tarih</th><th>İşlem</th></tr></thead>
                <tbody>
                @forelse($this->records() as $record)
                    <tr><td class="p-3">{{ $record->id }} / {{ ['member'=>'Yeni üye','listing'=>'İlan onayı','test'=>'Test'][$record->event_type] ?? $record->event_type }}</td>
                        <td>{{ ['pending'=>'Bekliyor','retry'=>'Yeniden denenecek','sending'=>'İşleniyor','accepted'=>'API kabul etti','failed'=>'Başarısız','uncertain'=>'Sonucu belirsiz','cancelled'=>'İptal'][$record->status] ?? $record->status }} @if($record->error_code) ({{ $record->error_code }}) @endif</td>
                        <td>{{ $record->attempts }}</td><td>{{ $record->created_at->timezone('Europe/Istanbul')->format('d.m.Y H:i') }}</td>
                        <td>@if($record->status==='failed' && $record->error_code==='rate_limited' && $record->attempts<8)<x-filament::button wire:click="retry({{ $record->id }})">Yeniden dene</x-filament::button>@endif</td>
                    </tr>
                @empty<tr><td colspan="5" class="p-3">Henüz bildirim yok.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
