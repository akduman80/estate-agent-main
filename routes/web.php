<?php

use App\Http\Controllers\AccountSecurityController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginRedirectController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DisplayPreferenceController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\EidsCallbackController;
use App\Http\Controllers\GitHubDeployController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalDocumentController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MobileProjectPublishController;
use App\Http\Controllers\PollVoteController;
use App\Http\Controllers\ProjectController;
use App\Livewire\BlogList;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/anketler/aktif', [PollVoteController::class, 'active'])->name('polls.active');

Route::post('/anketler/{poll}/oy', PollVoteController::class)
    ->middleware('throttle:10,1')
    ->name('polls.vote');

Route::get('/haberler', BlogList::class)->name('blogs.index');
Route::get('/haberler/{post:slug}', [BlogController::class, 'show'])->name('blogs.show');

Route::get('/projeler', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/harita', fn () => view('shared-map', ['mapRecords' => \App\Services\SharedMapRecords::records()]))->name('shared-map');
Route::get('/projeler/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');

Route::get('/iletisim', ContactController::class)->name('contact');

Route::get('/konum/ilceler', [LocationController::class, 'districts'])
    ->name('locations.districts');
Route::get('/konum/mahalleler', [LocationController::class, 'neighborhoods'])
    ->name('locations.neighborhoods');

Route::get('/eids/start', [EidsCallbackController::class, 'start'])->middleware('auth')->name('eids.start');
Route::get('/eids/callback', [EidsCallbackController::class, 'callback'])->name('eids.callback');
Route::get('/hesabim/eids/baslat', [EidsCallbackController::class, 'startForAccount'])->middleware('auth')->name('account.eids.start');

Route::get('/dokumanlar/{index}/indir', DocumentDownloadController::class)
    ->whereNumber('index')
    ->name('documents.download');

Route::get('/ilanlar', [ListingController::class, 'index'])->name('listings.index');
Route::get('/ilan/{listing:slug}/paylasim-gorseli/{version}.jpg', \App\Http\Controllers\ListingSocialImageController::class)
    ->where('version', '[a-f0-9]{64}')->name('listings.social-image');
Route::get('/ilan/{listing:slug}', [ListingController::class, 'show'])->name('listings.show');

Route::get('/login', LoginRedirectController::class)->name('login');
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
Route::get('/sifre-sifirla/{token?}', [PasswordResetController::class, 'resetForm'])->middleware('guest')->name('password.reset');
Route::post('/sifre-sifirla', [PasswordResetController::class, 'reset'])->middleware('guest')->name('password.update');

Route::get('/telefon-dogrula', [PhoneVerificationController::class, 'show'])->middleware('auth')->name('phone.verification.notice');
Route::post('/telefon-dogrula/gonder', [PhoneVerificationController::class, 'send'])->middleware('auth')->name('phone.verification.send');
Route::post('/telefon-dogrula', [PhoneVerificationController::class, 'verify'])->middleware('auth')->name('phone.verification.verify');
Route::get('/hesap/guvenlik', [AccountSecurityController::class, 'show'])->middleware('auth')->name('account.security');
Route::patch('/hesap/gorunum-tercihi', DisplayPreferenceController::class)->middleware('auth')->name('account.display-preference');
Route::post('/hesap/iki-asamali-dogrulama', [AccountSecurityController::class, 'updateTwoFactor'])->middleware('auth')->name('account.two-factor.update');
Route::post('/hesap/e-posta-degistir', [AccountSecurityController::class, 'requestEmailChange'])->middleware('auth')->name('account.email.request');
Route::post('/hesap/e-posta-degistir/onayla', [AccountSecurityController::class, 'confirmEmailChange'])->middleware('auth')->name('account.email.confirm');
Route::post('/hesap/sifre-degistir', [AccountSecurityController::class, 'requestPasswordChange'])->middleware('auth')->name('account.password.request');
Route::post('/hesap/sifre-degistir/onayla', [AccountSecurityController::class, 'confirmPasswordChange'])->middleware('auth')->name('account.password.confirm');

Route::get('/sozlesmeler/{document:slug}', [LegalDocumentController::class, 'show'])->name('legal.show');

Route::post('/github-deploy-webhook', GitHubDeployController::class);

Route::post('/api/v1/projects/publish', fn () => response()->json([
    'message' => 'Eski doğrudan yayın bağlantısı kapatıldı. Yeni uygulamada Siteye taslak gönder bağlantısını kullanın.',
], 410))
    ->middleware('throttle:30,1')
    ->name('api.projects.publish');

Route::middleware([\App\Http\Middleware\MobileDraftAuthentication::class, 'throttle:30,1'])
    ->group(function (): void {
        Route::post('/api/v1/mobile-drafts', [\App\Http\Controllers\MobileDraftController::class, 'store']);
        Route::get('/api/v1/mobile-drafts', [\App\Http\Controllers\MobileDraftController::class, 'index']);
    });

Route::get('/mobile-draft-media/{import}/{index}', function (\App\Models\MobileDraftImport $import, int $index) {
    abort_unless(auth()->id() === $import->user_id || auth()->user()?->role === \App\Enums\UserRole::SUPERVISOR, 403);
    $file = $import->media[$index] ?? null;
    abort_unless($file && \Illuminate\Support\Facades\Storage::disk($file['disk'])->exists($file['path']), 404);
    return \Illuminate\Support\Facades\Storage::disk($file['disk'])->download($file['path']);
})->middleware('auth')->name('mobile-draft-media');

require __DIR__.'/document-tools.php';
require __DIR__.'/assistant.php';
require __DIR__.'/property-tools.php';

Route::get('/listing-photo/{image}', \App\Http\Controllers\ListingPhotoController::class)->whereNumber('image')->name('listing.photo');
Route::get('/storage/listings/{path}', \App\Http\Controllers\ListingPhotoSourceController::class)
    ->where('path', '.*\.(?i:jpe?g|png|webp)')->name('listing.photo.source');
