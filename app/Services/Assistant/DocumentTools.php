<?php

namespace App\Services\Assistant;

use App\Models\Setting;
use App\Support\DocumentTemplateStore;
use Illuminate\Support\Str;

class DocumentTools
{
    public function catalog(): array
    {
        $documents = collect(Setting::get('user_documents', []))
            ->filter(fn ($d) => is_array($d) && ($d['is_active'] ?? true) && filled($d['title'] ?? null) && filled($d['file'] ?? null))
            ->sortBy(fn ($d) => (int) ($d['sort'] ?? 0))->values()
            ->map(fn ($d, $index) => ['title' => $d['title'], 'url' => route('documents.download', ['index' => $index]), 'fields' => $this->schema((string) $d['file'])])->all();
        foreach (app(DocumentTemplateStore::class)->publishedCatalog() as $d) {
            $documents[] = ['title' => $d['title'], 'url' => $d['url'], 'fields' => $this->metadata($d['mapping']['fields'] ?? [])];
        }

        return $documents;
    }

    private function metadata(array $fields): array
    {
        return array_values(array_map(fn ($field) => array_intersect_key($field, array_flip(['key','label','kind','required','dependsOn'])), array_filter($fields, fn ($field) => is_array($field) && ! ($field['locked'] ?? false))));
    }

    private function schema(string $file): array
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        if (! $disk->exists($file) || $disk->size($file) > 10000000) return [];
        $hash = hash('sha256', $disk->get($file));
        $path = resource_path('data/assistant-document-fields.json');
        $registry = is_file($path) ? json_decode(file_get_contents($path), true) : [];
        foreach ($registry['sources'] ?? [] as $source => $expected) {
            if (! is_file(base_path($source)) || ! hash_equals($expected, hash('sha256', str_replace("\r\n", "\n", file_get_contents(base_path($source)))))) return [];
        }
        return $this->metadata($registry['templates'][$hash] ?? []);
    }

    public function kind(string $message): ?string
    {
        $text = Str::ascii(Str::lower($message), 'tr');

        return match (true) {
            str_contains($text, 'tahliye') => 'eviction',
            str_contains($text, 'is yeri kira'),str_contains($text, 'isyeri kira') => 'workplace-lease',
            str_contains($text, 'kira sozlesme') => 'lease',
            str_contains($text, 'on odemeli') => 'prepaid',
            str_contains($text, 'satis vaadi') => 'sale-promise',
            str_contains($text, 'senet') => 'promissory',
            str_contains($text, 'kapora') => 'deposit',
            default => null,
        };
    }

    public function matching(array $catalog, ?string $kind): array
    {
        if (! $kind) {
            return $catalog;
        }

        return array_values(array_filter($catalog, function ($document) use ($kind) {
            $title = Str::ascii(Str::lower($document['title']), 'tr');

            return match ($kind) {
                'eviction' => str_contains($title, 'tahliye'),
                'workplace-lease' => str_contains($title, 'kira sozlesme') && (str_contains($title, 'is yeri') || str_contains($title, 'isyeri')),
                'lease' => str_contains($title, 'kira sozlesme') && ! str_contains($title, 'is yeri') && ! str_contains($title, 'isyeri'),
                'prepaid' => str_contains($title, 'on odemeli'),
                'sale-promise' => str_contains($title, 'satis vaadi') && ! str_contains($title, 'on odemeli'),
                'promissory' => str_contains($title, 'senet'),
                'deposit' => str_contains($title, 'kapora'),
                default => false,
            };
        }));
    }

    public function prepare(string $message): array
    {
        $fields = [];
        foreach (['landlord' => '(?:ev sahibi|kiraya veren|kiralayan)', 'tenant' => '(?:kiracı|kiraci)'] as $key => $pattern) {
            if (preg_match('/'.$pattern.'\s+([^,;\n.]{2,100})/iu', $message, $m)) {
                $fields[$key] = trim($m[1]);
            }
        }
        if (preg_match('/(?:aylık kira|aylik kira|kira)\s+(\d+(?:[.,]\d+)?)\s*(bin|milyon)?\s*(?:tl|lira)/iu', $message, $m)) {
            $amount = (float) str_replace(',', '.', $m[1]) * match (Str::lower($m[2] ?? '')) {
                'bin' => 1000, 'milyon' => 1000000, default => 1
            };
            if ($amount > 0 && $amount <= 100000000) {
                $fields['monthlyRent'] = (string) $amount;
            }
        }
        $months = ['ocak' => 1, 'subat' => 2, 'mart' => 3, 'nisan' => 4, 'mayis' => 5, 'haziran' => 6, 'temmuz' => 7, 'agustos' => 8, 'eylul' => 9, 'ekim' => 10, 'kasim' => 11, 'aralik' => 12];
        $fold = Str::ascii(Str::lower($message), 'tr');
        if (preg_match('/baslangic(?: tarihi)?\s+(\d{1,2})\s+('.implode('|', array_keys($months)).')\s+(\d{4})/', $fold, $m) && checkdate($months[$m[2]], (int) $m[1], (int) $m[3])) {
            $fields['startDate'] = sprintf('%02d.%02d.%04d', $m[1], $months[$m[2]], $m[3]);
        }
        foreach (['city' => 'il', 'county' => 'ilçe', 'district' => 'mahalle', 'street' => 'sokak', 'number' => 'bina numarası', 'duration' => 'kira süresi', 'paymentMethod' => 'ödeme şekli', 'landlordAddress' => 'kiraya veren adresi', 'tenantAddress' => 'kiracı adresi', 'inventory' => 'demirbaşlar'] as $key => $label) {
            if (preg_match('/(?:^|[,;\n])\s*'.preg_quote($label, '/').'\s*[:=]\s*([^,;\n]{1,200})/iu', $message, $m)) $fields[$key] = trim($m[1]);
        }

        return $fields;
    }
}
