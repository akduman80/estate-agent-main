<x-layouts.app>
    @section('title', 'İlanlar | Zyd Emlak')

    <div class="page-head">
        <div class="container">
            <div class="row">
                <div class="page-head-content">
                    <h1 class="page-title">İlanlar</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="properties-area recent-property">
        <div class="container">
            <div class="row">
                <div class="col-md-3 p0 padding-top-40">
                    <div class="blog-asside-right pr0">
                        <div class="panel panel-default sidebar-menu wow fadeInRight animated">
                            <div class="panel-heading">
                                <h3 class="panel-title">Akıllı Arama</h3>
                            </div>
                            <div class="panel-body search-widget">
                                <form action="{{ route('listings.index') }}" method="GET" class="form-inline">
                                    <fieldset>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Anahtar kelime">
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <select name="city" class="selectpicker" data-container="body" data-live-search="true" data-live-search-style="begins" title="İl Seçiniz">
                                                    <option value="">Tüm İller</option>
                                                    @foreach($cities as $city)
                                                        <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-xs-6">
                                                <select name="type" class="selectpicker show-tick form-control" data-container="body">
                                                    <option value="">İlan Tipi</option>
                                                    @foreach($typeOptions as $type)
                                                        <option value="{{ $type }}" @selected(request('type') === $type)>{{ $type }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="padding-5">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <label for="min_price">Min fiyat (₺):</label>
                                                <input type="number" name="min_price" id="min_price" value="{{ request('min_price') }}" class="form-control" placeholder="0">
                                            </div>
                                            <div class="col-xs-6">
                                                <label for="max_price">Max fiyat (₺):</label>
                                                <input type="number" name="max_price" id="max_price" value="{{ request('max_price') }}" class="form-control" placeholder="5000000">
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset class="padding-5">
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <label for="min_area">Min m²:</label>
                                                <input type="number" name="min_area" id="min_area" value="{{ request('min_area') }}" class="form-control" placeholder="40">
                                            </div>
                                            <div class="col-xs-6">
                                                <label for="max_area">Max m²:</label>
                                                <input type="number" name="max_area" id="max_area" value="{{ request('max_area') }}" class="form-control" placeholder="12000">
                                            </div>
                                        </div>
                                    </fieldset>

                                    <fieldset>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <input class="button btn largesearch-btn" value="Ara" type="submit">
                                            </div>
                                        </div>
                                    </fieldset>
                                </form>
                            </div>
                        </div>

                        <div class="panel panel-default sidebar-menu wow fadeInRight animated">
                            <div class="panel-heading">
                                <h3 class="panel-title">Önerilenler</h3>
                            </div>
                            <div class="panel-body recent-property-widget">
                                <ul>
                                    @forelse($recommendedListings as $recommended)
                                        @php
                                            $recommendedImage = $recommended->images->first()?->image_path;
                                            $recommendedImageUrl = $recommendedImage ? Storage::url($recommendedImage) : asset('fe-assets/assets/img/demo/small-property-1.jpg');
                                        @endphp
                                        <li>
                                            <div class="col-md-3 col-sm-3 col-xs-3 blg-thumb p0">
                                                <a href="{{ route('listings.show', $recommended->slug) }}">
                                                    <img src="{{ $recommendedImageUrl }}" alt="{{ $recommended->title }}">
                                                </a>
                                                <span class="property-seeker">
                                                    <b class="b-1">Z</b>
                                                    <b class="b-2">Y</b>
                                                </span>
                                            </div>
                                            <div class="col-md-8 col-sm-8 col-xs-8 blg-entry">
                                                <h6><a href="{{ route('listings.show', $recommended->slug) }}">{{ Str::limit($recommended->title, 32) }}</a></h6>
                                                <span class="property-price">₺{{ number_format($recommended->price, 0, ',', '.') }}</span>
                                            </div>
                                        </li>
                                    @empty
                                        <li>Henüz önerilen ilan yok.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-9 pr0 padding-top-40 properties-page">
                    <div class="col-md-12 clear">
                        <div class="col-xs-10 page-subheader sorting pl0">
                            <ul class="sort-by-list">
                                <li class="{{ request('sort') !== 'price' ? 'active' : '' }}">
                                    <a href="{{ route('listings.index', request()->except('sort')) }}" class="order_by_date">
                                        İlan Tarihi <i class="fa fa-sort-amount-asc"></i>
                                    </a>
                                </li>
                                <li class="{{ request('sort') === 'price' ? 'active' : '' }}">
                                    <a href="{{ route('listings.index', array_merge(request()->except('page'), ['sort' => 'price'])) }}" class="order_by_price">
                                        İlan Fiyatı <i class="fa fa-sort-numeric-desc"></i>
                                    </a>
                                </li>
                            </ul>

                            <div class="items-per-page">
                                <label><b>Toplam ilan :</b></label>
                                <div class="sel">
                                    <select disabled>
                                        <option selected>{{ $listings->total() }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="col-xs-2 layout-switcher">
                            <a class="layout-list" href="javascript:void(0);" id="btn-list-view"><i class="fa fa-th-list"></i></a>
                            <a class="layout-grid active" href="javascript:void(0);" id="btn-grid-view"><i class="fa fa-th"></i></a>
                        </div>
                    </div>

                    <div class="col-md-12 clear">
                        <div id="list-type" class="proerty-th">
                            @forelse($listings as $listing)
                                @php
                                    $image = $listing->images->first()?->image_path;
                                    $imageUrl = $image ? Storage::url($image) : asset('fe-assets/assets/img/demo/property-1.jpg');
                                @endphp
                                <div class="col-sm-6 col-md-4 p0 listing-card-item">
                                    <div class="box-two proerty-item">
                                        <div class="item-thumb">
                                            <a href="{{ route('listings.show', $listing->slug) }}">
                                                <img src="{{ $imageUrl }}" alt="{{ $listing->title }}">
                                            </a>
                                        </div>

                                        <div class="item-entry overflow">
                                            <h5><a href="{{ route('listings.show', $listing->slug) }}">{{ Str::limit($listing->title, 35) }}</a></h5>
                                            <div class="dot-hr"></div>
                                            <span class="pull-left"><b>Alan :</b> {{ $listing->square_meters ? number_format($listing->square_meters, 0, ',', '.') . 'm²' : '-' }}</span>
                                            <span class="proerty-price pull-right">₺{{ number_format($listing->price, 0, ',', '.') }}</span>
                                            <p>{{ Str::limit(strip_tags($listing->description), 150) }}</p>
                                            <div class="property-icon">
                                                <i class="fa fa-map-marker"></i> {{ $listing->city_name }} / {{ $listing->district_name }}
                                                @if($listing->is_verified)
                                                    | <i class="fa fa-check-circle"></i> EİDS
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-md-12 text-center">
                                    <div class="alert alert-warning">
                                        <i class="fa fa-info-circle"></i> Arama kriterlerine uygun aktif ilan bulunamadı.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="pull-right">
                            {{ $listings->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
