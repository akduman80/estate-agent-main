<?php

use App\Http\Controllers\EidsCallbackController;
use App\Livewire\BlogList;
use App\Models\Listing;
use App\Models\Post;
use App\Models\Setting;
use App\Services\TurkeyAddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::get('/haberler', BlogList::class)->name('blogs.index');

Route::get('/haberler/{post:slug}', function (Post $post) {
    abort_unless($post->status === 'published', 404);

    $post->load(['author', 'category']);

    $previousPost = Post::query()
        ->where('status', 'published')
        ->where('published_at', '<', $post->published_at)
        ->latest('published_at')
        ->first();

    $nextPost = Post::query()
        ->where('status', 'published')
        ->where('published_at', '>', $post->published_at)
        ->oldest('published_at')
        ->first();

    return view('blogs.show', compact('post', 'previousPost', 'nextPost'));
})->name('blogs.show');

Route::get('/iletisim', function () {
    return view('contact');
})->name('contact');

Route::get('/eids/start', [EidsCallbackController::class, 'start'])->name('eids.start');
Route::get('/eids/callback', [EidsCallbackController::class, 'callback'])->name('eids.callback');

Route::get('/dokumanlar/{index}/indir', function (int $index) {
    $documents = collect(Setting::get('user_documents', []))
        ->filter(fn ($document) => is_array($document) && ($document['is_active'] ?? true) && filled($document['title'] ?? null) && filled($document['file'] ?? null))
        ->sortBy(fn (array $document) => (int) ($document['sort'] ?? 0))
        ->values();

    $document = $documents->get($index);

    abort_unless(
        is_array($document)
        && ($document['is_active'] ?? true)
        && filled($document['file'] ?? null),
        404
    );

    $path = (string) $document['file'];

    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->download($path);
})->whereNumber('index')->name('documents.download');

Route::get('/ilanlar', function (Request $request) {
    $listings = Listing::query()
        ->with(['images', 'eidsVerification'])
        ->where('is_active', true)
        ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%' . (string) $request->string('q') . '%'))
        ->when($request->filled('city'), fn ($query) => $query->where('city_id', (string) $request->string('city')))
        ->when($request->filled('type'), fn ($query) => $query->where('type', (string) $request->string('type')))
        ->when($request->filled('min_price'), fn ($query) => $query->where('price', '>=', $request->input('min_price')))
        ->when($request->filled('max_price'), fn ($query) => $query->where('price', '<=', $request->input('max_price')))
        ->when($request->filled('min_area'), fn ($query) => $query->where('square_meters', '>=', $request->input('min_area')))
        ->when($request->filled('max_area'), fn ($query) => $query->where('square_meters', '<=', $request->input('max_area')))
        ->when(
            $request->input('sort') === 'price',
            fn ($query) => $query->orderByDesc('price'),
            fn ($query) => $query->latest()
        )
        ->paginate(12)
        ->withQueryString();

    $recommendedListings = Listing::query()
        ->with(['images', 'eidsVerification'])
        ->where('is_active', true)
        ->orderByDesc('is_featured')
        ->latest()
        ->take(4)
        ->get();

    return view('listings.index', [
        'listings' => $listings,
        'recommendedListings' => $recommendedListings,
        'cities' => TurkeyAddressService::getCities(),
        'typeOptions' => Setting::get('options_type', ['Satılık', 'Kiralık']),
    ]);
})->name('listings.index');

Route::get('/ilan/{listing:slug}', function (Listing $listing) {
    abort_unless($listing->is_active, 404);

    $listing->load(['images', 'eidsVerification']);

    return view('listings.show', compact('listing'));
})->name('listings.show');

// Genel Tek Login Route'u
Route::get('/login', fn() => redirect('/admin/login'))->name('login');
Route::post('/github-deploy-webhook', function (Request $request) {
    // Secret Kontrolü (İstersen devre dışı bırakabilirsin)
    $githubSecret = 'ziyadeemlak_deploy_2026';
    $signature = $request->header('X-Hub-Signature-256');

    if ($signature) {
        $hash = 'sha256=' . hash_hmac('sha256', $request->getContent(), $githubSecret);
        if (!hash_equals($hash, $signature)) {
            Log::warning('Webhook unauthorized signature match failure.');
            return response()->json(['error' => 'Unauthorized Signature'], 401);
        }
    }

    // Git Pull ve Deployment Komutları
    $result = Process::path('/var/www/estate-agent')
                     ->run('git pull origin main 2>&1');

    Log::info('Git Pull Result: ' . $result->output());

    if ($result->successful()) {
        // Cache ve Migrate İşlemleri
        Process::path('/var/www/estate-agent')
               ->run('php artisan migrate --force && php artisan config:cache && php artisan view:cache');

        return response()->json([
            'status'  => 'success',
            'output'  => $result->output()
        ]);
    }

    return response()->json([
        'status' => 'error',
        'output' => $result->output()
    ], 500);
});
