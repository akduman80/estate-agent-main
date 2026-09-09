<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketDataService
{
    private const TROY_OUNCE_GRAMS = 31.1034768;

    public function current(): array
    {
        if (! config('services.market_data.enabled', true)) {
            return [];
        }

        $currencies = $this->currencies();
        $goldUsd = $this->goldUsd();
        $items = [];

        if (isset($goldUsd, $currencies['USD'])) {
            $items['gold'] = [
                'label' => 'Gram Altın',
                'value' => ($goldUsd * $currencies['USD']) / self::TROY_OUNCE_GRAMS,
                'icon' => 'fa-solid fa-cubes-stacked',
            ];
        }

        foreach (['USD' => ['Dolar', 'fa-solid fa-dollar-sign'], 'EUR' => ['Euro', 'fa-solid fa-euro-sign']] as $code => [$label, $icon]) {
            if (isset($currencies[$code])) {
                $items[strtolower($code)] = compact('label', 'icon') + ['value' => $currencies[$code]];
            }
        }

        return $items;
    }

    private function currencies(): array
    {
        $cached = Cache::get('market_data_tcmb');
        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        try {
            $response = Http::accept('application/xml')->timeout(4)->retry(1, 150)
                ->get(config('services.market_data.tcmb_url'));
            $response->throw();
            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
            if ($xml === false) {
                return [];
            }

            $rates = [];
            foreach ($xml->Currency as $currency) {
                $code = (string) $currency['CurrencyCode'];
                if (in_array($code, ['USD', 'EUR'], true) && (float) $currency->ForexSelling > 0) {
                    $rates[$code] = (float) $currency->ForexSelling;
                }
            }

            if ($rates !== []) {
                Cache::put('market_data_tcmb', $rates, now()->addHours(6));
            }

            return $rates;
        } catch (\Throwable $exception) {
            Log::warning('TCMB kuru alınamadı.', ['message' => $exception->getMessage()]);

            return [];
        }
    }

    private function goldUsd(): ?float
    {
        $cached = Cache::get('market_data_gold_usd');
        if (is_numeric($cached) && (float) $cached > 0) {
            return (float) $cached;
        }

        try {
            $response = Http::acceptJson()->timeout(4)->retry(1, 150)
                ->get(rtrim(config('services.market_data.gold_url'), '/').'/price/XAU');
            $response->throw();
            $price = (float) $response->json('price');
            if ($price > 0) {
                Cache::put('market_data_gold_usd', $price, now()->addHour());

                return $price;
            }
        } catch (\Throwable $exception) {
            Log::warning('Altın fiyatı alınamadı.', ['message' => $exception->getMessage()]);
        }

        return null;
    }
}
