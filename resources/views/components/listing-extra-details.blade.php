@php($details = $listing->form_details ?? [])
@php($extraFields = ['open_area'=>'Açık alan m²','bathrooms'=>'Banyo sayısı','kitchen'=>'Mutfak tipi','balcony'=>'Balkon','energy_class'=>'Enerji kimlik belgesi','rental_period'=>'Kira dönemi'])
@if(collect($extraFields)->keys()->contains(fn ($key) => filled($details[$key] ?? null)) || !empty($details['features']))
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <h2 class="mb-4 text-xl font-extrabold text-blue-950">Detaylı özellikler</h2>
    <dl class="grid gap-3 sm:grid-cols-2">
        @foreach($extraFields as $key=>$label)
            @if(filled($details[$key] ?? null))<div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="text-sm font-semibold">{{ $details[$key] }}</dd></div>@endif
        @endforeach
        @foreach(['interior'=>'İç özellikler','exterior'=>'Dış özellikler','neighborhood'=>'Muhit','transport'=>'Ulaşım','view'=>'Manzara','housing'=>'Konut tipi','accessible'=>'Engelliye ve yaşlıya uygunluk'] as $key=>$label)
            @if(!empty($details['features'][$key]['values']) && empty($details['features'][$key]['declined']))
            <div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="text-sm font-semibold">{{ implode(' · ', $details['features'][$key]['values']) }}</dd></div>
            @endif
        @endforeach
    </dl>
</section>
@endif
@if(!empty($details['virtual_tour']))
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="mb-4 text-xl font-extrabold text-blue-950">Sanal tur</h2><a href="{{ $details['virtual_tour'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-orange-600 underline">Sanal turu güvenli yeni sekmede aç</a></section>
@endif
