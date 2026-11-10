<div class="flex min-h-full flex-1 flex-col">
    <section class="bg-blue-950 py-12 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <span class="text-xs font-bold tracking-[0.24em] text-orange-400">EMLAK HABERLERİ</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">Güncel Emlak Haberleri</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300 md:text-base">Osmaniye ve Türkiye'den gayrimenkul, konut, kira, tapu, yatırım ve emlak sektöründeki önemli gelişmeleri takip edin.</p>
        </div>
    </section>

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-4 text-xs text-slate-500 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="hover:text-orange-500">Anasayfa</a>
            <i class="fa-solid fa-chevron-right text-[9px]"></i>
            <span class="font-semibold text-slate-700">Emlak Haberleri</span>
        </div>
    </section>

    <main class="mx-auto w-full max-w-7xl flex-grow px-4 py-10 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            <section class="lg:col-span-9">
                <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-2xl font-bold text-blue-950">Son Haberler</h2>
                        <p class="mt-1 text-xs text-slate-500">En güncel emlak ve gayrimenkul haberleri</p>
                    </div>
                    <label class="block"><span class="sr-only">Haber sıralaması</span><select wire:model.live="sort" class="w-full rounded-xl border border-slate-200 p-3"><option value="newest">En yeni haberler</option><option value="likes">En çok beğenilenler</option><option value="comments">En çok yorumlananlar</option></select></label>
                    <label class="relative block">
                        <span class="sr-only">Haber ara</span>
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input wire:model.live.debounce.350ms="search" type="search" placeholder="Haber ara..." class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-200 sm:w-64">
                    </label>
                </div>

                <div class="mb-7 flex flex-wrap gap-2">
                    <button wire:click="selectCategory" type="button" class="rounded-lg px-4 py-2 text-xs font-bold transition {{ ! $selectedCategory ? 'bg-blue-950 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:border-orange-400' }}">Tümü</button>
                    @foreach($categories as $category)
                        <button wire:click="selectCategory({{ $category->id }})" type="button" class="rounded-lg px-4 py-2 text-xs font-bold transition {{ (string) $selectedCategory === (string) $category->id ? 'bg-blue-950 text-white' : 'border border-slate-200 bg-white text-slate-600 hover:border-orange-400' }}">
                            {{ $category->name }} <span class="ml-1 opacity-60">{{ $category->posts_count }}</span>
                        </button>
                    @endforeach
                </div>

                <div wire:loading.class="opacity-50" class="grid grid-cols-1 gap-6 transition md:grid-cols-2 xl:grid-cols-3">
                    @forelse($posts as $post)
                        <x-news-card :post="$post" />
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center md:col-span-2 xl:col-span-3">
                            <i class="fa-regular fa-newspaper mb-4 block text-4xl text-orange-400"></i>
                            <h3 class="font-bold text-blue-950">Sonuç bulunamadı</h3>
                            <p class="mt-2 text-sm text-slate-500">Arama kelimenizi veya kategori seçiminizi değiştirebilirsiniz.</p>
                        </div>
                    @endforelse
                </div>

                @if($posts->hasPages())
                    <div class="mt-8">{{ $posts->links() }}</div>
                @endif
            </section>

            <aside class="space-y-6 lg:col-span-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 border-b border-slate-100 pb-3 font-bold text-blue-950"><i class="fa-solid fa-layer-group mr-2 text-orange-500"></i>Haber Kategorileri</h2>
                    <div class="space-y-1">
                        @foreach($categories as $category)
                            <button wire:click="selectCategory({{ $category->id }})" type="button" class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-xs font-semibold text-slate-600 transition hover:bg-orange-50 hover:text-orange-600">
                                <span>{{ $category->name }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px]">{{ $category->posts_count }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl bg-blue-950 p-6 text-white">
                    <i class="fa-solid fa-location-dot text-2xl text-orange-400"></i>
                    <h3 class="mt-3 text-lg font-bold">Osmaniye'nin Emlak İlan ve Haber Sitesi</h3>
                    <p class="mt-3 text-xs leading-6 text-slate-300">Osmaniye'deki güncel emlak ilanları, gayrimenkul haberleri ve sektörel gelişmeler ZYDEMLAK'ta.</p>
                    <a href="{{ route('listings.index') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-3 text-xs font-bold text-white transition hover:bg-orange-600">Güncel İlanları Gör <i class="fa-solid fa-arrow-right"></i></a>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="font-bold text-blue-950"><i class="fa-solid fa-headset mr-2 text-orange-500"></i>Gayrimenkul Danışmanlığı</h3>
                    <p class="mt-3 text-xs leading-5 text-slate-500">Satılık, kiralık ve yatırım amaçlı gayrimenkuller hakkında bilgi almak için bizimle iletişime geçebilirsiniz.</p>
                    <a href="{{ route('contact') }}" class="mt-4 flex items-center justify-center gap-2 rounded-xl bg-orange-500 py-3 font-bold text-white transition hover:bg-orange-600"><i class="fa-solid fa-envelope"></i>Bize Ulaşın</a>
                </div>
            </aside>
        </div>
    </main>
</div>
