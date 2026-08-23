<x-layouts.auth title="Şifremi Unuttum">
    <h1 class="mt-8 text-2xl font-bold">Şifrenizi sıfırlayın</h1>
    <p class="mt-2 text-slate-600">Kayıtlı e-posta adresinize tek kullanımlık bir sıfırlama bağlantısı göndereceğiz.</p>
    <form class="mt-8" method="post" action="{{ route('password.email') }}">@csrf
        <label class="block"><span class="text-sm font-medium">E-posta</span><input class="mt-1 w-full rounded-lg border-slate-300" type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        <button class="mt-5 w-full rounded-lg bg-amber-500 px-5 py-3 font-bold text-white">Sıfırlama bağlantısı gönder</button>
    </form>
</x-layouts.auth>
