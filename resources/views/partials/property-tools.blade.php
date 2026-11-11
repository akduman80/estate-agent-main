<div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
    <a href="{{ route('tools.parcel') }}" class="rounded-xl bg-white px-4 py-3 font-bold text-blue-950">Arsa ve Parsel Sorgula</a>
    <a href="{{ route('tools.rent') }}" class="rounded-xl bg-white px-4 py-3 font-bold text-blue-950">Kira Artışını Hesapla</a>
</div>
@once @push('scripts')<script src="{{ asset('js/property-search-voice.js') }}" defer></script>@endpush @endonce