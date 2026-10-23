<?php

namespace App\Services\Assistant;

class ListingDraftTool
{
    public function prepare(string $message, array $filters): array
    {
        $draft = [];
        foreach (['city' => 'city_id', 'district' => 'district_id', 'type' => 'type', 'max_price' => 'price'] as $from => $to) {
            if (isset($filters[$from])) {
                $draft[$to] = $filters[$from];
            }
        }
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:metrekare|m²|m2)/iu', $message, $m)) {
            $draft['square_meters'] = (float) str_replace(',', '.', $m[1]);
        }
        $parts = array_filter([$filters['city'] ?? null, $filters['district'] ?? null, $filters['type'] ?? null, $filters['rooms'] ?? null, $filters['category'] ?? null]);
        if ($parts) {
            $draft['title'] = implode(' ', $parts);
        }
        // Preserve only what the user actually said. RichEditor receives plain escaped text.
        $draft['description'] = '<p>'.htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';

        return $draft;
    }
}
