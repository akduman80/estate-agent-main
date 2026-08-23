<x-layouts.auth title="Hesap Güvenliği">
    <h1 class="mt-8 text-2xl font-bold">Hesap güvenliği</h1>
    <p class="mt-2 text-slate-600">Doğrulanmış e-posta: {{ $user->email }}</p>

    <section class="mt-8 border-t pt-6">
        <h2 class="text-lg font-bold">E-posta adresini değiştir</h2>
        <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('account.email.request') }}">@csrf
            <input class="rounded-lg border-slate-300" type="email" name="new_email" placeholder="Yeni e-posta" required>
            <input class="rounded-lg border-slate-300" type="password" name="current_password" placeholder="Mevcut şifre" required>
            <button class="rounded-lg bg-amber-500 px-4 py-3 font-bold text-white sm:col-span-2">Yeni adrese kod gönder</button>
        </form>
        @if(session()->has('pending_new_email'))
            <form class="mt-4 flex gap-3" method="post" action="{{ route('account.email.confirm') }}">@csrf<input class="flex-1 rounded-lg border-slate-300" name="code" maxlength="6" placeholder="6 haneli kod" required><button class="rounded-lg bg-slate-900 px-5 text-white">Değişikliği onayla</button></form>
        @endif
    </section>

    <section class="mt-8 border-t pt-6">
        <h2 class="text-lg font-bold">Şifreyi değiştir</h2>
        <form class="mt-4 grid gap-4 sm:grid-cols-2" method="post" action="{{ route('account.password.request') }}">@csrf
            <input class="rounded-lg border-slate-300 sm:col-span-2" type="password" name="current_password" placeholder="Mevcut şifre" required>
            <input class="rounded-lg border-slate-300" type="password" name="password" placeholder="Yeni şifre" required>
            <input class="rounded-lg border-slate-300" type="password" name="password_confirmation" placeholder="Yeni şifre tekrar" required>
            <button class="rounded-lg bg-amber-500 px-4 py-3 font-bold text-white sm:col-span-2">E-posta kodu gönder</button>
        </form>
        @if(session()->has('pending_password_hash'))
            <form class="mt-4 flex gap-3" method="post" action="{{ route('account.password.confirm') }}">@csrf<input class="flex-1 rounded-lg border-slate-300" name="code" maxlength="6" placeholder="6 haneli kod" required><button class="rounded-lg bg-slate-900 px-5 text-white">Şifreyi değiştir</button></form>
        @endif
    </section>
</x-layouts.auth>
