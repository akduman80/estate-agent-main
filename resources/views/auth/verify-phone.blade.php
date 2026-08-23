<x-layouts.auth title="Telefon Doğrulama">
    <h1 class="mt-8 text-2xl font-bold">Telefon numaranızı doğrulayın</h1>
    <p class="mt-2 text-slate-600">EİDS kimlik doğrulaması için {{ $user->phone }} numarasının OTP ile doğrulanması zorunludur.</p>
    <form class="mt-6" method="post" action="{{ route('phone.verification.send') }}">@csrf<button class="w-full rounded-lg border border-amber-500 px-5 py-3 font-bold text-amber-700">Telefonuma kod gönder</button></form>
    <form class="mt-5" method="post" action="{{ route('phone.verification.verify') }}">@csrf
        <input class="w-full rounded-lg border-slate-300 text-center text-2xl tracking-[.5em]" name="code" inputmode="numeric" maxlength="6" required>
        <button class="mt-5 w-full rounded-lg bg-amber-500 px-5 py-3 font-bold text-white">Telefonu doğrula</button>
    </form>
</x-layouts.auth>
