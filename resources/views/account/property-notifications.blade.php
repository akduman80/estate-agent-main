<x-layouts.modern title="Etkileşim Bildirimleri | ZYDEMLAK">
    <main class="mx-auto w-full max-w-3xl px-4 py-8">
        <h1 class="text-2xl font-bold">Etkileşim Bildirimleri</h1>
        <form method="POST" action="{{ route('property.notifications.update') }}" class="my-4">@csrf @method('PATCH')
            <label><input type="checkbox" name="enabled" value="1" @checked($enabled)> Site içi soru ve yanıt bildirimlerini al</label>
            <button class="ml-3 rounded-lg border p-3">Kaydet</button>
        </form>
        @if(session('status'))<p role="status">{{ session('status') }}</p>@endif
        @forelse($notices as $notice)
            @php($comment = $comments->get($notice->comment_id))
            <article class="my-3 rounded-xl border p-4"><p>{{ $notice->message }}</p><p class="text-xs">{{ $notice->created_at }}</p>
                @if($comment?->listing)<a class="font-bold text-blue-900" href="{{ route('listings.show', $comment->listing) }}">İlana git</a>@elseif($comment?->post)<a class="font-bold text-blue-900" href="{{ route('blogs.show', $comment->post) }}">Habere git</a>@endif
                @unless($notice->read_at)<form method="POST" action="{{ route('property.notifications.read', $notice->id) }}">@csrf<button class="mt-2 rounded border p-2">Okundu işaretle</button></form>@endunless
            </article>
        @empty<p>Henüz bildirim yok.</p>@endforelse
        {{ $notices->links() }}
    </main>
</x-layouts.modern>
