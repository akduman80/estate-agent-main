<x-layouts.auth title="Yeni Şifre">
    <h1 class="mt-8 text-2xl font-bold">Yeni şifrenizi belirleyin</h1>
    <form class="mt-8 space-y-5" method="post" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label class="block"><span class="text-sm font-medium">E-posta</span><input class="mt-1 w-full rounded-lg border-slate-300" type="email" name="email" value="{{ old('email', $email) }}" required></label>
        <label class="block"><span class="text-sm font-medium">Yeni şifre</span><input class="mt-1 w-full rounded-lg border-slate-300" type="password" name="password" required></label>
        <label class="block"><span class="text-sm font-medium">Yeni şifre tekrar</span><input class="mt-1 w-full rounded-lg border-slate-300" type="password" name="password_confirmation" required></label>
        <button class="w-full rounded-lg bg-amber-500 px-5 py-3 font-bold text-white">Şifreyi yenile</button>
    </form>
</x-layouts.auth>
