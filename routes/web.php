<?php

use App\Livewire\BlogList;
use App\Livewire\PropertySearch;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/haberler', BlogList::class)->name('blogs.index');

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
