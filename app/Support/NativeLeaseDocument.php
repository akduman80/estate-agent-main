<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;
use Symfony\Component\Process\Process;
use ZipArchive;

/** Only the hash-pinned, existing blank lease template is accepted. */
class NativeLeaseDocument
{
    public const HASH = 'f7e7ea2e3d9a703650a8f6d55b7dec2f49c67fc574051607f076189309369924';
    public const KEYS = ['district', 'street', 'number', 'propertyType', 'landlord', 'landlordIdentity', 'landlordAddress', 'tenant', 'tenantIdentity', 'tenantAddress', 'startDate', 'duration', 'annualRent', 'monthlyRent', 'paymentMethod', 'usage', 'condition', 'inventory'];

    public function fill(string $source, array $values): string
    {
        if (hash('sha256', $source) !== self::HASH) throw new \RuntimeException('Unsupported source');
        $temporary = tempnam(sys_get_temp_dir(), 'zyd-lease-');
        if ($temporary === false) throw new \RuntimeException('Temporary file unavailable');
        chmod($temporary, 0600);
        try {
            file_put_contents($temporary, $source);
            $zip = new ZipArchive();
            if ($zip->open($temporary) !== true) throw new \RuntimeException('Invalid source');
            try {
                $doc = new DOMDocument();
                if (! $doc->loadXML($zip->getFromName('word/document.xml'), LIBXML_NONET)) throw new \RuntimeException('Invalid XML');
                $xpath = new DOMXPath($doc);
                $ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
                $xpath->registerNamespace('w', $ns);
                $rows = $xpath->query('//w:tbl/w:tr');
                if ($rows->length !== count(self::KEYS)) throw new \RuntimeException('Template changed');
                foreach (self::KEYS as $index => $key) {
                    $value = (string) ($values[$key] ?? '');
                    if ($value === '') continue;
                    $cell = $xpath->query('./w:tc', $rows->item($index))->item(1);
                    $paragraph = $xpath->query('./w:p', $cell)->item(0);
                    $run = $doc->createElementNS($ns, 'w:r');
                    $properties = $xpath->query('./w:pPr/w:rPr', $paragraph)->item(0);
                    if ($properties) $run->appendChild($properties->cloneNode(true));
                    foreach (explode("\n", str_replace("\r", '', $value)) as $lineIndex => $line) {
                        if ($lineIndex) $run->appendChild($doc->createElementNS($ns, 'w:br'));
                        $text = $doc->createElementNS($ns, 'w:t');
                        $text->setAttribute('xml:space', 'preserve');
                        $text->appendChild($doc->createTextNode($line));
                        $run->appendChild($text);
                    }
                    $paragraph->appendChild($run);
                }
                if (! $zip->addFromString('word/document.xml', $doc->saveXML())) throw new \RuntimeException('Cannot update working copy');
            } finally { $zip->close(); }
            return file_get_contents($temporary);
        } finally { if (is_file($temporary)) unlink($temporary); }
    }

    public function convert(string $docx): string
    {
        $runtime = storage_path('app/private/document-tools-runtime');
        $binary = $runtime.'/opt/libreoffice26.2/program/soffice';
        if (! is_executable($binary)) throw new \RuntimeException('Converter unavailable');
        $directory = sys_get_temp_dir().'/zyd-document-'.bin2hex(random_bytes(16));
        if (! mkdir($directory, 0700)) throw new \RuntimeException('Temporary directory unavailable');
        // A single conversion per host; no filled document or process output is logged.
        $lock = fopen(sys_get_temp_dir().'/zydemlak-document-render.lock', 'c');
        try {
            if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) throw new \RuntimeException('Converter busy');
            file_put_contents($directory.'/document.docx', $docx);
            chmod($directory.'/document.docx', 0600);
            $process = new Process([$binary, '-env:UserInstallation=file://'.$directory.'/profile', '--headless', '--convert-to', 'pdf:writer_pdf_Export', '--outdir', $directory, $directory.'/document.docx'], $directory, ['FONTCONFIG_FILE' => $runtime.'/document-fonts.conf', 'HOME' => $directory, 'TMPDIR' => $directory]);
            $process->setTimeout(35);
            $process->disableOutput();
            $process->mustRun();
            $path = $directory.'/document.pdf';
            if (! is_file($path) || filesize($path) > 8 * 1024 * 1024) throw new \RuntimeException('Invalid output');
            $pdf = file_get_contents($path);
            if (! str_starts_with($pdf, '%PDF-')) throw new \RuntimeException('Invalid PDF');
            return $pdf;
        } finally {
            if (is_resource($lock)) { flock($lock, LOCK_UN); fclose($lock); }
            $this->removeTemporaryDirectory($directory);
        }
    }

    private function removeTemporaryDirectory(string $directory): void
    {
        if (! is_dir($directory)) return;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir() && ! $file->isLink()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
