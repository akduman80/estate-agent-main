<div>
    <!-- Home Large Search Overlay Section -->
    <div class="home-lager-shearch" style="padding-top: 25px; margin-top: -125px; padding-bottom: 60px; background-color: rgb(252, 252, 252); position: relative; z-index: 20;">
        <div class="container">
            <div class="col-md-12 large-search">
                <div class="search-form wow pulse">
                    <form wire:submit.prevent="$refresh" class="form-inline">
                        <div class="col-md-12 clear">
                            <div class="col-md-4">
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Arama kelimesi (İlan, Mahalle vb.)">
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="city_id" class="form-control" style="width: 100%; height: 42px; border: 1px solid #E3E3E3; color: #777; background: #fff;">
                                    <option value="">Tüm İller</option>
                                    @foreach($cities as $key => $cityName)
                                        <option value="{{ $key }}">{{ $cityName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="type" class="form-control" style="width: 100%; height: 42px; border: 1px solid #E3E3E3; color: #777; background: #fff;">
                                    <option value="">İlan Tipi (Tümü)</option>
                                    @foreach($typeOptions as $option)
                                        <option value="{{ $option }}">{{ $option }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-12 clear">
                            <div class="search-row">
                                <div class="col-sm-3">
                                    <label for="price-range">Fiyat Aralığı (₺):</label>
                                    <input type="text" class="span2" value="" data-slider-min="0"
                                           data-slider-max="5000000" data-slider-step="1000"
                                           data-slider-value="[2000,1000000]" id="price-range"><br />
                                    <b class="pull-left color">2.000 ₺</b>
                                    <b class="pull-right color">5.000.000 ₺</b>
                                </div>

                                <div class="col-sm-3">
                                    <label for="property-geo">Alan (m²) :</label>
                                    <input type="text" class="span2" value="" data-slider-min="0"
                                           data-slider-max="1000" data-slider-step="10"
                                           data-slider-value="[40,500]" id="property-geo"><br />
                                    <b class="pull-left color">40m²</b>
                                    <b class="pull-right color">1000m²</b>
                                </div>

                                <div class="col-sm-3">
                                    <label for="min-baths">Min Banyo :</label>
                                    <input type="text" class="span2" value="" data-slider-min="1"
                                           data-slider-max="10" data-slider-step="1"
                                           data-slider-value="[1,5]" id="min-baths"><br />
                                    <b class="pull-left color">1</b>
                                    <b class="pull-right color">10</b>
                                </div>

                                <div class="col-sm-3">
                                    <label for="min-bed">Min Yatak Odası :</label>
                                    <input type="text" class="span2" value="" data-slider-min="1"
                                           data-slider-max="10" data-slider-step="1"
                                           data-slider-value="[1,5]" id="min-bed"><br />
                                    <b class="pull-left color">1</b>
                                    <b class="pull-right color">10</b>
                                </div>
                            </div>

                            <div class="search-row">
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label><input type="checkbox" wire:model.live="features" value="fireplace"> Şömine</label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label><input type="checkbox" wire:model.live="features" value="pool"> Yüzme Havuzu</label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label><input type="checkbox" wire:model.live="features" value="garage"> Otopark</label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="checkbox">
                                        <label><input type="checkbox" wire:model.live="features" value="elevator"> Asansör</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="center">
                            <input type="submit" value="" class="btn btn-default btn-lg-sheach">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Güncel İlanlar Liste Alanı -->
    <div class="content-area recent-property" style="padding-bottom: 60px; background-color: rgb(252, 252, 252);">
        <div class="container">
            <div class="row">
                <div class="col-md-12 padding-top-40 properties-page">
                    <div class="col-md-12 text-center" style="margin-bottom: 30px;">
                        <h2>Güncel EİDS Onaylı İlanlar</h2>
                    </div>

                    <div class="col-md-12">
                        <div id="list-type" class="proerty-th">
                            @forelse($listings as $listing)
                                <div class="col-sm-6 col-md-3 p0">
                                    <div class="box-two proerty-item">
                                        <div class="item-thumb" style="position: relative;">
                                            <a href="{{ route('listings.show', $listing->slug) }}">
                                                <img src="{{ $listing->images->first()?->image_path ? asset('storage/'.$listing->images->first()->image_path) : asset('fe-assets/assets/img/demo/property-1.jpg') }}" alt="{{ $listing->title }}">
                                            </a>

                                            @if($listing->is_verified)
                                                <div style="position: absolute; top: 10px; right: 10px; z-index: 5;">
                                                    <span class="label label-success" style="background-color: #28a745; font-size: 11px; padding: 5px 8px;">
                                                        <i class="fa fa-check-circle"></i> EİDS Onaylı
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="item-entry overflow">
                                            <h5><a href="{{ route('listings.show', $listing->slug) }}">{{ Str::limit($listing->title, 30) }}</a></h5>
                                            <div class="dot-hr"></div>
                                            <span class="pull-left"><b>Alan :</b> {{ $listing->square_meters }}m² </span>
                                            <span class="proerty-price pull-right">₺{{ number_format($listing->price, 0, ',', '.') }}</span>
                                            <div class="property-icon">
                                                <i class="fa fa-map-marker"></i> {{ $listing->city_name }} / {{ $listing->district_name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-md-12 text-center">
                                    <div class="alert alert-warning" style="margin-top: 20px;">
                                        <i class="fa fa-info-circle"></i> Arama kriterlerinize uygun EİDS onaylı aktif ilan bulunamadı.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if(method_exists($listings, 'links'))
                        <div class="col-md-12 text-center" style="margin-top: 30px;">
                            {{ $listings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
