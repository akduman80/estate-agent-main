<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\EidsCallbackController;
use App\Http\Controllers\GitHubDeployController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PollVoteController;
use App\Livewire\BlogList;
use App\Models\LegalDocument;
use App\Models\Listing;
use App\Models\Poll;
use App\Models\Post;
use App\Models\Setting;
use App\Services\TurkeyAddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/', function () {
    $recentListings = Listing::query()
        ->with(['images', 'eidsVerification'])
        ->published()
        ->orderByDesc('is_featured')
        ->latest()
        ->take(6)
        ->get();

    $latestPosts = Post::query()
        ->with('category')
        ->where('status', 'published')
        ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
        ->latest('published_at')
        ->take(3)
        ->get();

    $activePoll = Poll::query()
        ->with('options')
        ->currentlyActive()
        ->latest('starts_at')
        ->first();

    return view('welcome', compact('recentListings', 'latestPosts', 'activePoll'));
})->name('home');

Route::post('/anketler/{poll}/oy', PollVoteController::class)
    ->middleware('throttle:10,1')
    ->name('polls.vote');

Route::get('/haberler', BlogList::class)->name('blogs.index');

Route::get('/haberler/{post:slug}', function (Post $post) {
    abort_unless(
        $post->status === 'published'
        && (! $post->published_at || $post->published_at->isPast()),
        404
    );

    $post->load(['author', 'category']);

    $latestPosts = Post::query()
        ->with('category')
        ->where('status', 'published')
        ->whereKeyNot($post->getKey())
        ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
        ->latest('published_at')
        ->take(3)
        ->get();

    $relatedPosts = Post::query()
        ->with('category')
        ->where('status', 'published')
        ->whereKeyNot($post->getKey())
        ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
        ->when($post->category_id, fn ($query) => $query->where('category_id', $post->category_id))
        ->latest('published_at')
        ->take(3)
        ->get();

    if ($relatedPosts->count() < 3) {
        $fallbackPosts = Post::query()
            ->with('category')
            ->where('status', 'published')
            ->whereKeyNot($post->getKey())
            ->whereNotIn('id', $relatedPosts->pluck('id'))
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')
            ->take(3 - $relatedPosts->count())
            ->get();

        $relatedPosts = $relatedPosts->concat($fallbackPosts);
    }

    $previousPost = Post::query()
        ->where('status', 'published')
        ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
        ->where('published_at', '<', $post->published_at)
        ->latest('published_at')
        ->first();

    $nextPost = Post::query()
        ->where('status', 'published')
        ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
        ->where('published_at', '>', $post->published_at)
        ->oldest('published_at')
        ->first();

    return view('blogs.show', compact('post', 'previousPost', 'nextPost', 'latestPosts', 'relatedPosts'));
})->name('blogs.show');

Route::get('/iletisim', function () {
    return view('contact');
})->name('contact');

Route::get('/konum/ilceler', [LocationController::class, 'districts'])
    ->name('locations.districts');
Route::get('/konum/mahalleler', [LocationController::class, 'neighborhoods'])
    ->name('locations.neighborhoods');

Route::get('/eids/start', [EidsCallbackController::class, 'start'])->name('eids.start');
Route::get('/eids/callback', [EidsCallbackController::class, 'callback'])->name('eids.callback');
Route::get('/hesabim/eids/baslat', [EidsCallbackController::class, 'startForAccount'])->middleware('auth')->name('account.eids.start');

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

    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $downloadName = Str::slug((string) $document['title'])
        .($extension !== '' ? '.'.strtolower($extension) : '');

    return Storage::disk('public')->download($path, $downloadName);
})->whereNumber('index')->name('documents.download');

Route::get('/ilanlar', function (Request $request) {
    $listings = Listing::query()
        ->with(['images', 'eidsVerification'])
        ->published()
        ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.(string) $request->string('q').'%'))
        ->when($request->filled('city'), fn ($query) => $query->where('city_id', (string) $request->string('city')))
        ->when($request->filled('district'), fn ($query) => $query->where('district_id', (string) $request->string('district')))
        ->when($request->filled('neighborhood'), fn ($query) => $query->where('neighborhood_id', (string) $request->string('neighborhood')))
        ->when($request->filled('type'), fn ($query) => $query->where('type', (string) $request->string('type')))
        ->when($request->filled('category'), fn ($query) => $query->where('category', (string) $request->string('category')))
        ->when($request->filled('min_price'), fn ($query) => $query->where('price', '>=', $request->input('min_price')))
        ->when($request->filled('max_price'), fn ($query) => $query->where('price', '<=', $request->input('max_price')))
        ->when($request->filled('min_area'), fn ($query) => $query->where('square_meters', '>=', $request->input('min_area')))
        ->when($request->filled('max_area'), fn ($query) => $query->where('square_meters', '<=', $request->input('max_area')))
        ->when($request->input('sort') === 'price_asc', fn ($query) => $query->orderBy('price'))
        ->when(in_array($request->input('sort'), ['price', 'price_desc'], true), fn ($query) => $query->orderByDesc('price'))
        ->when($request->input('sort') === 'area_desc', fn ($query) => $query->orderByDesc('square_meters'))
        ->when(! in_array($request->input('sort'), ['price', 'price_desc', 'price_asc', 'area_desc'], true), fn ($query) => $query->latest())
        ->paginate(12)
        ->withQueryString();

    $recommendedListings = Listing::query()
        ->with(['images', 'eidsVerification'])
        ->published()
        ->orderByDesc('is_featured')
        ->latest()
        ->take(4)
        ->get();

    return view('listings.index', [
        'listings' => $listings,
        'recommendedListings' => $recommendedListings,
        'cities' => TurkeyAddressService::getCities(),
        'typeOptions' => Setting::get('options_type', ['Satılık', 'Kiralık']),
        'categoryOptions' => Setting::get('options_category', ['Daire', 'Müstakil Ev', 'Arsa', 'Tarla', 'İşyeri']),
    ]);
})->name('listings.index');

Route::get('/ilan/{listing:slug}', function (Listing $listing) {
    abort_unless(Listing::query()->published()->whereKey($listing->getKey())->exists(), 404);

    $listing->load(['images', 'eidsVerification', 'businessProfile', 'user.businessProfile']);

    $similarListings = Listing::query()
        ->with(['images', 'eidsVerification'])
        ->published()
        ->where('id', '!=', $listing->getKey())
        ->when($listing->category, fn ($query) => $query->where('category', $listing->category))
        ->orderByDesc('is_featured')
        ->latest()
        ->take(3)
        ->get();

    if ($similarListings->count() < 3) {
        $additionalListings = Listing::query()
            ->with(['images', 'eidsVerification'])
            ->published()
            ->where('id', '!=', $listing->getKey())
            ->whereNotIn('id', $similarListings->modelKeys())
            ->orderByDesc('is_featured')
            ->latest()
            ->take(3 - $similarListings->count())
            ->get();

        $similarListings = $similarListings->concat($additionalListings);
    }

    return view('listings.show', compact('listing', 'similarListings'));
})->name('listings.show');

Route::get('/login', fn () => redirect('/portal/login'))->name('login');
Route::get('/kayit', [RegistrationController::class, 'create'])->middleware('guest')->name('register');
Route::post('/kayit', [RegistrationController::class, 'store'])->middleware('guest')->name('register.store');

Route::get('/e-posta-dogrula', [EmailVerificationController::class, 'show'])->middleware('guest')->name('verification.notice');
Route::post('/e-posta-dogrula', [EmailVerificationController::class, 'verify'])->middleware('guest')->name('verification.verify');
Route::post('/e-posta-dogrula/yeniden-gonder', [EmailVerificationController::class, 'resend'])->middleware('guest')->name('verification.resend');

Route::get('/iki-asamali-dogrulama', [TwoFactorController::class, 'show'])->middleware('guest')->name('two-factor.show');
Route::post('/iki-asamali-dogrulama', [TwoFactorController::class, 'verify'])->middleware('guest')->name('two-factor.verify');
Route::post('/iki-asamali-dogrulama/yeniden-gonder', [TwoFactorController::class, 'resend'])->middleware('guest')->name('two-factor.resend');

Route::get('/sifremi-unuttum', [PasswordResetController::class, 'requestForm'])->middleware('guest')->name('password.request');
Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendLink'])->middleware('guest')->name('password.email');
Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'resetForm'])->middleware('guest')->name('password.reset');
Route::post('/sifre-sifirla', [PasswordResetController::class, 'reset'])->middleware('guest')->name('password.update');

Route::middleware('auth')->group(function (): void {
    Route::get('/telefon-dogrula', [PhoneVerificationController::class, 'show'])->name('phone.verification.notice');
    Route::post('/telefon-dogrula/gonder', [PhoneVerificationController::class, 'send'])->name('phone.verification.send');
    Route::post('/telefon-dogrula', [PhoneVerificationController::class, 'verify'])->name('phone.verification.verify');
    Route::get('/hesap/guvenlik', [AccountSecurityController::class, 'show'])->name('account.security');
    Route::post('/hesap/iki-asamali-dogrulama', [AccountSecurityController::class, 'updateTwoFactor'])->name('account.two-factor.update');
    Route::post('/hesap/e-posta-degistir', [AccountSecurityController::class, 'requestEmailChange'])->name('account.email.request');
    Route::post('/hesap/e-posta-degistir/onayla', [AccountSecurityController::class, 'confirmEmailChange'])->name('account.email.confirm');
    Route::post('/hesap/sifre-degistir', [AccountSecurityController::class, 'requestPasswordChange'])->name('account.password.request');
    Route::post('/hesap/sifre-degistir/onayla', [AccountSecurityController::class, 'confirmPasswordChange'])->name('account.password.confirm');
});

Route::get('/sozlesmeler/{document:slug}', function (LegalDocument $document) {
    abort_unless($document->is_active, 404);

    return view('legal.show', compact('document'));
})->name('legal.show');
Route::post('/github-deploy-webhook', GitHubDeployController::class);
