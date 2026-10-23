<?php

namespace App\Services\Assistant;

use App\Models\Setting;
use Illuminate\Support\Str;

class KnowledgeTool
{
    public function sources(string $message): array
    {
        $text = Str::ascii(Str::lower($message), 'tr');
        $sources = Setting::get('assistant_sources', $this->defaults());
        $sources = array_values(array_filter(is_array($sources) ? $sources : [], function ($source) {
            if (! is_array($source) || ! is_string($source['url'] ?? null)) {
                return false;
            }$host = parse_url($source['url'] ?? '', PHP_URL_HOST);

            return is_array($source) && filled($source['title'] ?? null) && filled($source['checked_at'] ?? null) && parse_url($source['url'] ?? '', PHP_URL_SCHEME) === 'https' && is_string($host) && preg_match('/(^|\.)gov\.tr$/i', $host);
        }));
        if (str_contains($text, 'eids')) {
            return array_values(array_filter($sources, fn ($source) => str_contains(Str::ascii(Str::lower($source['title']), 'tr'), 'eids')));
        }

        return $sources;
    }

    public function defaults(): array
    {
        return [
            ['title' => 'GİB — Tapu harcı', 'url' => CalculatorTool::SOURCE, 'checked_at' => '2026-10-23'],
            ['title' => 'GİB — Değer artışı kazancı', 'url' => 'https://gib.gov.tr/vergi-konulari/1_bireysel/7_diger_kazanc_ve_iratlar/7', 'checked_at' => '2026-10-23'],
            ['title' => 'Ticaret Bakanlığı — EİDS açıklaması (13.06.2026)', 'url' => 'https://ticaret.gov.tr/haberler/ticaret-bakanligi-sosyal-medyadaki-sahte-ilanlara-gecit-vermiyor-eids-kurallarina-aykiri-paylasimlara-karsi-yaptirimlar-kararlilikla-uygulaniyor', 'checked_at' => '2026-10-23'],
        ];
    }
}
