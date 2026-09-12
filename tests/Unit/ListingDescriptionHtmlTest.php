<?php

namespace Tests\Unit;

use App\Support\ListingDescriptionHtml;
use PHPUnit\Framework\TestCase;

class ListingDescriptionHtmlTest extends TestCase
{
    public function test_it_preserves_allowed_listing_text_styles(): void
    {
        $html = '<p><span style="font-family: &quot;Times New Roman&quot;; font-size: 14.5px; color: rgb(255, 0, 17); background-color: #abc; text-align: center;">Metin</span></p>';

        $rendered = ListingDescriptionHtml::render($html);

        $this->assertStringContainsString('font-family: "Times New Roman"', $rendered);
        $this->assertStringContainsString('font-size: 14.5px', $rendered);
        $this->assertStringContainsString('color: #ff0011', $rendered);
        $this->assertStringContainsString('background-color: #aabbcc', $rendered);
        $this->assertStringContainsString('text-align: center', $rendered);
    }

    public function test_it_rejects_unsafe_listing_text_styles(): void
    {
        $html = '<p><span style="font-family: Bad; color: expression(alert(1)); font-size: 999px; text-align: justify; background-image: url(javascript:alert(1));">Metin</span></p>';

        $rendered = ListingDescriptionHtml::render($html);

        $this->assertStringContainsString('font-family: Bad', $rendered);
        $this->assertStringNotContainsString('expression', $rendered);
        $this->assertStringNotContainsString('999px', $rendered);
        $this->assertStringNotContainsString('justify', $rendered);
        $this->assertStringNotContainsString('background-image', $rendered);
    }
}
