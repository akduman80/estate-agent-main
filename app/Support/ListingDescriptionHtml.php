<?php

namespace App\Support;

class ListingDescriptionHtml
{
    public static function render(?string $html): string
    {
        return blank($html)
            ? e('Bu ilan için henüz açıklama eklenmemiş.')
            : RichContentHtml::render($html);
    }
}
