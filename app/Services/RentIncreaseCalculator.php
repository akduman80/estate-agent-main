<?php

namespace App\Services;

use App\Models\RentPeriodRate;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class RentIncreaseCalculator
{
    public const LAW = 'https://www.csgb.gov.tr/Media/xgjbrxit/6098-say%C4%B1l%C4%B1-bor%C3%A7lar-kanunu.pdf';

    public function calculate(array $data): array
    {
        $date = CarbonImmutable::parse($data['renewal_date']);
        if ($date->startOfMonth()->gt(CarbonImmutable::now()->startOfMonth()) || $date->lt('2019-01-01') || ($data['kind'] === 'workplace' && $date->lt('2020-07-01'))) {
            throw ValidationException::withMessages(['renewal_date' => 'Bu tarih için geleceğe dönük veya eski özel mevzuat hesabı yapılamıyor. Uzman teyidi alın.']);
        }
        $rate = RentPeriodRate::whereDate('period', $date->startOfMonth()->toDateString())->whereDate('published_on', '<=', today())->first();
        if (! $rate) {
            throw ValidationException::withMessages(['renewal_date' => 'Seçilen dönem için doğrulanmış oran henüz kaydedilmemiş. Oran tahmin edilmedi; yönetici kaynaklı dönem kaydı ekleyebilir.']);
        }
        $basis = (int) round((float) $rate->rate * 100);
        $cap = $basis;
        if ($data['kind'] === 'home' && $date->betweenIncluded(CarbonImmutable::parse('2022-06-11'), CarbonImmutable::parse('2024-07-01'))) {
            $cap = min($cap, 2500);
        }
        $applied = isset($data['contract_rate']) && $data['contract_rate'] !== '' ? min($cap, (int) round((float) $data['contract_rate'] * 100)) : $cap;
        $minor = (int) round((float) $data['rent'] * 100);
        $increase = intdiv($minor * $applied + 5000, 10000);

        return ['date' => $date->toDateString(), 'cpi' => $basis / 100, 'cap' => $cap / 100, 'applied' => $applied / 100, 'rent' => $minor / 100, 'increase' => $increase / 100, 'new_rent' => ($minor + $increase) / 100, 'annual_difference' => $increase * 12 / 100, 'source' => $rate->source_url, 'published_on' => $rate->published_on->toDateString(), 'kind' => $data['kind'], 'ceiling_only' => ! isset($data['contract_rate']) || $data['contract_rate'] === ''];
    }
}
