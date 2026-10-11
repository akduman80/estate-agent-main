<?php

namespace Tests\Unit;

use App\Support\RichContentHtml;
use PHPUnit\Framework\TestCase;

class RichContentHtmlTest extends TestCase
{
    public function test_formatted_content_preserves_safe_images_and_text_styles(): void
    {
        $html = '<figure><img src="/storage/posts/photo.jpg" alt="Fotoğraf" onerror="alert(1)"><figcaption>Başlık</figcaption></figure><p><span style="color: #abc; text-align: center; background-color: #123">Metin</span></p>';
        $output = RichContentHtml::render($html);

        $this->assertStringContainsString('src="/storage/posts/photo.jpg"', $output);
        $this->assertStringContainsString('<figcaption>Başlık</figcaption>', $output);
        $this->assertStringContainsString('color: #aabbcc', $output);
        $this->assertStringContainsString('text-align: center', $output);
        $this->assertStringContainsString('<span style="background-color: #112233">Metin</span>', $output);
        $this->assertStringNotContainsString('onerror', $output);
    }

    public function test_unsafe_image_sources_and_scripts_are_removed(): void
    {
        $output = RichContentHtml::render('<img src="javascript:alert(1)"><script>alert(1)</script><p>Güvenli</p>');

        $this->assertStringNotContainsString('javascript:', $output);
        $this->assertStringNotContainsString('script', $output);
        $this->assertStringContainsString('Güvenli', $output);
        $this->assertSame('', RichContentHtml::render(null));
    }
}
