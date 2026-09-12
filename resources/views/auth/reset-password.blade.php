<x-layouts.auth title="Yeni Şifre">
    <h1 class="mt-8 text-2xl font-bold">Yeni şifrenizi belirleyin</h1>
    <p class="mt-2 text-slate-600">E-postanıza gelen 6 haneli kodu girerek şifrenizi yenileyin.</p>
    <form class="mt-8 space-y-5" method="post" action="{{ route('password.update') }}">@csrf
        <label class="block"><span class="text-sm font-medium">E-posta</span><input class="mt-1 w-full rounded-lg border-slate-300" type="email" name="email" value="{{ old('email', $email) }}" required></label>
        <label class="block"><span class="text-sm font-medium">Kod</span><input class="mt-1 w-full rounded-lg border-slate-300 text-center text-2xl tracking-[.5em]" name="code" value="{{ old('code') }}" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus></label>
        <label class="block"><span class="text-sm font-medium">Yeni şifre</span><input class="mt-1 w-full rounded-lg border-slate-300" type="password" name="password" required></label>
        <label class="block"><span class="text-sm font-medium">Yeni şifre tekrar</span><input class="mt-1 w-full rounded-lg border-slate-300" type="password" name="password_confirmation" required></label>
        <button class="w-full rounded-lg bg-amber-500 px-5 py-3 font-bold text-white">Şifreyi yenile</button>
    </form>
    <form class="mt-4 text-center" method="post" action="{{ route('password.email') }}">@csrf
        <input type="hidden" name="email" value="{{ old('email', $email) }}">
        <button class="text-sm font-medium text-amber-700 underline">Kod gelmedi mi? Yeni kod gönder</button>
    </form>
</x-layouts.auth>
