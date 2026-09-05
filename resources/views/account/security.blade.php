<x-layouts.auth title="Hesap Güvenliği">
    <h1 class="mt-8 text-2xl font-bold">Hesap güvenliği</h1>
    <p class="mt-2 text-slate-600">Doğrulanmış e-posta: {{ $user->email }}</p>
    @unless($emailOtpEnabled)
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">E-posta OTP sistemi supervisor tarafından geçici olarak pasif durumda. Hassas değişiklikler mevcut şifreniz doğrulanarak tamamlanır.</div>
    @endunless

    @if($canManageTwoFactor)
        <section class="mt-8 rounded-xl border border-slate-200 p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold">SMS ile iki aşamalı giriş</h2>
                    <p class="mt-1 text-sm text-slate-600">Durum: <strong>{{ $user->two_factor_enabled ? 'Aktif' : 'Pasif' }}</strong></p>
                    <p class="mt-1 text-sm text-slate-600">Aktif olduğunda şifrenizden sonra {{ $user->phone }} numarasına gönderilen kod istenir.</p>
                    @if(!$user->phone_verified_at)
                        <p class="mt-2 text-sm font-medium text-amber-700">Telefonunuz henüz doğrulanmadı. İlk başarılı SMS girişinde doğrulanmış olarak kaydedilir.</p>
                    @endif
                </div>
            </div>

            <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('account.two-factor.update') }}">
                @csrf
                <input type="hidden" name="enabled" value="{{ $user->two_factor_enabled ? '0' : '1' }}">
                <input class="rounded-lg border-slate-300" type="password" name="current_password" placeholder="Mevcut şifre" required>
                <button class="rounded-lg px-4 py-3 font-bold text-white {{ $user->two_factor_enabled ? 'bg-red-600' : 'bg-emerald-600' }}">
                    {{ $user->two_factor_enabled ? 'İki aşamalı doğrulamayı kapat' : 'İki aşamalı doğrulamayı aç' }}
                </button>
            </form>
        </section>
    @endif

    <section class="mt-8 border-t pt-6">
        <h2 class="text-lg font-bold">E-posta adresini değiştir</h2>
        <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('account.email.request') }}">@csrf
            <input class="rounded-lg border-slate-300" type="email" name="new_email" placeholder="Yeni e-posta" required>
            <input class="rounded-lg border-slate-300" type="password" name="current_password" placeholder="Mevcut şifre" required>
            <button class="rounded-lg bg-amber-500 px-4 py-3 font-bold text-white sm:col-span-2">{{ $emailOtpEnabled ? 'Yeni adrese kod gönder' : 'E-posta adresini değiştir' }}</button>
        </form>
        @if($emailOtpEnabled && session()->has('pending_new_email'))
            <form class="mt-4 flex gap-3" method="post" action="{{ route('account.email.confirm') }}">@csrf<input class="flex-1 rounded-lg border-slate-300" name="code" maxlength="6" placeholder="6 haneli kod" required><button class="rounded-lg bg-slate-900 px-5 text-white">Değişikliği onayla</button></form>
        @endif
    </section>

    <section class="mt-8 border-t pt-6">
        <h2 class="text-lg font-bold">Şifreyi değiştir</h2>
        <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('account.password.request') }}">@csrf
            <input class="rounded-lg border-slate-300 sm:col-span-2" type="password" name="current_password" placeholder="Mevcut şifre" required>
            <input class="rounded-lg border-slate-300" type="password" name="password" placeholder="Yeni şifre" required>
            <input class="rounded-lg border-slate-300" type="password" name="password_confirmation" placeholder="Yeni şifre tekrar" required>
            <button class="rounded-lg bg-amber-500 px-4 py-3 font-bold text-white sm:col-span-2">{{ $emailOtpEnabled ? 'E-posta kodu gönder' : 'Şifreyi değiştir' }}</button>
        </form>
        @if($emailOtpEnabled && session()->has('pending_password_hash'))
            <form class="mt-4 flex gap-3" method="post" action="{{ route('account.password.confirm') }}">@csrf<input class="flex-1 rounded-lg border-slate-300" name="code" maxlength="6" placeholder="6 haneli kod" required><button class="rounded-lg bg-slate-900 px-5 text-white">Şifreyi değiştir</button></form>
        @endif
    </section>
</x-layouts.auth>
