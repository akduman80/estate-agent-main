@props(['listing'])

@php
    $mainImage = $listing->images->first()?->image_path;
    $imageUrl  = $mainImage ? Storage::url($mainImage) : asset('fe-assets/assets/img/demo/property-1.jpg');
    $eidsVerification = $listing->eidsVerification;
@endphp

<div class="col-sm-6 col-md-3 p0 listing-card-item">
    <div class="box-two proerty-item">
        <div class="item-thumb">
            <a href="{{ route('listings.show', $listing->slug) }}">
                <img src="{{ $imageUrl }}" alt="{{ $listing->title }}">
            </a>
        </div>

        <div class="item-entry overflow">
            <span class="label label-warning" style="display:inline-block;margin-bottom:8px;">{{ $listing->source_label }}</span>
            <h5>
                <a href="{{ route('listings.show', $listing->slug) }}" title="{{ $listing->title }}">
                    {{ Str::limit($listing->title, 35) }}
                </a>
            </h5>
            <div class="dot-hr"></div>

            <span class="pull-left">
                <b>Alan :</b> {{ $listing->square_meters ? number_format($listing->square_meters, 0, ',', '.') . 'm²' : 'N/A' }}
            </span>

            <span class="proerty-price pull-right">
                ₺ {{ number_format($listing->price, 0, ',', '.') }}
            </span>

            <!-- Liste Görünümünde Açılan Detay Açıklaması -->
            <p>
                {{ Str::limit(strip_tags($listing->description), 150) }}
            </p>

            <div class="property-icon">
                <span><i class="pe-7s-map-marker"></i> {{ $listing->city_name }} / {{ $listing->district_name }}</span>
                @if($listing->is_verified && ($eidsVerification?->block || $eidsVerification?->parcel))
                    <span>
                        EİDS Ada/Parsel: {{ $eidsVerification?->block ?: '-' }} / {{ $eidsVerification?->parcel ?: '-' }}
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
