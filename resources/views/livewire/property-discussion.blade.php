<section class="my-6 min-w-0 rounded-2xl border border-slate-200 bg-white p-4 sm:p-6" aria-label="{{ $targetType === 'listing' ? 'Sorular ve Cevaplar' : 'Yorumlar' }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-blue-950">{{ $targetType === 'listing' ? 'Sorular ve Cevaplar' : 'Yorumlar' }}</h2>
        <button type="button" wire:click="like({{ $liked ? 'false' : 'true' }})" wire:loading.attr="disabled" aria-pressed="{{ $liked ? 'true' : 'false' }}" class="rounded-xl border px-4 py-3 font-bold text-blue-950">{{ $liked ? '♥ Beğeniyi geri al' : '♡ Beğen' }} ({{ $likeCount }})</button>
    </div>
    <p class="my-3 text-sm text-slate-600">Herkese açık alandır. Telefon, kimlik numarası, özel adres, bağlantı veya diğer kişisel bilgileri paylaşmayın. Özel görüşmeler için mevcut iletişim kanallarını kullanın.</p>
    @if($notice)<p role="status" class="my-3 rounded-lg bg-blue-50 p-3">{{ $notice }}</p>@endif
    <label class="block text-sm">Tarih sırası<select wire:model.live="sort" class="ml-2 rounded-lg border p-2"><option value="oldest">Önce eskiler</option><option value="newest">Önce yeniler</option></select></label>
    <div class="my-4 space-y-4">
        @forelse($comments as $comment)
            <article wire:key="comment-{{ $comment->id }}" class="min-w-0 border-t pt-3">
                <p class="text-sm font-bold">{{ $comment->status === 'deleted' ? 'Silinen içerik' : ($comment->user?->name ?? 'Üyeliği silinmiş kullanıcı') }} <time class="font-normal text-slate-500">{{ $comment->created_at->format('d.m.Y H:i') }}</time></p>
                <p class="my-2 whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ $comment->status === 'deleted' ? 'Bu içerik kaldırıldı. Onaylanmış yanıtlar korunur.' : $comment->body }}</p>
                @if($comment->status !== 'deleted')<div class="flex flex-wrap gap-3 text-sm">
                    @if($canAnswer)<button type="button" wire:click="reply({{ $comment->id }})" class="min-h-11 font-bold text-blue-900">Yanıtla</button>@endif
                    <button type="button" wire:click="report({{ $comment->id }})" wire:loading.attr="disabled" class="min-h-11 text-slate-600">Şikâyet et</button>
                    @if(auth()->id() === $comment->user_id)<button type="button" wire:click="edit({{ $comment->id }})" class="min-h-11">Düzenle</button><button type="button" wire:click="remove({{ $comment->id }})" wire:confirm="İçeriğiniz kaldırılsın mı?" class="min-h-11">Sil</button>@endif
                </div>@endif
                @foreach(($replyThread === $comment->id && $threadReplies ? $threadReplies : $comment->replies) as $reply)<div wire:key="reply-{{ $reply->id }}" class="my-2 min-w-0 rounded-lg bg-slate-50 p-3">
                    @php($authorLabel = $targetType === 'listing' && $reply->kind === 'answer' && $reply->user ? app(\App\Services\PropertyInteractions::class)->answerLabel($target, $reply->user) : null)
                    <p class="text-sm font-bold">{{ $reply->user?->name ?? 'Üyeliği silinmiş kullanıcı' }} @if($reply->status !== 'deleted' && $authorLabel)<span class="rounded bg-blue-100 px-2 py-1">{{ $authorLabel }}</span>@endif <time class="font-normal text-slate-500">{{ $reply->created_at->format('d.m.Y H:i') }}</time></p>
                    <p class="whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ $reply->status === 'deleted' ? 'Bu yanıt kaldırıldı.' : $reply->body }}</p>
                    @if($reply->status !== 'deleted')<button type="button" wire:click="report({{ $reply->id }})" class="min-h-11 text-sm">Şikâyet et</button>@if(auth()->id() === $reply->user_id)<button type="button" wire:click="edit({{ $reply->id }})" class="min-h-11 px-3 text-sm">Düzenle</button><button type="button" wire:click="remove({{ $reply->id }})" wire:confirm="Yanıtınız kaldırılsın mı?" class="min-h-11 text-sm">Sil</button>@endif @endif
                </div>@endforeach
                @if($replyThread === $comment->id && $threadReplies){{ $threadReplies->links() }}@elseif($comment->visible_replies_count > 3)<button type="button" wire:click="showReplies({{ $comment->id }})" class="min-h-11 font-bold text-blue-900">Tüm yanıtları görüntüle ({{ $comment->visible_replies_count }})</button>@endif
            </article>
        @empty<p class="text-sm text-slate-500">Henüz onaylanmış içerik yok.</p>@endforelse
    </div>
    {{ $comments->links() }}
    @auth
        <a href="{{ route('property.notifications') }}" class="my-3 inline-block text-sm font-bold text-blue-900">Bildirimlerim ve bildirim tercihlerim</a>
        <form wire:submit="submit" class="mt-4">
            <label class="block font-bold" for="discussion-body-{{ $targetType }}-{{ $targetId }}">{{ $editing ? 'İçeriği düzenle' : ($replyTo ? 'Yanıt yaz' : ($targetType === 'listing' ? 'İlan sahibine soru sor' : 'Yorum yaz')) }}</label>
            <textarea id="discussion-body-{{ $targetType }}-{{ $targetId }}" wire:model="body" required minlength="3" maxlength="2000" rows="4" class="my-2 block w-full rounded-xl border p-3"></textarea>
            @error('body')<p role="alert" class="text-red-700">{{ $message }}</p>@enderror
            <button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-orange-500 px-4 py-3 font-bold text-white">Gönder</button>
            @if($replyTo || $editing)<button type="button" wire:click="cancel" class="rounded-xl border p-3">Vazgeç</button>@endif
            <span wire:loading role="status">İşleniyor…</span>
        </form>
    @else<a href="{{ route('login') }}" class="my-3 inline-block rounded-xl bg-blue-950 p-3 font-bold text-white">Yorum, soru veya beğeni için giriş yapın</a>@endauth
</section>
