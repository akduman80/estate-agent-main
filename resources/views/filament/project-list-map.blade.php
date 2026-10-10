@include('partials.zyd-map-assets')
<div class="mb-6" wire:ignore data-zyd-map data-config="{{ json_encode(['projects' => \App\Services\SharedMapRecords::records(true)], JSON_UNESCAPED_UNICODE) }}"></div>
