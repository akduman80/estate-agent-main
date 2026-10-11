@php
    $gallery = $project->images
        ->pluck('image_path')
        ->prepend($project->cover_image)
        ->filter()
        ->unique()
        ->map(fn ($path) => \Illuminate\Support\Facades\Storage::disk('public')->url($path))
        ->values();
    $gallery = $gallery->whenEmpty(fn ($images) => $images->push(asset('fe-assets/assets/img/demo/property-4.jpg')));
    $videoUrl = $project->video_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($project->video_path) : null;
    $youtubeId = $project->youtube_video_id;
    $slideCount = $gallery->count() + (($videoUrl || $youtubeId) ? 1 : 0);
    $statusLabel = match ($project->status) { 'planning' => 'Planlanıyor', 'completed' => 'Tamamlandı', default => 'Devam Ediyor' };
@endphp
<x-layouts.modern :title="$project->title.' | ZYDEMLAK'" :description="strip_tags($project->summary ?? '')">
    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <section data-project-slider class="relative bg-slate-950" aria-label="{{ $project->title }} fotoğraf galerisi" tabindex="0">
                <div class="relative aspect-[4/3] max-h-[620px] w-full overflow-hidden sm:aspect-[16/9]">
                    @foreach($gallery as $index => $image)
                        <img
                            src="{{ $image }}"
                            alt="{{ $project->title }} - {{ $index + 1 }}. fotoğraf"
                            data-project-slide
                            class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300 {{ $index === 0 ? 'opacity-100' : 'pointer-events-none opacity-0' }}"
                            @if($index > 0) loading="lazy" @endif
                        >
                    @endforeach

                    @if($videoUrl)
                        <video data-project-slide controls playsinline preload="metadata" class="pointer-events-none absolute inset-0 h-full w-full bg-slate-950 object-contain opacity-0">
                            <source src="{{ $videoUrl }}">
                            Tarayıcınız video oynatmayı desteklemiyor.
                        </video>
                    @elseif($youtubeId)
                        <iframe
                            data-project-slide
                            data-video-src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?rel=0"
                            title="{{ $project->title }} videosu"
                            class="pointer-events-none absolute inset-0 h-full w-full bg-slate-950 opacity-0"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    @endif
                </div>

                @if($slideCount > 1)
                    <button type="button" data-project-previous class="absolute left-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/85 text-white shadow-lg transition hover:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-white" aria-label="Önceki fotoğraf">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button type="button" data-project-next class="absolute right-3 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-blue-950/85 text-white shadow-lg transition hover:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-white" aria-label="Sonraki fotoğraf">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <span data-project-counter class="absolute bottom-3 right-3 rounded-full bg-slate-950/80 px-3 py-1.5 text-xs font-bold text-white">1 / {{ $slideCount }}</span>
                @endif
            </section>

            @if($slideCount > 1)
                <div class="flex gap-2 overflow-x-auto border-b border-slate-200 bg-white p-3" aria-label="Fotoğraf seçimi">
                    @foreach($gallery as $index => $image)
                        <button type="button" data-project-thumbnail="{{ $index }}" class="shrink-0 overflow-hidden rounded-lg border-2 {{ $index === 0 ? 'border-orange-500' : 'border-transparent opacity-70' }} transition hover:opacity-100" aria-label="{{ $index + 1 }}. fotoğrafı göster" aria-current="{{ $index === 0 ? 'true' : 'false' }}">
                            <img src="{{ $image }}" alt="" class="h-16 w-24 object-cover sm:h-20 sm:w-28" loading="lazy">
                        </button>
                    @endforeach
                    @if($videoUrl || $youtubeId)
                        <button type="button" data-project-thumbnail="{{ $gallery->count() }}" class="relative flex h-16 w-24 shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-transparent bg-slate-950 text-white opacity-70 transition hover:opacity-100 sm:h-20 sm:w-28" aria-label="Proje videosunu göster" aria-current="false">
                            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-500 shadow-lg"><i class="fa-solid fa-play ml-0.5"></i></span>
                            <span class="absolute bottom-1.5 left-1/2 -translate-x-1/2 text-[10px] font-extrabold uppercase tracking-wide">Video</span>
                        </button>
                    @endif
                </div>
            @endif

            <div class="p-6 sm:p-9">
                <span class="text-xs font-extrabold uppercase tracking-wide text-orange-600">{{ $statusLabel }}</span>
                <h1 class="mt-2 text-3xl font-extrabold text-blue-950">{{ $project->title }}</h1>
                <p class="mt-2 font-semibold text-slate-600">{{ $project->developer_name }}</p>
                @if($project->location)<p class="mt-3 text-sm text-slate-500"><i class="fa-solid fa-location-dot mr-1 text-orange-500"></i>{{ $project->location }}</p>@endif
                @if($project->summary)<div class="mt-6 text-lg font-semibold leading-8 text-slate-700">{!! \App\Support\RichContentHtml::render($project->summary) !!}</div>@endif
                @if($project->content)<div class="article-content mt-6 text-slate-700">{!! \App\Support\RichContentHtml::render($project->content) !!}</div>@endif
                @include('partials.mobile-selected-details', ['item' => $project])
            </div>
        </div>
    </main>

    @if($slideCount > 1)
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const slider = document.querySelector('[data-project-slider]');
                    if (! slider) return;

                    const slides = [...slider.querySelectorAll('[data-project-slide]')];
                    const thumbnails = [...document.querySelectorAll('[data-project-thumbnail]')];
                    const counter = slider.querySelector('[data-project-counter]');
                    let currentIndex = 0;
                    let touchStartX = null;

                    const showSlide = (requestedIndex) => {
                        currentIndex = (requestedIndex + slides.length) % slides.length;

                        slides.forEach((slide, index) => {
                            const active = index === currentIndex;
                            slide.classList.toggle('opacity-100', active);
                            slide.classList.toggle('opacity-0', ! active);
                            slide.classList.toggle('pointer-events-none', ! active);

                            if (slide.tagName === 'VIDEO' && ! active) slide.pause();
                            if (slide.tagName === 'IFRAME') {
                                if (active && ! slide.src) slide.src = slide.dataset.videoSrc;
                                if (! active && slide.src) slide.removeAttribute('src');
                            }
                        });

                        thumbnails.forEach((thumbnail, index) => {
                            const active = index === currentIndex;
                            thumbnail.classList.toggle('border-orange-500', active);
                            thumbnail.classList.toggle('border-transparent', ! active);
                            thumbnail.classList.toggle('opacity-70', ! active);
                            thumbnail.setAttribute('aria-current', String(active));
                        });

                        counter.textContent = `${currentIndex + 1} / ${slides.length}`;
                    };

                    slider.querySelector('[data-project-previous]')?.addEventListener('click', () => showSlide(currentIndex - 1));
                    slider.querySelector('[data-project-next]')?.addEventListener('click', () => showSlide(currentIndex + 1));
                    thumbnails.forEach((thumbnail) => thumbnail.addEventListener('click', () => showSlide(Number(thumbnail.dataset.projectThumbnail))));

                    slider.addEventListener('keydown', (event) => {
                        if (event.key === 'ArrowLeft') showSlide(currentIndex - 1);
                        if (event.key === 'ArrowRight') showSlide(currentIndex + 1);
                    });
                    slider.addEventListener('touchstart', (event) => {
                        if (event.target.closest('video')) return;
                        touchStartX = event.changedTouches[0].clientX;
                    }, { passive: true });
                    slider.addEventListener('touchend', (event) => {
                        if (touchStartX === null) return;
                        const distance = event.changedTouches[0].clientX - touchStartX;
                        if (Math.abs(distance) > 45) showSlide(currentIndex + (distance < 0 ? 1 : -1));
                        touchStartX = null;
                    }, { passive: true });
                });
            </script>
        @endpush
    @endif
</x-layouts.modern>
