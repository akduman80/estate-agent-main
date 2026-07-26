<div class="blog-page section">
    <div class="container">
        <div class="row">
            <div class="col-md-9 single-post-posts">
                <div id="posts" class="row">
                    @forelse($posts as $post)
                        <div class="col-md-6 col-sm-6">
                            <div class="single-story-itme">
                                <a href="#">
                                    <img src="{{ $post->cover_image ? asset('storage/'.$post->cover_image) : asset('fe-assets/assets/img/blog1.jpg') }}" alt="{{ $post->title }}" class="img-responsive">
                                </a>
                                <h4><a href="#">{{ $post->title }}</a></h4>
                                <p class="author-category">
                                    Editör: <a href="#">{{ $post->author?->name ?? 'Editör' }}</a> |
                                    Tarih: {{ $post->published_at?->format('d.m.Y') }} |
                                    Kategori: <span class="label label-info">{{ $post->category?->name }}</span>
                                </p>
                                <p class="intro">{{ Str::limit(strip_tags($post->content), 120) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="alert alert-info">Henüz yayınlanmış bir haber bulunmuyor.</p>
                    @endforelse
                </div>
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
