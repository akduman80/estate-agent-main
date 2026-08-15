<x-layouts.app>
    @php
        $sitePhone = \App\Models\Setting::get('site_phone', '+90 (850) 000 00 00');
        $siteEmail = \App\Models\Setting::get('site_email', 'info@estate.com');
        $pageTitle = \App\Models\Setting::get('contact_page_title', 'İletişim');
        $companyName = \App\Models\Setting::get('contact_company_name', 'Zyd Emlak');
        $siteAddress = \App\Models\Setting::get('site_address', 'Istanbul, Turkiye');
        $mapQuery = \App\Models\Setting::get('contact_map_query', $siteAddress) ?: $siteAddress;
        $phoneText = \App\Models\Setting::get('contact_phone_text', 'İlanlar, randevu ve danışmanlık talepleriniz için bize ulaşabilirsiniz.');
        $emailText = \App\Models\Setting::get('contact_email_text', 'Mesajınızı e-posta ile iletebilir veya aşağıdaki iletişim formunu kullanabilirsiniz.');
        $formTitle = \App\Models\Setting::get('contact_form_title', 'İletişim formu');
        $submitText = \App\Models\Setting::get('contact_submit_text', 'Mesaj gönder');
    @endphp

    @section('title', $pageTitle . ' | ' . $companyName)

    <div class="page-head">
        <div class="container">
            <div class="row">
                <div class="page-head-content">
                    <h1 class="page-title">{{ $pageTitle }}</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content-area recent-property padding-top-40" style="background-color: #FFF;">
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div id="contact1">
                        <div class="row">
                            <div class="col-sm-4">
                                <h3><i class="fa fa-map-marker"></i> Adres</h3>
                                <p>
                                    {{ $siteAddress }}
                                    <br>
                                    <strong>{{ $companyName }}</strong>
                                </p>
                            </div>

                            <div class="col-sm-4">
                                <h3><i class="fa fa-phone"></i> Telefon</h3>
                                <p class="text-muted">{{ $phoneText }}</p>
                                <p><strong>{{ $sitePhone }}</strong></p>
                            </div>

                            <div class="col-sm-4">
                                <h3><i class="fa fa-envelope"></i> E-posta</h3>
                                <p class="text-muted">{{ $emailText }}</p>
                                <ul>
                                    <li><strong><a href="mailto:{{ $siteEmail }}">{{ $siteEmail }}</a></strong></li>
                                </ul>
                            </div>
                        </div>

                        <hr>

                        <div id="map">
                            <iframe
                                title="{{ $companyName }} konum haritasi"
                                src="https://www.google.com/maps?q={{ urlencode($mapQuery) }}&output=embed"
                                width="100%"
                                height="300"
                                style="border:0;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>

                        <hr>

                        <h2>{{ $formTitle }}</h2>
                        <form action="mailto:{{ $siteEmail }}" method="POST" enctype="text/plain">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="firstname">Ad</label>
                                        <input type="text" class="form-control" id="firstname" name="ad">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="lastname">Soyad</label>
                                        <input type="text" class="form-control" id="lastname" name="soyad">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="email">E-posta</label>
                                        <input type="email" class="form-control" id="email" name="email">
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="subject">Konu</label>
                                        <input type="text" class="form-control" id="subject" name="konu">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <label for="message">Mesaj</label>
                                        <textarea id="message" class="form-control" name="mesaj" rows="6"></textarea>
                                    </div>
                                </div>

                                <div class="col-sm-12 text-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-envelope-o"></i> {{ $submitText }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
