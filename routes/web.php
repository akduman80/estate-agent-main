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
    // Güvenlik için secret key kontrolü (Opsiyonel ama önerilir)
    $githubSecret = config('app.deploy_secret', 'ziyadeemlak_deploy_2026');
    $signature = $request->header('X-Hub-Signature-256');

    if ($signature) {
        $hash = 'sha256=' . hash_hmac('sha256', $request->getContent(), $githubSecret);
        if (!hash_equals($hash, $signature)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    // Sunucuda bash betiğini veya git pull komutunu çalıştır
    Process::path('/var/www/estate-agent')
           ->run('git pull origin main && php artisan migrate --force && php artisan config:cache');

    return response()->json(['status' => 'success', 'message' => 'Pulled successfully']);
});
