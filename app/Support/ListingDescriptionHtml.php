<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class ListingDescriptionHtml
{
    private const ALLOWED_TAGS = [
        'a',
        'blockquote',
        'br',
        'code',
        'del',
        'em',
        'h2',
        'h3',
        'li',
        'ol',
        'p',
        'pre',
        'span',
        'strong',
        'u',
        'ul',
    ];

    public static function render(?string $html): string
    {
        if (blank($html)) {
            return e('Bu ilan için henüz açıklama eklenmemiş.');
        }

        if ($html === strip_tags($html)) {
            return nl2br(e($html));
        }

        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return e(strip_tags($html));
        }

        self::cleanChildren($body);

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                self::cleanElement($child);
            }

            if ($child->parentNode) {
                self::cleanChildren($child);
            }
        }
    }

    private static function cleanElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
            $element->parentNode?->removeChild($element);

            return;
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            self::unwrap($element);

            return;
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if ($tag === 'a' && $name === 'href') {
                if (self::isAllowedHref($attribute->value)) {
                    continue;
                }
            }

            if ($tag === 'span' && $name === 'style') {
                $style = self::cleanStyle($attribute->value);

                if ($style !== '') {
                    $element->setAttribute('style', $style);

                    continue;
                }
            }

            $element->removeAttribute($attribute->name);
        }

        if ($tag === 'a') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function cleanStyle(string $style): string
    {
        $allowed = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $color = self::normalizeColor($value);

            if ($property === 'color' && $color) {
                $allowed[] = 'color: '.$color;
            }

            if ($property === 'background-color' && $color) {
                $allowed[] = 'background-color: '.$color;
            }

            if ($property === 'font-family') {
                $fontFamily = self::cleanFontFamily($value);

                if ($fontFamily) {
                    $allowed[] = 'font-family: '.$fontFamily;
                }
            }

            if ($property === 'font-size') {
                $fontSize = self::cleanFontSize($value);

                if ($fontSize) {
                    $allowed[] = 'font-size: '.$fontSize;
                }
            }

            if ($property === 'text-align') {
                $textAlign = self::cleanTextAlign($value);

                if ($textAlign) {
                    $allowed[] = 'text-align: '.$textAlign;
                }
            }
        }

        return implode('; ', array_unique($allowed));
    }

    private static function normalizeColor(string $value): ?string
    {
        $value = strtolower(trim($value));

        if (preg_match('/^#[0-9a-f]{6}$/', $value)) {
            return $value;
        }

        if (preg_match('/^#[0-9a-f]{3}$/', $value)) {
            return '#'.implode('', array_map(fn (string $char): string => $char.$char, str_split(substr($value, 1))));
        }

        if (preg_match('/^rgba?\((\d+),\s*(\d+),\s*(\d+)/', $value, $matches)) {
            return sprintf('#%02x%02x%02x', (int) $matches[1], (int) $matches[2], (int) $matches[3]);
        }

        return null;
    }

    private static function cleanFontFamily(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 120) {
            return null;
        }

        if (! preg_match('/^[\pL\pN\s"\',.\-]+$/u', $value)) {
            return null;
        }

        return $value;
    }

    private static function cleanFontSize(string $value): ?string
    {
        $value = strtolower(trim($value));

        if (! preg_match('/^(\d{1,3}(?:\.\d{1,2})?)px$/', $value, $matches)) {
            return null;
        }

        $size = (float) $matches[1];

        if ($size <= 0 || $size > 200) {
            return null;
        }

        return $matches[1].'px';
    }

    private static function cleanTextAlign(string $value): ?string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['left', 'center', 'right'], true) ? $value : null;
    }

    private static function isAllowedHref(string $href): bool
    {
        $href = trim($href);

        return str_starts_with($href, '/')
            || str_starts_with($href, '#')
            || preg_match('/^(https?:|mailto:|tel:)/i', $href) === 1;
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
