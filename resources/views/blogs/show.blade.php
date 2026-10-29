<x-layouts.modern :title="$post->title.' | ZYDEMLAK'" :description="strip_tags($post->summary ?? '') ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 155)">
    @php
        $imageUrl = $post->news_poster_url ?: asset('fe-assets/assets/img/blog2.jpg');
        $publishedDate = ($post->published_at ?? $post->created_at)?->locale('tr')->translatedFormat('d F Y');
        $sitePhone = \App\Models\Setting::get('site_phone', '0530 360 77 69');
        $phoneDigits = preg_replace('/\D+/', '', $sitePhone);
    @endphp

    @push('head')
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $post->title }}">
        <meta property="og:description" content="{{ strip_tags($post->summary ?? '') ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 155) }}">
        <meta property="og:image" content="{{ $imageUrl }}">
        <meta property="og:url" content="{{ route('blogs.show', $post->slug) }}">
        @if($post->news_video_url && $post->news_poster_url && !empty($post->news_media['duration']))
            <script type="application/ld+json">{!! json_encode([
                '@context' => 'https://schema.org', '@type' => 'VideoObject',
                'name' => ($post->news_media['title'] ?? null) ?: $post->title,
                'description' => ($post->news_media['description'] ?? null) ?: strip_tags($post->summary ?? $post->title),
                'thumbnailUrl' => [$post->news_poster_url],
                'uploadDate' => ($post->published_at ?? $post->created_at)->toIso8601String(),
                'duration' => 'PT'.(int)ceil($post->news_media['duration']).'S',
                'contentUrl' => $post->news_video_url,
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
    @endpush

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-4 text-xs text-slate-500 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="hover:text-orange-500">Anasayfa</a>
            <i class="fa-solid fa-chevron-right text-[9px]"></i>
            <a href="{{ route('blogs.index') }}" class="hover:text-orange-500">Emlak Haberleri</a>
            <i class="fa-solid fa-chevron-right text-[9px]"></i>
            <span class="max-w-64 truncate font-semibold text-slate-700">{{ $post->title }}</span>
        </div>
    </section>

    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <article class="lg:col-span-8">
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <header class="p-6 md:p-8">
                        @if($post->category)
                            <a href="{{ route('blogs.index', ['kategori' => $post->category_id]) }}" class="mb-4 inline-block rounded-lg bg-orange-100 px-3 py-1.5 text-xs font-bold uppercase text-orange-600">{{ $post->category->name }}</a>
                        @endif
                        <h1 class="text-2xl font-extrabold leading-tight text-blue-950 md:text-4xl">{{ $post->title }}</h1>
                        <div class="mt-5 flex flex-wrap gap-4 text-xs text-slate-500">
                            <span><i class="fa-regular fa-calendar mr-1 text-orange-500"></i>{{ $publishedDate }}</span>
                            <span><i class="fa-solid fa-user mr-1 text-orange-500"></i>{{ $post->author?->name ?? 'ZYDEMLAK Haber Merkezi' }}</span>
                        </div>
                    </header>

                    <x-news-video :post="$post" />
                    @if($post->cover_image || (!$post->news_video_url && !\App\Services\NewsVideo::youtubeId($post->news_media['youtube_url'] ?? null)))
                        <img src="{{ $imageUrl }}" alt="{{ $post->title }}" class="h-[280px] w-full object-cover sm:h-[420px]">
                    @endif

                    <div class="article-content p-6 text-[15px] text-slate-700 md:p-8">
                        @if($post->summary)
                            <div class="article-lead">{!! \App\Support\RichContentHtml::render($post->summary) !!}</div>
                        @endif
                        {!! \App\Support\RichContentHtml::render($post->content) !!}

                        <div class="mt-8 border-t border-slate-100 pt-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold text-slate-500">Kategori:</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-600">{{ $post->category?->name ?? 'Emlak Haberleri' }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-600">Gayrimenkul</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs text-slate-600">Osmaniye</span>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col justify-between gap-4 border-t border-slate-100 pt-6 sm:flex-row sm:items-center">
                            <strong class="text-sm text-blue-950">Haberi Paylaş</strong>
                            <div class="flex gap-2">
                                <button type="button" data-share="whatsapp" class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500 text-white transition hover:bg-emerald-600" aria-label="WhatsApp'ta paylaş"><i class="fa-brands fa-whatsapp"></i></button>
                                <button type="button" data-share="facebook" class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white transition hover:bg-blue-700" aria-label="Facebook'ta paylaş"><i class="fa-brands fa-facebook-f"></i></button>
                                <button type="button" data-share="copy" class="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-700 text-white transition hover:bg-slate-800" aria-label="Bağlantıyı kopyala"><i class="fa-solid fa-link"></i></button>
                                <span data-copy-feedback class="hidden self-center text-xs font-semibold text-emerald-600">Kopyalandı</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($previousPost || $nextPost)
                    <nav class="mt-6 grid gap-3 sm:grid-cols-2" aria-label="Haberler arası geçiş">
                        @if($previousPost)
                            <a href="{{ route('blogs.show', $previousPost->slug) }}" class="rounded-xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 transition hover:border-orange-300 hover:text-orange-600"><i class="fa-solid fa-arrow-left mr-2"></i>{{ \Illuminate\Support\Str::limit($previousPost->title, 45) }}</a>
                        @endif
                        @if($nextPost)
                            <a href="{{ route('blogs.show', $nextPost->slug) }}" class="rounded-xl border border-slate-200 bg-white p-4 text-right text-sm font-semibold text-slate-700 transition hover:border-orange-300 hover:text-orange-600 sm:col-start-2">{{ \Illuminate\Support\Str::limit($nextPost->title, 45) }}<i class="fa-solid fa-arrow-right ml-2"></i></a>
                        @endif
                    </nav>
                @endif
            </article>

            <aside class="space-y-6 lg:col-span-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 border-b border-slate-100 pb-3 font-bold text-blue-950"><i class="fa-solid fa-newspaper mr-2 text-orange-500"></i>Son Haberler</h2>
                    <div class="space-y-4 divide-y divide-slate-100">
                        @forelse($latestPosts as $latestPost)
                            <div class="pt-4 first:pt-0"><x-news-mini-card :post="$latestPost" /></div>
                        @empty
                            <p class="text-xs text-slate-500">Başka yayınlanmış haber bulunmuyor.</p>
                        @endforelse
                    </div>
                    <a href="{{ route('blogs.index') }}" class="mt-5 block rounded-lg bg-orange-50 py-2.5 text-center text-xs font-bold text-orange-600 transition hover:bg-orange-100">Tüm Haberleri Gör</a>
                </div>

                <div class="rounded-2xl bg-blue-950 p-6 text-white">
                    <i class="fa-solid fa-location-dot text-2xl text-orange-400"></i>
                    <h2 class="mt-3 text-lg font-bold">Osmaniye'nin Emlak İlan ve Haber Sitesi</h2>
                    <p class="mt-3 text-xs leading-6 text-slate-300">Osmaniye'deki güncel emlak ilanları, gayrimenkul haberleri ve sektörel gelişmeler ZYDEMLAK'ta.</p>
                    <a href="{{ route('listings.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-3 text-xs font-bold text-white transition hover:bg-orange-600">Güncel İlanları Gör <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="font-bold text-blue-950"><i class="fa-solid fa-headset mr-2 text-orange-500"></i>Gayrimenkul Danışmanlığı</h2>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Satılık, kiralık ve yatırım amaçlı gayrimenkuller hakkında bilgi almak için bizimle iletişime geçebilirsiniz.</p>
                    <a href="tel:{{ $phoneDigits }}" class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-orange-500 py-3 font-bold text-white transition hover:bg-orange-600"><i class="fa-solid fa-phone"></i>{{ $sitePhone }}</a>
                    @if($phoneDigits)
                        <a href="https://wa.me/{{ str_starts_with($phoneDigits, '90') ? $phoneDigits : '90'.ltrim($phoneDigits, '0') }}" target="_blank" rel="noopener" class="mt-2 flex items-center justify-center gap-2 rounded-xl bg-emerald-500 py-3 font-bold text-white transition hover:bg-emerald-600"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>
                    @endif
                </div>
            </aside>
        </div>

        @if($relatedPosts->isNotEmpty())
            <section class="mt-10">
                <div class="mb-5 border-b border-slate-200 pb-4">
                    <h2 class="text-2xl font-bold text-blue-950">İlginizi Çekebilecek Haberler</h2>
                    <p class="mt-1 text-xs text-slate-500">Emlak sektöründen diğer gelişmeler</p>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach($relatedPosts as $relatedPost)
                        <x-news-card :post="$relatedPost" />
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    @push('scripts')
        <script>
            document.querySelectorAll('[data-share]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const url = encodeURIComponent(window.location.href);
                    const title = encodeURIComponent(document.title);

                    if (button.dataset.share === 'whatsapp') {
                        window.open(`https://wa.me/?text=${title}%20${url}`, '_blank', 'noopener');
                    } else if (button.dataset.share === 'facebook') {
                        window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank', 'noopener');
                    } else {
                        await navigator.clipboard.writeText(window.location.href);
                        const feedback = document.querySelector('[data-copy-feedback]');
                        feedback?.classList.remove('hidden');
                        window.setTimeout(() => feedback?.classList.add('hidden'), 1800);
                    }
                });
            });
        </script>
    @endpush
</x-layouts.modern>
