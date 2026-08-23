<x-layouts.auth :title="$type === 'business' ? 'Emlak İşletmesi Üyeliği' : 'Bireysel Üyelik'">
    <div class="mt-8 flex gap-3 border-b pb-5">
        <a class="rounded-lg px-4 py-2 {{ $type === 'individual' ? 'bg-amber-500 text-white' : 'bg-slate-100' }}" href="{{ route('register', ['type' => 'individual']) }}">Bireysel üyelik</a>
        <a class="rounded-lg px-4 py-2 {{ $type === 'business' ? 'bg-amber-500 text-white' : 'bg-slate-100' }}" href="{{ route('register', ['type' => 'business']) }}">Emlak işletmesi</a>
    </div>

    <h1 class="mt-7 text-2xl font-bold">{{ $type === 'business' ? 'Emlak işletmesi üyeliği' : 'Bireysel / gayrimenkul sahibi üyeliği' }}</h1>
    <p class="mt-2 text-sm text-slate-600">E-posta adresiniz kodla doğrulanacaktır. İlan yayını ayrıca EİDS ve yönetici onayı gerektirir.</p>

    <form method="post" action="{{ route('register.store') }}" class="mt-8 grid gap-5 sm:grid-cols-2">
        @csrf
        <input type="hidden" name="account_type" value="{{ $type }}">
        <label class="block"><span class="text-sm font-medium">Ad</span><input class="mt-1 w-full rounded-lg border-slate-300" name="first_name" value="{{ old('first_name') }}" required></label>
        <label class="block"><span class="text-sm font-medium">Soyad</span><input class="mt-1 w-full rounded-lg border-slate-300" name="last_name" value="{{ old('last_name') }}" required></label>
        @if ($type === 'individual')
            <label class="block"><span class="text-sm font-medium">T.C. Kimlik Numarası</span><input class="mt-1 w-full rounded-lg border-slate-300" name="identity_number" inputmode="numeric" maxlength="11" value="{{ old('identity_number') }}" required></label>
        @else
            <label class="block sm:col-span-2"><span class="text-sm font-medium">İşletme / Ofis Adı</span><input class="mt-1 w-full rounded-lg border-slate-300" name="office_name" value="{{ old('office_name') }}" required></label>
            <label class="block"><span class="text-sm font-medium">Yetki Belgesi Numarası</span><input class="mt-1 w-full rounded-lg border-slate-300" name="license_number" value="{{ old('license_number') }}" required></label>
            <label class="block"><span class="text-sm font-medium">Vergi Dairesi</span><input class="mt-1 w-full rounded-lg border-slate-300" name="tax_office" value="{{ old('tax_office') }}" required></label>
            <label class="block"><span class="text-sm font-medium">Vergi Numarası</span><input class="mt-1 w-full rounded-lg border-slate-300" name="tax_number" inputmode="numeric" maxlength="10" value="{{ old('tax_number') }}" required></label>
            <label class="block"><span class="text-sm font-medium">İşletme Telefonu (isteğe bağlı)</span><input class="mt-1 w-full rounded-lg border-slate-300" name="business_phone" value="{{ old('business_phone') }}"></label>
            <label class="block"><span class="text-sm font-medium">İl</span><input class="mt-1 w-full rounded-lg border-slate-300" name="city" value="{{ old('city') }}" required></label>
            <label class="block"><span class="text-sm font-medium">İlçe</span><input class="mt-1 w-full rounded-lg border-slate-300" name="district" value="{{ old('district') }}" required></label>
            <label class="block sm:col-span-2"><span class="text-sm font-medium">İşletme Adresi</span><textarea class="mt-1 w-full rounded-lg border-slate-300" name="address" required>{{ old('address') }}</textarea></label>
        @endif
        <label class="block"><span class="text-sm font-medium">Cep Telefonu</span><input class="mt-1 w-full rounded-lg border-slate-300" name="phone" value="{{ old('phone') }}" required></label>
        <label class="block"><span class="text-sm font-medium">E-posta</span><input class="mt-1 w-full rounded-lg border-slate-300" type="email" name="email" value="{{ old('email') }}" required></label>
        <label class="block"><span class="text-sm font-medium">Şifre</span><input class="mt-1 w-full rounded-lg border-slate-300" type="password" name="password" required></label>
        <label class="block"><span class="text-sm font-medium">Şifre Tekrar</span><input class="mt-1 w-full rounded-lg border-slate-300" type="password" name="password_confirmation" required></label>
        @if ($documents->isNotEmpty())
            <div class="space-y-3 sm:col-span-2">
                @foreach ($documents as $document)
                    <label class="flex items-start gap-3 text-sm"><input class="mt-1 rounded" type="checkbox" name="consents[]" value="{{ $document->id }}" @checked(in_array($document->id, old('consents', [])))><span><a class="font-medium text-amber-700 underline" target="_blank" href="{{ route('legal.show', $document->slug) }}">{{ $document->title }}</a> metnini okudum ve kabul ediyorum.</span></label>
                @endforeach
            </div>
        @endif
        <button class="rounded-lg bg-amber-500 px-5 py-3 font-bold text-white hover:bg-amber-600 sm:col-span-2">Üyeliği oluştur ve e-postayı doğrula</button>
    </form>
</x-layouts.auth>
