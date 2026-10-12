@php
    $configuredAds = \App\Models\Advertisement::query()
        ->whereIn('placement', ['home01', 'home02', 'home03'])
        ->orderByDesc('id')->get()->unique('placement')->keyBy('placement');
    $defaults = [
        ['slot' => 'home01', 'file' => 'reklam-01-fincan-kupa.jpg', 'label' => 'Fincan ve kupa baskısı', 'message' => 'Merhaba, fincan ve kupa baskısı hakkında bilgi almak istiyorum.'],
        ['slot' => 'home02', 'file' => 'reklam-02-tisort-sweatshirt.jpg', 'label' => 'Tişört ve sweatshirt baskısı', 'message' => 'Merhaba, tişört ve sweatshirt baskısı hakkında bilgi almak istiyorum.'],
        ['slot' => 'home03', 'file' => 'reklam-03-sapka-dtf.jpg', 'label' => 'Şapka ve DTF baskısı', 'message' => 'Merhaba, şapka ve DTF baskısı hakkında bilgi almak istiyorum.'],
    ];
    $slides = collect($defaults)->map(function ($slide) use ($configuredAds) {
        $record = $configuredAds->get($slide['slot']);
        if ($record && (! $record->is_active || ($record->starts_at && $record->starts_at->isFuture()) || ($record->ends_at && $record->ends_at->isPast()))) return null;
        $slide['src'] = $record ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->image_path) : asset('fe-assets/assets/img/advertisements/'.$slide['file']);
        $slide['label'] = $record?->label ?: $slide['label'];
        $slide['destination'] = $record?->target_url ?: 'https://wa.me/905303607769?text='.rawurlencode($slide['message']);
        if (! in_array(strtolower(parse_url($slide['destination'], PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)) $slide['destination'] = 'https://wa.me/905303607769?text='.rawurlencode($slide['message']);
        return $slide;
    })->filter()->values();
@endphp
@if($slides->isNotEmpty())
    <div class="home-print-ad-wrap">
        <a class="home-print-ad" data-home-print-ad href="{{ $slides->first()['destination'] }}" target="_blank" rel="sponsored noopener noreferrer" aria-label="{{ $slides->first()['label'] }} — WhatsApp 0530 360 77 69">
            @foreach($slides as $slide)
                <img @class(['is-visible' => $loop->first]) src="{{ $slide['src'] }}" width="1600" height="240" alt="{{ $slide['label'] }} — WhatsApp 0530 360 77 69" data-message="{{ $slide['message'] }}" data-destination="{{ $slide['destination'] }}" data-label="{{ $slide['label'] }} — WhatsApp 0530 360 77 69" @if(!$loop->first) aria-hidden="true" @else fetchpriority="high" @endif>
            @endforeach
        </a>
    </div>
@endif
