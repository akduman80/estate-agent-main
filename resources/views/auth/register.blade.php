@php
    $isBusiness = $type === 'business';
    $pageTitle = $isBusiness ? 'Emlak İşletmesi Üyeliği' : 'Bireysel Üyelik';
    $inputClass = 'mt-1.5 w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-orange-500 focus:ring-orange-500';
@endphp

<x-layouts.modern :title="$pageTitle.' | ZYDEMLAK'" description="ZYDEMLAK üyeliğinizi oluşturun, e-posta adresinizi doğrulayın ve EİDS doğrulamalı ilan sürecine başlayın.">
    <main class="flex-1 bg-slate-100 py-8 sm:py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="grid overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl lg:grid-cols-[360px_minmax(0,1fr)]">
                <aside class="relative overflow-hidden bg-blue-950 p-7 text-white sm:p-9">
                    <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full bg-orange-500/15"></div>
                    <div class="absolute -bottom-24 -left-20 h-64 w-64 rounded-full bg-white/5"></div>
                    <div class="relative">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-xl shadow-lg"><i class="fa-solid fa-user-shield"></i></span>
                        <h1 class="mt-6 text-3xl font-extrabold leading-tight">Güvenli üyelik,<br>doğrulanmış ilanlar</h1>
                        <p class="mt-4 text-sm leading-6 text-slate-300">{{ $emailOtpEnabled ? 'Hesabınızı oluşturduktan sonra e-posta adresinize 6 haneli doğrulama kodu gönderilir.' : 'OTP sistemi şu anda pasif. Kaydınız kod beklemeden güvenli hesap akışına alınır.' }}</p>

                        <div class="mt-8 space-y-5 text-sm">
                            <div class="flex gap-3"><i class="fa-solid fa-circle-check mt-0.5 text-orange-400"></i><span>{{ $emailOtpEnabled ? 'E-posta koduyla hesap doğrulama' : 'Supervisor kontrollü OTP özelliği' }}</span></div>
                            <div class="flex gap-3"><i class="fa-solid fa-circle-check mt-0.5 text-orange-400"></i><span>Kimlik ve vergi bilgilerinin güvenli saklanması</span></div>
                            <div class="flex gap-3"><i class="fa-solid fa-circle-check mt-0.5 text-orange-400"></i><span>EİDS ve yönetici onaylı ilan yayını</span></div>
                        </div>

                        <div class="mt-10 rounded-2xl border border-white/10 bg-white/5 p-4 text-xs leading-5 text-slate-300">
                            <i class="fa-solid fa-lock mr-2 text-orange-400"></i>İlanlar doğrulama ve yayın onayı tamamlanmadan ziyaretçilere gösterilmez.
                        </div>
                    </div>
                </aside>

                <section class="p-5 sm:p-8 lg:p-10">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Yeni hesap</p>
                            <h2 class="mt-2 text-2xl font-extrabold text-blue-950">{{ $pageTitle }}</h2>
                            <p class="mt-2 text-sm text-slate-500">Size uygun üyelik türünü seçerek bilgilerinizi doldurun.</p>
                        </div>
                        <a href="{{ route('login') }}" class="shrink-0 text-sm font-bold text-orange-600 hover:text-orange-700">Zaten üyeyim <i class="fa-solid fa-arrow-right ml-1"></i></a>
                    </div>

                    <div class="mt-7 grid grid-cols-2 rounded-xl bg-slate-100 p-1.5 text-center text-sm font-bold">
                        <a href="{{ route('register', ['type' => 'individual']) }}" class="rounded-lg px-3 py-3 transition {{ ! $isBusiness ? 'bg-white text-orange-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"><i class="fa-solid fa-user mr-1.5"></i>Bireysel</a>
                        <a href="{{ route('register', ['type' => 'business']) }}" class="rounded-lg px-3 py-3 transition {{ $isBusiness ? 'bg-white text-orange-600 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}"><i class="fa-solid fa-building mr-1.5"></i>Emlak işletmesi</a>
                    </div>

                    @if($errors->any())
                        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-exclamation mt-0.5 text-red-500"></i>
                                <div>
                                    <p class="font-bold">Lütfen aşağıdaki bilgileri kontrol edin:</p>
                                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs">
                                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="post" action="{{ route('register.store') }}" class="mt-7 grid gap-5 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="account_type" value="{{ $type }}">

                        <label class="block"><span class="text-xs font-bold text-slate-700">Ad <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="first_name" autocomplete="given-name" value="{{ old('first_name') }}" required></label>
                        <label class="block"><span class="text-xs font-bold text-slate-700">Soyad <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="last_name" autocomplete="family-name" value="{{ old('last_name') }}" required></label>

                        @if(! $isBusiness)
                            <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">T.C. Kimlik Numarası <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="identity_number" inputmode="numeric" autocomplete="off" maxlength="11" value="{{ old('identity_number') }}" required><span class="mt-1.5 block text-[11px] leading-4 text-slate-400">11 haneli kimlik numaranız hesap güvenliği için şifreli olarak saklanır.</span></label>
                        @else
                            <div class="sm:col-span-2"><h3 class="border-b border-slate-200 pb-2 text-sm font-extrabold text-blue-950">İşletme bilgileri</h3></div>
                            <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">İşletme / Ofis Adı <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="office_name" value="{{ old('office_name') }}" required></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">Yetki Belgesi Numarası <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="license_number" value="{{ old('license_number') }}" required></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">Vergi Dairesi <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="tax_office" value="{{ old('tax_office') }}" required></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">Vergi Numarası <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="tax_number" inputmode="numeric" maxlength="10" value="{{ old('tax_number') }}" required></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">İşletme Telefonu</span><input class="{{ $inputClass }}" type="tel" name="business_phone" value="{{ old('business_phone') }}"></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">İl <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="city" value="{{ old('city') }}" required></label>
                            <label class="block"><span class="text-xs font-bold text-slate-700">İlçe <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" name="district" value="{{ old('district') }}" required></label>
                            <label class="block sm:col-span-2"><span class="text-xs font-bold text-slate-700">İşletme Adresi <b class="text-red-500">*</b></span><textarea class="{{ $inputClass }}" name="address" rows="3" required>{{ old('address') }}</textarea></label>
                        @endif

                        <div class="sm:col-span-2"><h3 class="border-b border-slate-200 pb-2 text-sm font-extrabold text-blue-950">İletişim ve giriş bilgileri</h3></div>
                        <label class="block"><span class="text-xs font-bold text-slate-700">Cep Telefonu <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" type="tel" name="phone" autocomplete="tel" value="{{ old('phone') }}" required></label>
                        <label class="block"><span class="text-xs font-bold text-slate-700">E-posta <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" type="email" name="email" autocomplete="email" value="{{ old('email') }}" required></label>
                        <label class="block"><span class="text-xs font-bold text-slate-700">Şifre <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" type="password" name="password" autocomplete="new-password" required><span class="mt-1.5 block text-[11px] text-slate-400">En az 10 karakter; büyük-küçük harf ve rakam içermeli.</span></label>
                        <label class="block"><span class="text-xs font-bold text-slate-700">Şifre Tekrar <b class="text-red-500">*</b></span><input class="{{ $inputClass }}" type="password" name="password_confirmation" autocomplete="new-password" required></label>

                        @if($documents->isNotEmpty())
                            <fieldset class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:col-span-2">
                                <legend class="px-2 text-xs font-extrabold text-blue-950">Sözleşmeler ve aydınlatma metinleri</legend>
                                @foreach($documents as $document)
                                    <label class="flex items-start gap-3 text-xs leading-5 text-slate-600">
                                        <input class="mt-1 rounded border-slate-300 text-orange-500 focus:ring-orange-500" type="checkbox" name="consents[]" value="{{ $document->id }}" @checked(in_array($document->id, old('consents', []))) @required($document->is_required)>
                                        <span><a class="font-bold text-orange-700 underline decoration-orange-300 underline-offset-2" target="_blank" rel="noopener noreferrer" href="{{ route('legal.show', $document->slug) }}">{{ $document->title }}</a> metnini okudum ve kabul ediyorum.@if($document->is_required) <b class="text-red-500">*</b>@endif</span>
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif

                        <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-orange-500 px-5 py-3.5 text-sm font-extrabold text-white shadow transition hover:bg-orange-600 sm:col-span-2"><i class="fa-solid fa-user-plus"></i>{{ $emailOtpEnabled ? 'Üyeliği oluştur ve e-postayı doğrula' : 'Üyeliği oluştur' }}</button>
                        <p class="text-center text-[11px] leading-5 text-slate-400 sm:col-span-2">{{ $emailOtpEnabled ? 'Devam ettiğinizde e-posta adresinize tek kullanımlık 6 haneli doğrulama kodu gönderilir.' : 'OTP yeniden aktif edildiğinde üyelik ve giriş işlemlerinde e-posta doğrulama kodu istenir.' }}</p>
                    </form>
                </section>
            </div>
        </div>
    </main>
</x-layouts.modern>
