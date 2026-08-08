@props(['listing'])

@php
    $mainImage = $listing->images->first()?->image_path;
    $imageUrl  = $mainImage ? Storage::url($mainImage) : asset('fe-assets/assets/img/demo/property-1.jpg');
@endphp

<div class="col-sm-6 col-md-3 p0 listing-card-item">
    <div class="box-two proerty-item">
        <div class="item-thumb">
            <a href="{{ url('/ilan/' . $listing->slug) }}">
                <img src="{{ $imageUrl }}" alt="{{ $listing->title }}" style="height: 200px; object-fit: cover; width: 100%;">
            </a>
        </div>

        <div class="item-entry overflow">
            <h5>
                <a href="{{ url('/ilan/' . $listing->slug) }}" title="{{ $listing->title }}">
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
            <p style="display: none; margin-top: 15px; clear: both; font-size: 13px; color: #777;">
                {{ Str::limit(strip_tags($listing->description), 150) }}
            </p>

            <div class="property-icon" style="margin-top: 10px; clear: both;">
                <span><i class="pe-7s-map-marker"></i> {{ $listing->city_id }} / {{ $listing->district_id }}</span>
            </div>
        </div>
    </div>
</div>
