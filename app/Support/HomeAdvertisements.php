<?php

namespace App\Support;

use App\Models\Advertisement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class HomeAdvertisements
{
    public const WIDTH = 1600;

    public const HEIGHT = 240;

    public const MAX_KILOBYTES = 8192;

    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public const DISPLAY_SECONDS = 5;

    public const TRANSITION_MS = 640;

    public const TIMEZONE = 'Europe/Istanbul';

    public static function placements(): array
    {
        $options = [];
        for ($order = 1; $order <= 99; $order++) {
            $options[sprintf('home%02d', $order)] = $order.'. sıra';
        }

        return $options;
    }

    public static function defaults(): array
    {
        return [
            ['placement' => 'home01', 'file' => 'reklam-01-fincan-kupa.jpg', 'label' => 'Fincan ve kupa baskısı', 'message' => 'Merhaba, fincan ve kupa baskısı hakkında bilgi almak istiyorum.'],
            ['placement' => 'home02', 'file' => 'reklam-02-tisort-sweatshirt.jpg', 'label' => 'Tişört ve sweatshirt baskısı', 'message' => 'Merhaba, tişört ve sweatshirt baskısı hakkında bilgi almak istiyorum.'],
            ['placement' => 'home03', 'file' => 'reklam-03-sapka-dtf.jpg', 'label' => 'Şapka ve DTF baskısı', 'message' => 'Merhaba, şapka ve DTF baskısı hakkında bilgi almak istiyorum.'],
        ];
    }

    public static function slides(): Collection
    {
        $positions = array_keys(self::placements());
        // Some installations only pull source on push. Keep the already-published
        // carousel visible until deploy or the authorized admin page imports it.
        // After import, disabling/deleting every record must never restore defaults.
        if (! Storage::disk('local')->exists('advertisements/home-imported-v1.json')
            && ! Advertisement::query()->whereIn('placement', $positions)->exists()) {
            return collect(self::defaults())->map(fn (array $ad): array => [
                'src' => asset('fe-assets/assets/img/advertisements/'.$ad['file']),
                'label' => $ad['label'],
                'destination' => 'https://wa.me/905303607769?text='.rawurlencode($ad['message']),
            ]);
        }

        // Resolve a position before publication filtering so an unpublished replacement
        // cannot reveal an older record in the same position.
        return Advertisement::query()->whereIn('placement', $positions)
            ->orderByDesc('id')->get()->unique('placement')->sortBy('placement')
            ->filter(fn (Advertisement $ad): bool => $ad->is_active
                && (! $ad->starts_at || $ad->starts_at->lte(now()))
                && (! $ad->ends_at || $ad->ends_at->gte(now())))
            ->map(fn (Advertisement $ad): array => [
                'src' => Storage::disk('public')->url($ad->image_path),
                'label' => $ad->label,
                'destination' => in_array(strtolower(parse_url($ad->target_url ?? '', PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
                    ? $ad->target_url : 'https://wa.me/905303607769',
            ])->values();
    }
}
