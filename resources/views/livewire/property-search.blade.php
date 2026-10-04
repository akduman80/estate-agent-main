<div>
    <!-- Home Large Search Overlay Section -->
    <div class="home-lager-shearch">
        <div class="container">
            <div class="col-md-12 large-search">
                <div class="search-form wow pulse">
                    <form wire:submit.prevent="$refresh" class="form-inline">
                        <div class="col-md-12 clear">
                            <div class="col-md-3">
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Arama kelimesi (İlan, Mahalle vb.)">
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="city_id" class="form-control">
                                    <option value="">Tüm İller</option>
                                    @foreach($cities as $key => $cityName)
                                        <option value="{{ $key }}">{{ $cityName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="district_id" class="form-control" @disabled(! $city_id)>
                                    <option value="">Tüm İlçeler</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district }}">{{ $district }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select wire:model.live="neighborhood_id" class="form-control" @disabled(! $district_id)>
                                    <option value="">Tüm Mahalleler</option>
                                    @foreach($neighborhoods as $neighborhood)
                                        <option value="{{ $neighborhood }}">{{ $neighborhood }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="type" class="form-control">
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
    <div class="content-area recent-property">
        <div class="container">
            <div class="row">
                <div class="col-md-12 padding-top-40 properties-page">
                    <div class="col-md-12 text-center">
                        <h2>Güncel İlanlar</h2>
                    </div>

                    <div class="col-md-12">
                        <div id="list-type" class="proerty-th">
                            @forelse($listings as $listing)
                                <div class="col-sm-6 col-md-3 p0">
                                    <div class="box-two proerty-item">
                                        <div class="item-thumb">
                                            <a href="{{ route('listings.show', $listing->slug) }}">
                                                <img src="{{ $listing->images->first()?->image_path ? asset('storage/'.$listing->images->first()->image_path) : asset('fe-assets/assets/img/demo/property-1.jpg') }}" alt="{{ $listing->title }}">
                                            </a>
                                        </div>

                                        <div class="item-entry overflow">
                                            <h5><a href="{{ route('listings.show', $listing->slug) }}">{{ Str::limit($listing->title, 30) }}</a></h5>
                                            <div class="dot-hr"></div>
                                            <span class="pull-left"><b>Alan :</b> {{ $listing->square_meters }}m² </span>
                                            <span class="proerty-price pull-right">₺{{ \App\Support\ListingDecimal::format($listing->price) }}</span>
                                            <div class="property-icon">
                                                <i class="fa fa-map-marker"></i> {{ $listing->city_name }} / {{ $listing->district_name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-md-12 text-center">
                                    <div class="alert alert-warning">
                                        <i class="fa fa-info-circle"></i> Arama kriterlerinize uygun aktif ilan bulunamadı.
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if(method_exists($listings, 'links'))
                        <div class="col-md-12 text-center">
                            {{ $listings->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
