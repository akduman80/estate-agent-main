<?php

namespace App\Services\Assistant;

use App\Services\TurkeyAddressService;
use Illuminate\Support\Str;

class IntentAnalyzer
{
    public function analyze(string $message, array $previous = []): array
    {
        $message = str_ireplace(['satlik', 'kirlik', 'mustkil'], ['satılık', 'kiralık', 'müstakil'], $message);
        $text = Str::lower($message);
        $fold = Str::ascii($text, 'tr');
        // Full requests start afresh; short refinements retain the current search.
        $fresh = preg_match('/yeni arama|baska (?:bir )?arama/', $fold) ||
            (preg_match('/satilik|kiralik/', $fold) && preg_match('/daire|mustakil|villa|arsa|tarla|isyeri|\bev\b/', $fold) && ! preg_match('/olsun|ayni|bunlar|onlar/', $fold));
        $filters = $fresh ? [] : $previous;
        if (str_contains($fold, 'yeni arama') || preg_match('/tum\s+(?:aktif\s+)?ilan/', $fold)) {
            $filters = [];
        }
        $explicitCity = false;
        foreach (TurkeyAddressService::getCities() as $city) {
            if (preg_match('/(?<![a-z])'.preg_quote(Str::ascii(Str::lower($city), 'tr'), '/').'(?![a-z])/u', $fold)) {
                if (($filters['city'] ?? null) !== $city) {
                    unset($filters['district'], $filters['neighborhood']);
                }
                $filters['city'] = $city;
                $explicitCity = true;
                break;
            }
        }
        $locationText = preg_replace('/\byeni arama\b|\bbaska (?:bir )?arama\b/', '', $fold);
        $location = app(LocationResolver::class)->resolve($locationText, $filters, $explicitCity);
        $ambiguous = $location['ambiguous'];
        if ($location['filters']) {
            if (isset($location['filters']['city']) && ($filters['city'] ?? null) !== $location['filters']['city']) unset($filters['district'], $filters['neighborhood']);
            if (isset($location['filters']['district']) && ($filters['district'] ?? null) !== $location['filters']['district']) unset($filters['neighborhood']);
            $filters = array_merge($filters, $location['filters']);
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
        $numbers = ['bir'=>'1','iki'=>'2','uc'=>'3','dort'=>'4','bes'=>'5','alti'=>'6','yedi'=>'7','sekiz'=>'8','dokuz'=>'9','on'=>'10'];
        $numeric = preg_replace_callback('/\b('.implode('|', array_keys($numbers)).')\b/', fn ($m) => $numbers[$m[1]], $fold);
        $numeric = str_replace(['–', '—'], '-', $numeric);
        // Area expressions are parsed separately so their limits cannot change price limits.
        if (preg_match('/(?:(net|brut)\s*)?(\d+(?:[.,]\d+)?)\s*(?:m2|m²|metrekare)(?:\s*(net|brut))?([^,;]*)/', $numeric, $area)) {
            $kind = ($area[1] ?: ($area[3] ?? '')) === 'net' ? 'net_area' : 'area';
            $upper = preg_match('/en fazla|altinda|alti|kadar/', $area[4] ?? '') || preg_match('/en fazla\s*(?:net|brut)?\s*'.$area[2].'/', $numeric);
            $filters[($upper ? 'max_' : 'min_').$kind] = (float) str_replace(',', '.', $area[2]);
        }
        $money = fn ($n, $unit) => (float) str_replace(',', '.', $n) * ($unit === 'milyon' ? 1000000 : ($unit === 'bin' ? 1000 : 1));
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(bin|milyon)?\s*(?:ile|-)\s*(\d+(?:[.,]\d+)?)\s*(bin|milyon)/', $numeric, $range)) {
            unset($filters['price_lt'], $filters['price_gt']);
            $filters['min_price'] = $money($range[1], $range[2] ?: $range[4]);
            $filters['max_price'] = $money($range[3], $range[4]);
            $filters['currency'] = 'TRY';
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(milyon|bin)(?:\s+(\d+)\s*bin)?|([\d.]+)\s*(?:tl|lira)/', $numeric, $amount)) {
            unset($filters['min_price'], $filters['max_price'], $filters['price_lt'], $filters['price_gt']);
            $value = !empty($amount[4]) ? (float) str_replace('.', '', $amount[4]) : $money($amount[1], $amount[2]) + (int) ($amount[3] ?? 0) * 1000;
            $tail = substr($numeric, strpos($numeric, $amount[0]) + strlen($amount[0]));
            $prefix = substr($numeric, 0, strpos($numeric, $amount[0]));
            $key = preg_match('/en az\s*$/', $prefix) ? 'min_price' : (preg_match('/^.{0,12}(?:uzeri|ustunde)/', $tail) ? 'price_gt' : (preg_match('/^.{0,12}(?:altinda|alti)/', $tail) ? 'price_lt' : 'max_price'));
            $filters[$key] = $value;
            $filters['currency'] = 'TRY';
        }
        if (str_contains($fold, 'villa')) { $filters['category'] = 'Villa'; unset($filters['categories']); }
        if (preg_match('/(\d+)\s*katli/', $numeric, $m)) $filters['total_floors'] = (int) $m[1];
        if (preg_match('/(?<![+\d])(\d+)\.?\s*kat\b/', $numeric, $m)) $filters['floor'] = $m[1];
        if (preg_match('/ikinci\s*kat/', $fold)) $filters['floor'] = '2';
        if (preg_match('/zemin\s*kat\s*(?:olmasin|istemiyorum|haric)/', $fold)) $filters['exclude_ground'] = true;
        elseif (preg_match('/zemin\s*kat/', $fold)) $filters['floor'] = '0';
        if (preg_match('/hisseli\s*(?:olmasin|istemiyorum|haric)/', $fold)) $filters['exclude_shared'] = true;
        if (str_contains($fold, 'ucuz')) {
            $filters['sort'] = 'price_asc';
        }
        if (str_contains($fold, 'pahalidan') || str_contains($fold, 'en pahali')) {
            $filters['sort'] = 'price_desc';
        }

        $intent = match (true) {
            str_contains($fold, 'karsilastir') => 'compare',
            str_contains($fold, 'sozlesme'), str_contains($fold, 'belge'), str_contains($fold, 'taahhut'), str_contains($fold, 'senet'), str_contains($fold, 'kiraci '), str_contains($fold, 'ev sahibi '), str_contains($fold, 'baslangic tarihi') => 'documents',
            preg_match('/ilan(?:i)?\s+(?:ver|olustur|yayin)/', $fold) === 1, str_contains($fold, 'ilan ver'), str_contains($fold, 'ilan olustur'), str_contains($fold, 'yayinla') => 'create',
            str_contains($fold, 'hesapla'), str_contains($fold, 'masraf'), str_contains($fold, 'kira artisi'), str_contains($fold, 'kira getirisi'), str_contains($fold, 'geri donus') => 'calculator',
            preg_match('/(ikincisini|birincisini|ucuncusunu|\d+\.?\s*ilani)\s*ac/', $fold) === 1 => 'open',
            preg_match('/metrekaresi|kac metrekare|isitma|bina yasi|hangi kat|musait|eids|tapusu/', $fold) === 1 && $previous !== [] => 'details',
            preg_match('/\bilan(?:lar[ai]?|lari|i)?\b/', $fold) === 1, str_contains($fold, 'listele'), str_contains($fold, 'ara'), str_contains($fold, 'bul'), str_contains($fold, 'goster'), str_contains($fold, 'sirala'), str_contains($fold, 'sonraki'), str_contains($fold, 'daha fazla'), str_contains($fold, 'devamini'), $filters !== $previous => 'search',
            default => 'help',
        };
        $ordinal = str_contains($fold, 'ikinci') ? 2 : (str_contains($fold, 'ucuncu') ? 3 : 1);
        if (preg_match('/(\d+)\.?\s*ilani/', $fold, $match)) {
            $ordinal = (int) $match[1];
        }
        $count = preg_match('/(?:ilk\s+)?(?:iki|2)\s+(?:ilan|daire)/', $fold) ? 2 : 3;
        $unsupported = false;
        foreach (['bahceli', 'havuzlu', 'otoparkli', 'manzarali'] as $feature) {
            if (str_contains($fold, $feature)) $filters['features'] = array_values(array_unique(array_merge($filters['features'] ?? [], [$feature])));
        }
        if (preg_match('/(?:bahce|havuz|otopark|manzara).*?(?:onemli degil|sart degil|kosulunu kaldir)/', $fold)) unset($filters['features'], $filters['unsupported_condition']);
        $unsupported = !empty($filters['features']) || !empty($filters['unsupported_condition']);
        $remainingNegatives = preg_replace('/zemin\s*kat\s*(?:olmasin|istemiyorum|haric)|hisseli\s*(?:olmasin|istemiyorum|haric)/', '', $fold);
        if (preg_match('/olmasin|istemiyorum|haric/', $remainingNegatives)) $unsupported = true;
        if (preg_match('/bahce|havuz|otopark|manzara|asansor|esyali|krediye|cepheli|imarli|denize|okula|hastaneye/', $fold) && !preg_match('/onemli degil|sart degil|kosulunu kaldir/', $fold)) $unsupported = true;
        if (($filters !== $previous || $ambiguous || $unsupported) && $intent === 'help') $intent = 'search';
        $next = str_contains($fold, 'sonraki') || str_contains($fold, 'daha fazla') || str_contains($fold, 'devamini');

        return compact('intent', 'filters', 'ordinal', 'count', 'unsupported', 'next', 'ambiguous');
    }
}
