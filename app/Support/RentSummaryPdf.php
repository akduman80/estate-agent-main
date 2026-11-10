<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/** Small self-contained A4 summary; GD/Unicode fonts already ship with this app. */
class RentSummaryPdf
{
    public function image(array $result): \GdImage
    {
        $previousDirectory = getcwd();
        chdir(base_path());
        try {
            $image = imagecreatetruecolor(1240, 1754);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            $ink = imagecolorallocate($image, 23, 37, 84);
            $accent = imagecolorallocate($image, 234, 88, 12);
            // Relative ASCII paths also work with Windows FreeType in Unicode user folders.
            $font = './public/vendor/pdfjs/standard_fonts/LiberationSans-Regular.ttf';
            $bold = './public/vendor/pdfjs/standard_fonts/LiberationSans-Bold.ttf';
            if (PHP_OS_FAMILY === 'Windows') {
                $process = new Process(['cmd.exe', '/d', '/c', 'for %I in (.) do @echo %~sI'], base_path());
                $process->setTimeout(5)->mustRun();
                $fontRoot = trim($process->getOutput());
                $font = $fontRoot.'/public/vendor/pdfjs/standard_fonts/LiberationSans-Regular.ttf';
                $bold = $fontRoot.'/public/vendor/pdfjs/standard_fonts/LiberationSans-Bold.ttf';
            }
            if (! is_file($font) || ! is_file($bold) || imagettfbbox(25, 0, $font, 'Test') === false) {
                throw new \RuntimeException('PDF font unavailable.');
            }
            $y = 100;
            $write = function (string $text, int $size = 25, bool $heading = false) use ($image, $ink, $accent, $font, $bold, &$y) {
                $face = $heading ? $bold : $font;
                $line = '';
                $width = fn (string $value) => imagettfbbox($size, 0, $face, $value)[2];
                foreach (preg_split('/\s+/u', $text) as $word) {
                    $candidate = $line === '' ? $word : $line.' '.$word;
                    if ($line !== '' && $width($candidate) > 1080) {
                        imagettftext($image, $size, 0, 80, $y, $heading ? $accent : $ink, $face, $line);
                        $y += $size + 13;
                        $line = '';
                    }
                    if ($line !== '') {
                        $line .= ' ';
                    }
                    foreach (mb_str_split($word) as $character) {
                        if ($width($line.$character) > 1080) {
                            imagettftext($image, $size, 0, 80, $y, $heading ? $accent : $ink, $face, $line);
                            $y += $size + 13;
                            $line = '';
                        }
                        $line .= $character;
                    }
                }
                if ($line !== '') {
                    imagettftext($image, $size, 0, 80, $y, $heading ? $accent : $ink, $face, $line);
                    $y += $size + 13;
                }
                $y += 14;
            };
            $write('ZYDEMLAK', 36, true);
            $write('Kira Artışı Hesaplama Özeti', 30, true);
            $write('Yenileme tarihi: '.$result['date'].'  |  '.($result['kind'] === 'home' ? 'Konut' : 'Çatılı işyeri'));
            foreach (['cpi' => 'TÜFE 12 aylık ortalama', 'cap' => 'Yasal oran üst sınırı', 'applied' => 'Uygulanan oran'] as $key => $label) {
                $write($label.': %'.number_format($result[$key], 2, ',', '.'));
            }
            foreach (['rent' => 'Mevcut aylık kira', 'increase' => 'Artış tutarı / aylık fark', 'new_rent' => 'Yeni aylık kira', 'annual_difference' => 'Yıllık fark'] as $key => $label) {
                $write($label.': '.number_format($result[$key], 2, ',', '.').' TL');
            }
            if ($result['ceiling_only']) {
                $write('Sözleşme şartı belirtilmedi: Sonuç yalnız üst sınır örneğidir.', 24, true);
            }
            $write('TÜİK yayın tarihi: '.$result['published_on'], 22);
            $write('Dönem kaynağı: '.$result['source'], 19);
            $write('Konutlarda 11.06.2022–01.07.2024 arası yenilemelerde geçici %25 sınırı dikkate alınır. Bu sınır çatılı işyerlerine uygulanmaz.', 22);
            $write('Bilgilendirme amaçlıdır. Sözleşme koşulları, beş yılı aşan ilişkiler, dövizli kira ve özel hukuki durumlar için uzman teyidi gerekir. Kesin mahkeme sonucu veya resmî belge değildir.', 22);
            if ($y > 1674) {
                throw new \RuntimeException('PDF content exceeded the page.');
            }

            return $image;
        } finally {
            if ($previousDirectory !== false) {
                chdir($previousDirectory);
            }
        }
    }

    public function make(array $result): string
    {
        $image = $this->image($result);
        ob_start();
        try {
            imagejpeg($image, null, 92);
            $jpeg = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        $content = "q\n595 0 0 842 0 0 cm\n/Im0 Do\nQ\n";
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /XObject /Subtype /Image /Width 1240 /Height 1754 /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg)." >>\nstream\n".$jpeg."\nendstream",
            '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream',
        ];
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset)."\n";
        }

        return $pdf."trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
    }
}
