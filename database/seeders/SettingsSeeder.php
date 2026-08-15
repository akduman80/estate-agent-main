<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class SettingsSeeder extends Seeder
{
    /**
     * Seed the site's manageable settings.
     */
    public function run(): void
    {
        $documents = [
            [
                'title' => 'Kira Kontratı',
                'file' => 'user-documents/kira-kontrati.pdf',
                'sort' => 10,
                'is_active' => true,
            ],
            [
                'title' => 'Tahliye Taahhütnamesi',
                'file' => 'user-documents/tahliye-taahhutnamesi.pdf',
                'sort' => 20,
                'is_active' => true,
            ],
            [
                'title' => 'Yer Gösterme Belgesi',
                'file' => 'user-documents/yer-gosterme-belgesi.pdf',
                'sort' => 30,
                'is_active' => true,
            ],
        ];

        foreach ($documents as $document) {
            Storage::disk('public')->put(
                $document['file'],
                $this->makeSamplePdf($document['title'])
            );
        }

        Setting::updateOrCreate(
            ['key' => 'user_documents'],
            ['value' => $documents],
        );

        Setting::clearCache('user_documents');
    }

    private function makeSamplePdf(string $title): string
    {
        $text = $this->escapePdfText($title . ' - Ornek Dokuman');
        $stream = "BT /F1 20 Tf 72 740 Td ({$text}) Tj ET";

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
