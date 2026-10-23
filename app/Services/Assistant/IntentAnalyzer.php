<?php

namespace App\Services\Assistant;

use App\Services\TurkeyAddressService;
use Illuminate\Support\Str;

class IntentAnalyzer
{
    public function analyze(string $message, array $previous = []): array
    {
        $text = Str::lower($message);
        $fold = Str::ascii($text, 'tr');
        $filters = $previous;
        if (str_contains($fold, 'yeni arama')) {
            $filters = [];
        }
        foreach (TurkeyAddressService::getCities() as $city) {
            if (preg_match('/(?<![a-z])'.preg_quote(Str::ascii(Str::lower($city), 'tr'), '/').'(?![a-z])/u', $fold)) {
                if (($filters['city'] ?? null) !== $city) {
                    unset($filters['district']);
                }
                $filters['city'] = $city;
                break;
            }
        }
        if (isset($filters['city'])) {
            foreach (TurkeyAddressService::getDistricts($filters['city']) as $district) {
                if (str_contains($fold, Str::ascii(Str::lower($district), 'tr'))) {
                    $filters['district'] = $district;
                }
            }
        }
        foreach (['satilik' => 'Satılık', 'kiralik' => 'Kiralık'] as $word => $value) {
            if (str_contains($fold, $word)) {
                $filters['type'] = $value;
            }
        }
        foreach (['daire' => 'Daire', 'arsa' => 'Arsa', 'tarla' => 'Tarla', 'isyeri' => 'İşyeri', 'mustakil' => 'Müstakil Ev'] as $word => $value) {
            if (str_contains($fold, $word)) {
                $filters['category'] = $value;
                unset($filters['categories']);
            }
        }
        if (preg_match('/\bev\b/', $fold) && ! preg_match('/daire|mustakil|villa/', $fold)) {
            unset($filters['category']);
            $filters['categories'] = ['Daire', 'Müstakil Ev', 'Villa', 'Konut'];
        }
        if (str_contains($fold, 'arsa') && str_contains($fold, 'tarla')) {
            unset($filters['category']);
            $filters['categories'] = ['Arsa', 'Tarla'];
        }
        if (preg_match('/\b(\d{1,2}\s*\+\s*\d{1,2})\b/', $text, $match)) {
            $filters['rooms'] = preg_replace('/\s+/', '', $match[1]);
        }
        $numbers = ['bir' => '1', 'iki' => '2', 'uc' => '3', 'dort' => '4', 'bes' => '5', 'alti' => '6', 'yedi' => '7', 'sekiz' => '8', 'dokuz' => '9', 'on' => '10'];
        $numeric = preg_replace_callback('/\b('.implode('|', array_keys($numbers)).')\b/', fn ($m) => $numbers[$m[1]], $fold);
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*milyon(?:\s+(\d+)\s*bin)?/', $numeric, $match)) {
            $filters['max_price'] = (float) str_replace(',', '.', $match[1]) * 1000000 + (int) ($match[2] ?? 0) * 1000;
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*bin\s*(?:tl|lira|alt|kadar)/', $numeric, $match)) {
            $filters['max_price'] = (float) str_replace(',', '.', $match[1]) * 1000;
        } elseif (preg_match('/(\d[\d.]*)\s*(?:tl|lira)/', $numeric, $match)) {
            $filters['max_price'] = (float) str_replace('.', '', $match[1]);
        }
        if (str_contains($fold, 'ucuz')) {
            $filters['sort'] = 'price_asc';
        }
        if (str_contains($fold, 'pahalidan') || str_contains($fold, 'en pahali')) {
            $filters['sort'] = 'price_desc';
        }
        if (str_contains($fold, 'daha ucuz') && isset($previous['max_price'])) {
            $filters['max_price'] = floor($previous['max_price'] * .9);
        }
        $intent = match (true) {
            str_contains($fold, 'karsilastir') => 'compare',
            str_contains($fold, 'sozlesme'), str_contains($fold, 'belge'), str_contains($fold, 'taahhut'), str_contains($fold, 'senet'), str_contains($fold, 'kiraci '), str_contains($fold, 'ev sahibi '), str_contains($fold, 'baslangic tarihi') => 'documents',
            preg_match('/ilan(?:i)?\s+(?:ver|olustur|yayin)/', $fold) === 1, str_contains($fold, 'ilan ver'), str_contains($fold, 'ilan olustur'), str_contains($fold, 'yayinla') => 'create',
            str_contains($fold, 'hesapla'), str_contains($fold, 'masraf'), str_contains($fold, 'kira artisi'), str_contains($fold, 'kira getirisi'), str_contains($fold, 'geri donus') => 'calculator',
            preg_match('/(ikincisini|birincisini|ucuncusunu|\d+\.?\s*ilani)\s*ac/', $fold) === 1 => 'open',
            str_contains($fold, 'metrekaresi') => 'details',
            preg_match('/\bilan(?:lar[ai]?|lari|i)?\b/', $fold) === 1, str_contains($fold, 'listele'), str_contains($fold, 'ara'), str_contains($fold, 'bul'), str_contains($fold, 'goster'), str_contains($fold, 'sirala'), str_contains($fold, 'sonraki'), str_contains($fold, 'daha fazla'), str_contains($fold, 'devamini'), $filters !== $previous => 'search',
            default => 'help',
        };
        $ordinal = str_contains($fold, 'ikincisini') ? 2 : (str_contains($fold, 'ucuncusunu') ? 3 : 1);
        if (preg_match('/(\d+)\.?\s*ilani/', $fold, $match)) {
            $ordinal = (int) $match[1];
        }
        $count = preg_match('/(?:ilk\s+)?(?:iki|2)\s+(?:ilan|daire)/', $fold) ? 2 : 3;
        $unsupported = preg_match('/bahce|havuz|otopark|manzara/', $fold) === 1;
        $next = str_contains($fold, 'sonraki') || str_contains($fold, 'daha fazla') || str_contains($fold, 'devamini');

        return compact('intent', 'filters', 'ordinal', 'count', 'unsupported', 'next');
    }
}
