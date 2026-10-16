<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OsmaniyePrayerTimesService
{
    public const SOURCE_URL = 'https://namazvakitleri.diyanet.gov.tr/tr-TR/9788/osmaniye-icin-namaz';

    public function today(): array
    {
        $today = now('Europe/Istanbul')->locale('tr');
        $date = $today->toDateString();
        $label = $today->translatedFormat('d F Y l');

        // A separate date key prevents yesterday's values being shown on a new day.
        // File storage uses the existing application cache, with no database changes.
        $times = Cache::store('file')->remember('osmaniye-prayers-'.$date, 300, function () use ($label) {
            try {
                $response = Http::connectTimeout(2)->timeout(3)->get(self::SOURCE_URL);
                if (! $response->successful()) {
                    return [];
                }

                $document = new DOMDocument;
                $previous = libxml_use_internal_errors(true);
                try {
                    $document->loadHTML('<?xml encoding="UTF-8">'.$response->body(), LIBXML_NONET);
                } finally {
                    libxml_clear_errors();
                    libxml_use_internal_errors($previous);
                }

                foreach ((new DOMXPath($document))->query('//tr') as $row) {
                    $cells = $row->getElementsByTagName('td');
                    if ($cells->length !== 8 || trim($cells->item(0)->textContent) !== $label) {
                        continue;
                    }

                    $values = [];
                    for ($index = 2; $index < 8; $index++) {
                        $value = trim($cells->item($index)->textContent);
                        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value)) {
                            return [];
                        }
                        $values[] = $value;
                    }

                    return array_combine(['İmsak', 'Güneş', 'Öğle', 'İkindi', 'Akşam', 'Yatsı'], $values);
                }
            } catch (\Throwable) {
                // No guessed values or stale-day fallback when the source is unavailable.
            }

            return [];
        });

        return compact('date', 'label', 'times');
    }
}
