<div>
    <div class="page-head">
        <div class="container">
            <div class="row">
                <div class="page-head-content">
                    <h1 class="page-title">Haberler</h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content-area blog-page padding-top-40" style="background-color: #FCFCFC; padding-bottom: 55px;">
        <div class="container">
            <div class="row">
                <div class="blog-lst col-md-12 pl0">
                    <div id="posts">
                        @forelse($posts as $post)
                            @php
                                $postUrl = route('blogs.show', $post->slug);
                                $imageUrl = $post->cover_image ? asset('storage/'.$post->cover_image) : asset('fe-assets/assets/img/blog2.jpg');
                            @endphp

                            <section class="post">
                                <div class="text-center padding-b-50">
                                    <h2 class="wow fadeInLeft animated">
                                        <a href="{{ $postUrl }}">{{ $post->title }}</a>
                                    </h2>
                                    <div class="title-line wow fadeInRight animated"></div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-6">
                                        <p class="author-category">
                                            Editör: <a href="{{ $postUrl }}">{{ $post->author?->name ?? 'Editör' }}</a>
                                            @if($post->category)
                                                | Kategori: <a href="{{ route('blogs.index') }}">{{ $post->category->name }}</a>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-sm-6 right">
                                        <p class="date-comments">
                                            <a href="{{ $postUrl }}">
                                                <i class="fa fa-calendar-o"></i>
                                                {{ $post->published_at?->format('d.m.Y') ?? $post->created_at->format('d.m.Y') }}
                                            </a>
                                        </p>
                                    </div>
                                </div>

                                <div class="image wow fadeInLeft animated">
                                    <a href="{{ $postUrl }}">
                                        <img src="{{ $imageUrl }}" class="img-responsive" alt="{{ $post->title }}">
                                    </a>
                                </div>

                                <p class="intro">{{ $post->summary ?: Str::limit(strip_tags($post->content), 220) }}</p>
                                <p class="read-more">
                                    <a href="{{ $postUrl }}" class="btn btn-default btn-border">Devamını oku</a>
                                </p>
                            </section>
                        @empty
                            <p class="alert alert-info">Henüz yayınlanmış bir haber bulunmuyor.</p>
                        @endforelse
                    </div>

                    {{ $posts->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
