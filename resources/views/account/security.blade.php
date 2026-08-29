<x-layouts.auth title="Hesap Güvenliği">
    <h1 class="mt-8 text-2xl font-bold">Hesap güvenliği</h1>
    <p class="mt-2 text-slate-600">Doğrulanmış e-posta: {{ $user->email }}</p>
    @unless($emailOtpEnabled)
        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">E-posta OTP sistemi supervisor tarafından geçici olarak pasif durumda. Hassas değişiklikler mevcut şifreniz doğrulanarak tamamlanır.</div>
    @endunless

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
