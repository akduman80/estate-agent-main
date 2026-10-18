<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use ZipArchive;

/** Only blank, validated OOXML templates and whitelisted placeholder values. */
class NativeDocxTemplate
{
    private const NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private function package(string $bytes, callable $action): mixed
    {
        if (strlen($bytes) > 12 * 1024 * 1024 || ! str_starts_with($bytes, "PK\x03\x04")) throw new \RuntimeException('Geçerli DOCX gerekli.');
        $path = tempnam(sys_get_temp_dir(), 'zyd-docx-');
        if ($path === false) throw new \RuntimeException('Geçici dosya oluşturulamadı.');
        chmod($path, 0600);
        try {
            file_put_contents($path, $bytes);
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) throw new \RuntimeException('DOCX açılamadı.');
            try {
                $total = 0; $names = [];
                if ($zip->numFiles > 2000 || $zip->locateName('word/document.xml') === false) throw new \RuntimeException('DOCX yapısı geçersiz.');
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->statIndex($i); $name = $entry['name']; $total += $entry['size'];
                    if ($total > 50 * 1024 * 1024 || isset($names[$name]) || ($entry['encryption_method'] ?? 0) !== 0 || preg_match('~vbaProject|embeddings/|activeX/|\.\./|^/|\\\\~i', $name)) throw new \RuntimeException('Güvenli olmayan DOCX içeriği.');
                    $names[$name] = true;
                    if (str_ends_with($name, '.xml') || str_ends_with($name, '.rels')) {
                        $xml = $zip->getFromIndex($i);
                        if ($xml === false || preg_match('~<!DOCTYPE|<!ENTITY|TargetMode\s*=\s*["\x27]External|altChunk|macroEnabled|attachedTemplate|oleObject~i', $xml)) throw new \RuntimeException('Etkin veya dış bağlantılı DOCX reddedildi.');
                    }
                }
                $result = $action($zip);
            } finally { $zip->close(); }
            return $result === true ? file_get_contents($path) : $result;
        } finally { if (is_file($path)) unlink($path); }
    }

    private function paragraphs(ZipArchive $zip, callable $action): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! preg_match('~^word/(document|header[0-9]+|footer[0-9]+)\.xml$~D', $name)) continue;
            $doc = new DOMDocument();
            if (! $doc->loadXML($zip->getFromIndex($i), LIBXML_NONET)) throw new \RuntimeException('DOCX XML geçersiz.');
            $xpath = new DOMXPath($doc); $xpath->registerNamespace('w', self::NS);
            $changed = false;
            foreach ($xpath->query('//w:p[not(ancestor::w:p)]') as $paragraph) $changed = $action($xpath, $paragraph) || $changed;
            if ($changed && ! $zip->addFromString($name, $doc->saveXML())) throw new \RuntimeException('Çalışma kopyası yazılamadı.');
        }
    }

    public function fields(string $source): array
    {
        return $this->package($source, function ($zip) {
            $keys = [];
            $this->paragraphs($zip, function ($xpath, $p) use (&$keys) {
                $text = ''; foreach ($xpath->query('.//w:t', $p) as $node) $text .= $node->textContent;
                preg_match_all('/\{\{([a-zA-Z][a-zA-Z0-9_]{0,63})\}\}/', $text, $matches);
                foreach ($matches[1] as $key) $keys[$key] = true;
                return false;
            });
            if (count($keys) > 200) throw new \RuntimeException('En fazla 200 alan desteklenir.');
            return array_keys($keys);
        });
    }

    public function fill(string $source, array $values): string
    {
        $keys = $this->fields($source);
        if (array_diff(array_keys($values), $keys)) throw new \RuntimeException('Bilinmeyen belge alanı.');
        foreach ($values as $value) if (! is_string($value) || mb_strlen($value) > 2000 || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $value)) throw new \RuntimeException('Alan değeri geçersiz.');
        return $this->package($source, function ($zip) use ($values) {
            $this->paragraphs($zip, function ($xpath, $p) use ($values) {
                $nodes = iterator_to_array($xpath->query('.//w:t', $p));
                $text = ''; foreach ($nodes as $node) $text .= $node->textContent;
                if (! preg_match_all('/\{\{([a-zA-Z][a-zA-Z0-9_]{0,63})\}\}/', $text, $matches, PREG_OFFSET_CAPTURE)) return false;
                foreach (array_reverse($matches[0], true) as $index => [$marker, $start]) {
                    $end = $start + strlen($marker); $cursor = 0; $inserted = false;
                    foreach ($nodes as $node) {
                        $value = $node->textContent; $length = strlen($value); $next = $cursor + $length;
                        if ($next > $start && $cursor < $end) {
                            $prefix = substr($value, 0, max(0, $start - $cursor));
                            $suffix = substr($value, min($length, max(0, $end - $cursor)));
                            $replacement = $inserted ? '' : ($values[$matches[1][$index][0]] ?? '');
                            while ($node->firstChild) $node->removeChild($node->firstChild);
                            $node->appendChild($node->ownerDocument->createTextNode($prefix.$replacement.$suffix));
                            $node->setAttribute('xml:space', 'preserve'); $inserted = true;
                        }
                        $cursor = $next;
                    }
                }
                foreach ($nodes as $node) {
                    $lines = explode("\n", str_replace("\r", '', $node->textContent));
                    if (count($lines) < 2) continue;
                    while ($node->firstChild) $node->removeChild($node->firstChild);
                    $node->appendChild($node->ownerDocument->createTextNode(array_shift($lines)));
                    $anchor = $node;
                    foreach ($lines as $line) {
                        $br = $node->ownerDocument->createElementNS(self::NS,'w:br');
                        $textNode = $node->ownerDocument->createElementNS(self::NS,'w:t');
                        $textNode->setAttribute('xml:space','preserve');$textNode->appendChild($node->ownerDocument->createTextNode($line));
                        $anchor->parentNode->insertBefore($br,$anchor->nextSibling);$br->parentNode->insertBefore($textNode,$br->nextSibling);$anchor=$textNode;
                    }
                }
                return true;
            });
            return true;
        });
    }
}
