<x-layouts.modern title="Projeler | ZYDEMLAK" description="İnşaat firmaları ve müteahhitlerden güncel konut ve ticari gayrimenkul projeleri.">
    <main class="flex-1 bg-slate-50">
        <section class="bg-blue-950 py-10 text-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-xs font-bold uppercase tracking-[.2em] text-orange-400">Yeni yaşam ve yatırım alanları</p>
                <h1 class="mt-2 text-3xl font-extrabold">Projeler</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100">Müteahhit ve inşaat firmalarının devam eden veya tamamlanan projelerini keşfedin.</p>
            </div>
        </section>
        <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($projects as $project)
                    <x-project-card :project="$project" />
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 sm:col-span-2 lg:col-span-3">Henüz yayınlanmış proje bulunmuyor.</div>
                @endforelse
            </div>
            @if($projects->hasPages())<div class="mt-8">{{ $projects->links() }}</div>@endif
        </section>
    </main>
</x-layouts.modern>
