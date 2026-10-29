@props(['post', 'loadAssets' => false])
@php
    $media = $post->news_media ?? [];
    $videoUrl = $post->news_video_url;
    $youtubeId = \App\Services\NewsVideo::youtubeId($media['youtube_url'] ?? null);
    $qualities = $media['qualities'] ?? [];
    $disk = $media['disk'] ?? config('news_video.disk');
    $captionLabel = config('news_video.caption_languages')[$media['captions_language'] ?? 'tr'] ?? 'Türkçe';
@endphp
@if($videoUrl)
    <div class="zyd-news-video" data-news-video data-manifest="{{ $videoUrl }}" data-variants="{{ json_encode(array_map(fn ($q) => ['quality' => $q, 'url' => preg_replace('~master\.m3u8$~', $q.'.m3u8', $videoUrl)], $qualities)) }}">
        <video controls playsinline crossorigin="anonymous" preload="none" poster="{{ $post->news_poster_url }}" aria-label="{{ $media['title'] ?? $post->title }}">
            @if(!empty($media['captions']))
                <track kind="subtitles" src="{{ \Illuminate\Support\Facades\Storage::disk($disk)->url($media['captions']) }}" srclang="{{ $media['captions_language'] ?? 'tr' }}" label="{{ config('news_video.caption_languages')[$media['captions_language'] ?? 'tr'] ?? 'Türkçe' }}">
            @endif
        </video>
        <div class="zyd-news-video-controls" role="group" aria-label="Video ayarları">
            <button type="button" data-start>▶ Videoyu Oynat</button>
            <button type="button" data-seek="-10" aria-label="10 saniye geri sar">−10 sn</button>
            <button type="button" data-seek="10" aria-label="10 saniye ileri sar">+10 sn</button>
            <label>⚙ Kalite <select data-quality aria-label="Video kalitesi"><option value="auto">Otomatik</option></select></label>
            <label>Hız <select data-speed aria-label="Oynatma hızı">@foreach([0.5,0.75,1,1.25,1.5,1.75,2] as $speed)<option value="{{ $speed }}" @selected($speed === 1)>{{ $speed }}x</option>@endforeach</select></label>
            <button type="button" data-captions @disabled(empty($media['captions']))>{{ $captionLabel }} Altyazı</button>
        </div>
        <p data-video-message role="status" aria-live="polite"></p>
    </div>
@endif
@if($youtubeId)
    <div class="zyd-youtube" data-youtube="{{ $youtubeId }}">
        <button type="button" data-youtube-play>YouTube Videosunu Oynat</button>
        <p>YouTube oynatıldığında YouTube hizmetine bağlanılır. Kaliteyi YouTube yönetir.</p>
    </div>
@endif
@if($videoUrl || $youtubeId || $loadAssets)
    @once
        <link rel="stylesheet" href="{{ asset('css/news-video.css') }}">
        <script src="{{ asset('js/news-video.js') }}" defer></script>
    @endonce
@endif
