<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class CalculatorTool
{
    public const SOURCE = 'https://cdn.gib.gov.tr/api/gibportal-file/file/getFileResources?objectKey=arsiv%2Fyardim-kaynaklar%2Finfografikler%2Fpdfs%2Ftapuharci.pdf';

    public function simple(string $message, ?float $amount): ?array
    {
        $text = Str::ascii(Str::lower($message), 'tr');
        if (preg_match('/euro|dolar|usd|eur/', $text)) {
            return ['message' => 'Bu hesaplama aracı yalnız TL tutarlarını destekliyor. Para birimlerini dönüştürmeden karşılaştırma yapamam.'];
        }
        if (str_contains($text, 'getiri') || str_contains($text, 'geri donus')) {
            $monthly = (float) (app(DocumentTools::class)->prepare($message)['monthlyRent'] ?? 0);
            if (! $amount || ! $monthly) {
                return ['message' => 'Satış bedeli ve aylık kira tutarını belirtin. Örnek: 4 milyon TL ev, kira 15 bin lira; kira getirisi hesapla.'];
            }

            return ['message' => 'Masraf, vergi ve boş kalma süresi hariç basit brüt hesap: '."\n".
                'Yıllık kira = '.number_format($monthly, 2, ',', '.').' × 12 = '.number_format($monthly * 12, 2, ',', '.').' TL.'."\n".
                'Brüt yıllık kira getirisi = yıllık kira / satış bedeli × 100 = %'.number_format($monthly * 12 / $amount * 100, 2, ',', '.').'.'."\n".
                'Basit geri dönüş = satış bedeli / yıllık kira = '.number_format($amount / ($monthly * 12), 2, ',', '.').' yıl. Kira ve değer artışı varsayılmadı. Yatırım garantisi değildir.'];
        }
        if (str_contains($text, 'birim fiyat') && preg_match('/(\d+(?:[.,]\d+)?)\s*(?:metrekare|m²|m2)/u', $message, $m)) {
            $area = (float) str_replace(',', '.', $m[1]);
            if ($amount && $amount > 0 && $area > 0) {
                return ['message' => 'Verdiğiniz alan üzerinden birim fiyat = '.number_format($amount, 2, ',', '.').' TL / '.number_format($area, 2, ',', '.').' m² = '.number_format($amount / $area, 2, ',', '.').' TL/m². Brüt ve net alanı karıştırmadan karşılaştırın.'];
            }
        }

        return null;
    }

    public function titleFee(?float $amount): array
    {
        $source = ['title' => 'GİB — Gayrimenkul alım-satımında tapu harcı', 'url' => self::SOURCE, 'checked_at' => '2026-10-23'];
        if (now()->toDateString() > '2026-11-22') {
            return ['message' => 'Tapu harcı oranının doğrulama süresi doldu. Güncel resmî oran yeniden kontrol edilmeden tutar hesaplanamaz.', 'sources' => [$source]];
        }
        if (! $amount || $amount <= 0 || $amount > 1000000000000) {
            return ['message' => 'Hesaplanacak satış bedelini belirtin. Örnek: 4 milyon TL için tapu harcı hesapla.', 'sources' => [$source]];
        }
        $minor = (int) round($amount * 100);
        $fee = intdiv($minor * 20 + 500, 1000);
        $format = fn (int $value) => number_format($value / 100, 2, ',', '.').' TL';

        return [
            'message' => 'Standart satış için tahmini tapu harcı (muafiyet olmadığı ve emlak vergisi değerinin satış bedelini aşmadığı varsayımıyla): '."\n".
                'İşlem tutarı: '.$format($minor)."\n".'Alıcı: tutar × binde 20 (%2) = '.$format($fee)."\n".
                'Satıcı: tutar × binde 20 (%2) = '.$format($fee)."\n".'Toplam harç: '.$format($fee * 2)."\n".
                'Oran teyit tarihi: 23.10.2026; yeniden teyit son tarihi: 22.11.2026. Kaynak, oranın başlangıç tarihini ayrıca belirtmiyor. Emlak vergisi değeri daha yüksekse hesap matrahı değişir. Döner sermaye, hizmet bedelleri ve diğer giderler bu tutara dahil değildir. Sonuç resmî belge veya bağlayıcı vergi görüşü değildir; işlem öncesi tapu/vergi biriminden teyit edin.',
            'sources' => [$source],
        ];
    }
}
