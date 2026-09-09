<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'ZYDEMLAK' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.pwa', ['pwaAdmin' => false])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-4xl items-center justify-center px-4 py-12">
        <div class="w-full rounded-2xl bg-white p-6 shadow-xl sm:p-10">
            <a href="{{ route('home') }}" class="text-2xl font-black tracking-tight text-amber-600">ZYDEMLAK</a>
            @if (session('status'))
                <div class="mt-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-6 rounded-lg bg-red-50 p-4 text-sm text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
            {{ $slot }}
        </div>
    </main>
</body>
</html>
