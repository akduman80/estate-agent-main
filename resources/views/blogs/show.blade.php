<x-layouts.app>
    @section('title', $post->title . ' | Haberler')

    @php
        $imageUrl = $post->cover_image ? asset('storage/'.$post->cover_image) : asset('fe-assets/assets/img/blog2.jpg');
    @endphp

    <div class="page-head">
        <div class="container">
            <div class="row">
                <div class="page-head-content">
                    <h1 class="page-title">Haber Detayı</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content-area blog-page padding-top-40" style="background-color: #FCFCFC; padding-bottom: 55px;">
        <div class="container">
            <div class="row">
                <div class="blog-lst col-md-12 pl0">
                    <section id="post-{{ $post->id }}" class="post single">
                        <div class="post-header single">
                            <div>
                                <h2 class="wow fadeInLeft animated">{{ $post->title }}</h2>
                                <div class="title-line wow fadeInRight animated"></div>
                            </div>

                            <div class="row wow fadeInRight animated">
                                <div class="col-sm-6">
                                    <p class="author-category">
                                        Editör: <a href="{{ route('blogs.index') }}">{{ $post->author?->name ?? 'Editör' }}</a>
                                        @if($post->category)
                                            | Kategori: <a href="{{ route('blogs.index') }}">{{ $post->category->name }}</a>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-sm-6 right">
                                    <p class="date-comments">
                                        <a href="{{ route('blogs.show', $post->slug) }}">
                                            <i class="fa fa-calendar-o"></i>
                                            {{ $post->published_at?->format('d.m.Y') ?? $post->created_at->format('d.m.Y') }}
                                        </a>
                                    </p>
                                </div>
                            </div>

                            <div class="image wow fadeInRight animated">
                                <img src="{{ $imageUrl }}" class="img-responsive" alt="{{ $post->title }}">
                            </div>
                        </div>

                        <div id="post-content" class="post-body single wow fadeInLeft animated">
                            {!! $post->content !!}
                        </div>

                        <div class="post-footer single wow fadeInBottum animated">
                            <ul class="pager">
                                <li class="previous {{ $previousPost ? '' : 'disabled' }}">
                                    @if($previousPost)
                                        <a href="{{ route('blogs.show', $previousPost->slug) }}"><i></i>&larr; Önceki</a>
                                    @else
                                        <a href="#"><i></i>&larr; Önceki</a>
                                    @endif
                                </li>
                                <li class="next {{ $nextPost ? '' : 'disabled' }}">
                                    @if($nextPost)
                                        <a href="{{ route('blogs.show', $nextPost->slug) }}">Sonraki &rarr;<i></i></a>
                                    @else
                                        <a href="#">Sonraki &rarr;<i></i></a>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
