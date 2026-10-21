<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/** Stores blank templates only. Completed documents never reach this store. */
class DocumentTemplateStore
{
    public function __construct(private ?string $directory = null, private ?string $publishedDirectory = null) {}

    private function root(): string { return $this->directory ?? storage_path('app/private/document-templates'); }
    private function published(): string { return $this->publishedDirectory ?? public_path('document-templates'); }

    public function all(): array
    {
        $file = $this->root().'/catalog.json';
        return is_file($file) ? json_decode(file_get_contents($file), true, 64, JSON_THROW_ON_ERROR) : [];
    }

    public function publishedCatalog(): array
    {
        $path = $this->published().'/catalog.json';
        if (! is_file($path)) return [];
        try { return json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { return []; } // A damaged optional catalog must not break the existing home page.
    }

    private function atomic(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path), 0750);
        $temporary = $path.'.'.bin2hex(random_bytes(8)).'.tmp';
        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || ! rename($temporary, $path)) throw new \RuntimeException('Şablon kaydedilemedi.');
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    private function locked(callable $action): mixed
    {
        File::ensureDirectoryExists($this->root(), 0700);
        $lock = fopen($this->root().'/catalog.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX)) throw new \RuntimeException('Şablon kilidi alınamadı.');
        try { return $action(); } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    public function validateMapping(array $mapping): void
    {
        $fail = fn () => throw ValidationException::withMessages(['mapping' => 'Alan eşlemesi geçersiz. Sayfa, koordinat ve alan türlerini kontrol edin.']);
        $sizes = $mapping['pageSizes'] ?? [];
        if (! is_array($sizes) || ! array_is_list($sizes) || ! is_array($mapping['fields'] ?? null) || ! array_is_list($mapping['fields']) || array_diff(array_keys($mapping), ['pageSizes','fields'])) $fail();
        if (count($sizes) < 1 || count($sizes) > 100 || count($mapping['fields']) > 200) $fail();
        foreach ($sizes as $size) if (! is_array($size) || count($size) !== 2 || ! is_numeric($size[0] ?? null) || ! is_numeric($size[1] ?? null) || min($size) < 72 || max($size) > 3000) $fail();
        $byKey = array_column($mapping['fields'], null, 'key');
        foreach ($mapping['fields'] as $field) {
            if (! isset($field['dependsOn'])) continue;
            $parentKey = $field['dependsOn'];
            if (! is_string($parentKey) || ! isset($byKey[$parentKey])) $fail();
            $expected = match ($field['kind'] ?? '') { 'district' => 'city', 'neighborhood' => 'district', default => null };
            if ($expected === null || ($byKey[$parentKey]['kind'] ?? '') !== $expected) $fail();
        }
        $keys = [];
        foreach ($mapping['fields'] ?? [] as $field) {
            if (! is_array($field) || array_diff(array_keys($field), ['key','label','kind','required','locked','microphone','positions','pages','options','dependsOn'])) $fail();
            if (isset($field['options'])) {
                if (! is_array($field['options']) || ! array_is_list($field['options']) || count($field['options']) > 100) $fail();
                foreach ($field['options'] as $option) if (! is_string($option) || trim($option) === '' || mb_strlen($option) > 120) $fail();
            }
            if (isset($field['pages'])) {
                if (! is_array($field['pages']) || ! array_is_list($field['pages']) || count($field['pages']) < 1 || count($field['pages']) > count($sizes)) $fail();
                foreach ($field['pages'] as $page) if (! is_int($page) || ! isset($sizes[$page - 1])) $fail();
            }
            $key = $field['key'] ?? '';
            if (! is_string($key) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $key) || in_array($key, $keys, true)) $fail();
            $keys[] = $key;
            if (! is_string($field['label'] ?? null) || mb_strlen($field['label']) > 120 || ! in_array($field['kind'] ?? '', ['text','name','identity','phone','email','address','city','district','neighborhood','date','amount','description'], true)) $fail();
            if (! is_array($field['positions'] ?? null) || ! array_is_list($field['positions']) || count($field['positions']) < 1 || count($field['positions']) > 100 || (($field['locked'] ?? false) && ($field['required'] ?? false))) $fail();
            foreach (['required', 'locked', 'microphone'] as $flag) if (isset($field[$flag]) && ! is_bool($field[$flag])) $fail();
            foreach ($field['positions'] as $p) {
                if (! is_array($p) || array_diff(array_keys($p), ['page','x','y','width','height','fontSize','fontFamily','bold','italic'])) $fail();
                foreach (['bold','italic'] as $flag) if (isset($p[$flag]) && ! is_bool($p[$flag])) $fail();
                $page = $p['page'] ?? 0; if (! is_int($page) || ! isset($sizes[$page - 1])) $fail();
                foreach (['x','y','width','height','fontSize'] as $number) if (! is_numeric($p[$number] ?? null) || ! is_finite((float) $p[$number])) $fail();
                [$w, $h] = $sizes[$page - 1];
                if ($p['x'] < 0 || $p['y'] < 0 || $p['width'] <= 0 || $p['height'] <= 0 || $p['x'] + $p['width'] > $w || $p['y'] + $p['fontSize'] > $h || $p['y'] - $p['height'] < -1 || $p['fontSize'] < 8 || $p['fontSize'] > 36 || ($p['fontFamily'] ?? 'LiberationSans') !== 'LiberationSans') $fail();
            }
        }
    }

    public function save(string $title, string $category, string $format, string $bytes, array $mapping, ?string $id = null): array
    {
        $preview = null;
        if (strlen($bytes) > 25 * 1024 * 1024 || strlen($bytes) < 8) throw ValidationException::withMessages(['upload' => 'Dosya boyutu geçersiz.']);
        if ($format === 'pdf') {
            if (! str_starts_with($bytes, '%PDF-') || preg_match('~/JavaScript|/JS\b|/Launch\b|/EmbeddedFile|/Encrypt\b~', $bytes)) throw ValidationException::withMessages(['upload' => 'PDF geçersiz, şifreli veya etkin içerik barındırıyor.']);
            $this->validateMapping($mapping);
        } elseif ($format !== 'docx' || ! str_starts_with($bytes, "PK\x03\x04")) throw ValidationException::withMessages(['upload' => 'Yalnız PDF/DOCX kabul edilir.']);
        else {
            if (! class_exists(\ZipArchive::class)) throw ValidationException::withMessages(['upload' => 'Bu sunucuda DOCX doğrulaması için ZIP desteği yok. PDF kullanın.']);
            $temp = tempnam(sys_get_temp_dir(), 'document-template-');
            try {
                file_put_contents($temp, $bytes);
                $zip = new \ZipArchive();
                if ($zip->open($temp) !== true) throw ValidationException::withMessages(['upload' => 'DOCX açılamadı.']);
                try {
                    $total = 0;
                    if ($zip->locateName('word/document.xml') === false || $zip->numFiles > 2000) throw ValidationException::withMessages(['upload' => 'Geçerli DOCX gerekli.']);
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entry = $zip->statIndex($i); $total += $entry['size'];
                        if ($total > 50 * 1024 * 1024 || preg_match('~vbaProject|embeddings/|\.\./~i', $entry['name'])) throw ValidationException::withMessages(['upload' => 'Etkin içerik veya aşırı büyük DOCX reddedildi.']);
                        if (str_ends_with($entry['name'], '.rels') && preg_match('/TargetMode\s*=\s*["\x27]External/i', $zip->getFromIndex($i))) throw ValidationException::withMessages(['upload' => 'Dış bağlantılı DOCX kabul edilmiyor.']);
                    }
                } finally { $zip->close(); }
            } finally { if (is_file($temp)) unlink($temp); }
        }
        if ($format === 'docx') {
            try {
                $keys = app(NativeDocxTemplate::class)->fields($bytes);
                $mapped = array_column($mapping['fields'] ?? [], 'key');
                if (array_diff($keys, $mapped) || array_diff($mapped, $keys) || count($mapped) !== count(array_unique($mapped))) throw new \RuntimeException('DOCX alan anahtarları eşleşmiyor.');
                $validation = ['pageSizes' => $mapping['pageSizes'] ?? [[595,842]], 'fields' => []];
                foreach ($mapping['fields'] ?? [] as $field) { $field['positions'] = [['page'=>1,'x'=>10,'y'=>800,'width'=>100,'height'=>20,'fontSize'=>11]]; $validation['fields'][] = $field; }
                $this->validateMapping($validation);
                $preview = app(NativeLeaseDocument::class)->convert($bytes);
            } catch (\Throwable) { throw ValidationException::withMessages(['upload' => 'DOCX doğrulanamadı veya ön izleme oluşturulamadı. Alan işaretlerini ve dönüştürücüyü kontrol edin.']); }
        }
        if ($title === '' || mb_strlen($title) > 120 || mb_strlen($category) > 80) throw ValidationException::withMessages(['title' => 'Başlık veya kategori geçersiz.']);
        return $this->locked(function () use ($title, $category, $format, $bytes, $mapping, $id, $preview) {
            $catalog = $this->all();
            $id ??= bin2hex(random_bytes(16));
            if (! preg_match('/^[a-f0-9]{32}$/D', $id)) throw new \InvalidArgumentException('Geçersiz şablon.');
            $version = count($catalog[$id]['versions'] ?? []) + 1;
            $hash = hash('sha256', $bytes);
            $revision = compact('id', 'version', 'title', 'category', 'format', 'hash', 'mapping') + ['saved_at' => now()->toIso8601String()];
            $this->atomic($this->root()."/{$id}/{$version}.{$format}", $bytes);
            if ($preview !== null) {
                $revision['previewHash'] = hash('sha256', $preview);
                $this->atomic($this->root()."/{$id}/{$version}.preview.pdf", $preview);
            }
            $catalog[$id]['versions'][] = $revision;
            $catalog[$id]['active'] ??= null;
            $this->atomic($this->root().'/catalog.json', json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            return $revision;
        });
    }

    public function revision(string $id, int $version): array
    {
        $r = $this->all()[$id]['versions'][$version - 1] ?? null;
        abort_unless($r, 404);
        $bytes = file_get_contents($this->root()."/{$r['id']}/{$r['version']}.{$r['format']}");
        if (! hash_equals($r['hash'], hash('sha256', $bytes))) throw new \RuntimeException('Şablon özeti eşleşmiyor.');
        return [$r, $bytes];
    }

    public function publish(string $id, ?int $version): void
    {
        $this->locked(function () use ($id, $version) {
            $catalog = $this->all();
            if (! isset($catalog[$id])) abort(404);
            if ($version !== null) {
                $revision = $catalog[$id]['versions'][$version - 1] ?? null;
                if (! $revision || ! in_array($revision['format'], ['pdf','docx'], true)) throw ValidationException::withMessages(['upload' => 'Şablon biçimi geçersiz.']);
                if ($revision['format'] === 'docx' && ! isset($revision['previewHash'])) throw ValidationException::withMessages(['upload' => 'Eski DOCX taslağını yeni sürüm olarak kaydedip test edin.']);
                if ($revision['format'] === 'docx') foreach ($revision['mapping']['fields'] ?? [] as $field) {
                    if (empty($field['pages']) && empty($field['positions'])) throw ValidationException::withMessages(['mapping' => 'DOCX alanlarının geçtiği sayfaları eşleyip yeni sürümü test edin.']);
                }
                if ($revision['format'] === 'pdf') $this->validateMapping($revision['mapping']);
                if (empty($revision['mapping']['fields'])) throw ValidationException::withMessages(['mapping' => 'Yayınlamadan önce en az bir alan eşleyin.']);
                $source = $this->root()."/{$id}/{$version}.{$revision['format']}";
                if (! hash_equals($revision['hash'], hash_file('sha256', $source))) throw new \RuntimeException('Kaynak doğrulaması başarısız.');
                if ($revision['format'] === 'docx') {
                    $preview = $this->preview($id, $version);
                    $this->atomic($this->published()."/{$id}-{$version}.preview.pdf", $preview);
                }
                $this->atomic($this->published()."/{$id}-{$version}.{$revision['format']}", file_get_contents($source));
            }
            $catalog[$id]['active'] = $version;
            $public = [];
            foreach ($catalog as $entry) {
                if ($entry['active'] === null) continue;
                $r = $entry['versions'][$entry['active'] - 1];
                $public[] = $r + ['url' => '/document-templates/'.$r['id'].'-'.$r['version'].'.'.$r['format']];
                if ($r['format'] === 'docx') $public[array_key_last($public)]['previewUrl'] = '/document-templates/'.$r['id'].'-'.$r['version'].'.preview.pdf';
            }
            $this->atomic($this->published().'/catalog.json', json_encode($public, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $this->atomic($this->root().'/catalog.json', json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            // No user data; remove withdrawn public copies after updating discovery.
            $keep = array_map(fn ($r) => basename($r['url']), $public);
            foreach ($public as $r) if (isset($r['previewUrl'])) $keep[] = basename($r['previewUrl']);
            foreach (array_merge(glob($this->published().'/*.pdf') ?: [], glob($this->published().'/*.docx') ?: []) as $path) if (! in_array(basename($path), $keep, true)) unlink($path);
        });
    }

    public function preview(string $id, int $version): string
    {
        [$revision] = $this->revision($id, $version);
        abort_unless($revision['format'] === 'docx', 422);
        if (! isset($revision['previewHash'])) {
            [, $source] = $this->revision($id, $version);
            app(NativeDocxTemplate::class)->fields($source);
            return app(NativeLeaseDocument::class)->convert($source);
        }
        $bytes = file_get_contents($this->root()."/{$id}/{$version}.preview.pdf");
        if (! hash_equals($revision['previewHash'], hash('sha256', $bytes))) throw new \RuntimeException('Ön izleme doğrulanamadı.');
        return $bytes;
    }
}
