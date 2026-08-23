<x-layouts.auth title="İki Aşamalı Giriş">
    <h1 class="mt-8 text-2xl font-bold">İkinci doğrulama aşaması</h1>
    <p class="mt-2 text-slate-600">Kayıtlı e-posta adresinize gönderilen 6 haneli giriş kodunu girin.</p>
    <form class="mt-8" method="post" action="{{ route('two-factor.verify') }}">@csrf
        <input class="w-full rounded-lg border-slate-300 text-center text-2xl tracking-[.5em]" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus>
        <button class="mt-5 w-full rounded-lg bg-amber-500 px-5 py-3 font-bold text-white">Girişi tamamla</button>
    </form>
    <form class="mt-4 text-center" method="post" action="{{ route('two-factor.resend') }}">@csrf<button class="text-sm font-medium text-amber-700 underline">Kod gelmedi mi? Yeni kod gönder</button></form>
</x-layouts.auth>
