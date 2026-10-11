@if(!empty($item->extra_fields))
<section class="mt-6 rounded-xl border border-slate-200 bg-white p-4" aria-label="Seçilmiş ek bilgiler">
    <h2 class="mb-3 font-bold text-blue-950">Ek bilgiler</h2>
    <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        @foreach($item->extra_fields as $field)
            <div><dt class="text-sm font-bold">{{ $field['ad'] }}</dt><dd class="whitespace-pre-wrap text-sm">{!! \App\Support\RichContentHtml::render($field['deger']) !!}</dd></div>
        @endforeach
    </dl>
</section>
@endif
@foreach($item->mobile_video_paths ?? [] as $path)
    @if($path !== $item->video_path)
        <video class="mt-4 w-full rounded-xl" controls playsinline preload="metadata" aria-label="Seçilmiş ek video"><source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}"></video>
    @endif
@endforeach
