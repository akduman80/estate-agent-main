<x-layouts.auth :title="$document->title">
    <article class="prose prose-slate mt-8 max-w-none">
        <h1>{{ $document->title }}</h1>
        <p class="text-sm text-slate-500">Sürüm {{ $document->version }} · {{ optional($document->published_at)->format('d.m.Y') }}</p>
        {!! $document->content !!}
    </article>
</x-layouts.auth>
