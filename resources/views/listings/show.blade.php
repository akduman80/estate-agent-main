<x-layouts.app>
    @section('title', $listing->title . ' | Zyd Emlak')

    @php
        $images = $listing->images;
        $mainImage = $images->first()?->image_path;
        $mainImageUrl = $mainImage ? Storage::url($mainImage) : asset('fe-assets/assets/img/demo/property-1.jpg');
    @endphp

    <div class="page-head">
        <div class="container">
            <div class="row">
                <div class="page-head-content">
                    <h1 class="page-title">{{ $listing->title }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content-area single-property">
        <div class="container">
            <div class="clearfix padding-top-40">
                <div class="col-md-8 single-property-content">
                    <div class="row">
                        <div class="light-slide-item">
                            <img src="{{ $mainImageUrl }}" alt="{{ $listing->title }}">
                        </div>
                    </div>

                    <div class="single-property-wrapper">
                        <div class="single-property-header">
                            <h1 class="property-title pull-left">{{ $listing->title }}</h1>
                            <span class="property-price pull-right">₺{{ number_format($listing->price, 0, ',', '.') }}</span>
                        </div>

                        <div class="property-meta entry-meta clearfix">
                            <span><i class="fa fa-map-marker"></i> {{ $listing->city_name }} / {{ $listing->district_name }}</span>
                            <span><i class="fa fa-home"></i> {{ $listing->type }}</span>
                            @if($listing->square_meters)
                                <span><i class="fa fa-expand"></i> {{ number_format($listing->square_meters, 0, ',', '.') }} m²</span>
                            @endif
                        </div>

                        <div class="section">
                            <h4>Açıklama</h4>
                            <p>{!! nl2br(e($listing->description ?: 'Bu ilan için açıklama eklenmemiş.')) !!}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 p0">
                    <aside class="sidebar sidebar-property blog-asside-right">
                        <div class="panel panel-default sidebar-menu">
                            <div class="panel-heading">
                                <h3 class="panel-title">İlan Bilgileri</h3>
                            </div>
                            <div class="panel-body recent-property-widget">
                                <ul>
                                    <li><strong>İlan Tipi:</strong> {{ $listing->type }}</li>
                                    <li><strong>Fiyat:</strong> ₺{{ number_format($listing->price, 0, ',', '.') }}</li>
                                    <li><strong>İl / İlçe:</strong> {{ $listing->city_name }} / {{ $listing->district_name }}</li>
                                    @if($listing->neighborhood_id)
                                        <li><strong>Mahalle:</strong> {{ $listing->neighborhood_id }}</li>
                                    @endif
                                    @if($listing->square_meters)
                                        <li><strong>Alan:</strong> {{ number_format($listing->square_meters, 0, ',', '.') }} m²</li>
                                    @endif
                                    @if($listing->zoning_status)
                                        <li><strong>İmar Durumu:</strong> {{ $listing->zoning_status }}</li>
                                    @endif
                                    @if($listing->deed_status)
                                        <li><strong>Tapu Durumu:</strong> {{ $listing->deed_status }}</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
