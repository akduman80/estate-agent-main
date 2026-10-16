@php($slides = \App\Support\HomeAdvertisements::slides())
@if($slides->isNotEmpty())
    <div class="home-print-ad-wrap">
        <a class="home-print-ad" data-home-print-ad href="{{ $slides->first()['destination'] }}" target="_blank" rel="sponsored noopener noreferrer" aria-label="{{ $slides->first()['label'] }}">
            @foreach($slides as $slide)
                <img @class(['is-visible' => $loop->first]) src="{{ $slide['src'] }}" width="1600" height="240" alt="{{ $slide['label'] }}" data-message="" data-destination="{{ $slide['destination'] }}" data-label="{{ $slide['label'] }}" @if(!$loop->first) aria-hidden="true" @else fetchpriority="high" @endif>
            @endforeach
        </a>
    </div>
@endif
