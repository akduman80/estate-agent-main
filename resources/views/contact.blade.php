@php
    $sitePhone = \App\Models\Setting::get('site_phone', '+90 (850) 000 00 00');
    $siteEmail = \App\Models\Setting::get('site_email', 'info@zydemlak.com');
    $pageTitle = \App\Models\Setting::get('contact_page_title', 'İletişim');
    $companyName = \App\Models\Setting::get('contact_company_name', 'ZYDEMLAK');
    $siteAddress = \App\Models\Setting::get('site_address', 'Osmaniye, Türkiye');
    $mapQuery = \App\Models\Setting::get('contact_map_query', $siteAddress) ?: $siteAddress;
    $phoneText = \App\Models\Setting::get('contact_phone_text', 'İlanlar, randevu ve danışmanlık talepleriniz için bize ulaşabilirsiniz.');
    $emailText = \App\Models\Setting::get('contact_email_text', 'Sorularınızı e-posta ile iletebilirsiniz.');
    $formTitle = \App\Models\Setting::get('contact_form_title', 'Bize mesaj gönderin');
    $submitText = \App\Models\Setting::get('contact_submit_text', 'E-posta uygulamasını aç');
    $phoneHref = preg_replace('/[^0-9+]/', '', $sitePhone);
@endphp

<x-layouts.modern :title="$pageTitle.' | '.$companyName" :description="$companyName.' iletişim bilgileri, adres, telefon ve e-posta.'">
    <main class="flex-1">
        <section class="bg-blue-950 py-12 text-center text-white sm:py-16">
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-orange-400">{{ $companyName }}</p>
                <h1 class="mt-3 text-3xl font-extrabold sm:text-4xl">{{ $pageTitle }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-6 text-slate-300">Gayrimenkul ilanları, üyelik ve danışmanlık talepleriniz için bize dilediğiniz kanaldan ulaşabilirsiniz.</p>
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid gap-5 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-orange-50 text-xl text-orange-500"><i class="fa-solid fa-location-dot"></i></span>
                    <h2 class="mt-4 text-lg font-extrabold text-blue-950">Adres</h2>
                    <div class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{!! \App\Support\RichContentHtml::render($siteAddress) !!}</div>
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode(strip_tags($mapQuery)) }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-orange-600 hover:text-orange-700">Yol tarifi al <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i></a>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-orange-50 text-xl text-orange-500"><i class="fa-solid fa-phone"></i></span>
                    <h2 class="mt-4 text-lg font-extrabold text-blue-950">Telefon</h2>
                    <div class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{!! \App\Support\RichContentHtml::render($phoneText) !!}</div>
                    <a href="tel:{{ $phoneHref }}" class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-orange-600 hover:text-orange-700">{{ $sitePhone }}</a>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-orange-50 text-xl text-orange-500"><i class="fa-solid fa-envelope"></i></span>
                    <h2 class="mt-4 text-lg font-extrabold text-blue-950">E-posta</h2>
                    <div class="mt-2 min-h-12 text-sm leading-6 text-slate-600">{!! \App\Support\RichContentHtml::render($emailText) !!}</div>
                    <a href="mailto:{{ $siteEmail }}" class="mt-4 inline-flex items-center gap-2 break-all text-sm font-bold text-orange-600 hover:text-orange-700">{{ $siteEmail }}</a>
                </article>
            </div>

            <div class="mt-8 grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[1.15fr_.85fr]">
                <div class="min-h-[360px] bg-slate-100">
                    <iframe title="{{ $companyName }} konum haritası" src="https://www.google.com/maps?q={{ urlencode($mapQuery) }}&output=embed" class="h-full min-h-[360px] w-full border-0" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="p-6 sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Size nasıl yardımcı olabiliriz?</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-blue-950">{{ $formTitle }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Formu doldurduğunuzda cihazınızdaki e-posta uygulaması açılır. Mesajınızı göndermeden önce son kez kontrol edebilirsiniz.</p>

                    <form action="mailto:{{ $siteEmail }}" method="post" enctype="text/plain" class="mt-6 space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="text-xs font-bold text-slate-700">Ad Soyad</span><input type="text" name="ad_soyad" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500"></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">E-posta</span><input type="email" name="email" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500"></label>
                        </div>
                        <label class="block"><span class="text-xs font-bold text-slate-700">Konu</span><input type="text" name="konu" required class="mt-1.5 w-full rounded-xl border-slate-300 text-sm focus:border-orange-500 focus:ring-orange-500"></label>
                        <x-rich-textarea name="mesaj" label="Mesaj" required :plain-submit="true" />
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3 text-sm font-bold text-white shadow transition hover:bg-orange-600"><i class="fa-solid fa-envelope"></i>{{ $submitText }}</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
</x-layouts.modern>
