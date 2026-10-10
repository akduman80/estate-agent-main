<?php

use App\Models\Listing;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('mobile-drafts:key {email} {--name=Telefon} {--revoke}', function (): void {
    $user = \App\Models\User::where('email', $this->argument('email'))->firstOrFail();
    $name = (string) $this->option('name');
    \Illuminate\Support\Facades\DB::table('mobile_draft_tokens')->where('user_id', $user->id)->where('name', $name)->delete();
    if ($this->option('revoke')) {
        $this->info('Cihazın taslak aktarım anahtarı iptal edildi.');
        return;
    }
    $token = bin2hex(random_bytes(32));
    \Illuminate\Support\Facades\DB::table('mobile_draft_tokens')->insert([
        'user_id' => $user->id, 'name' => $name, 'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->info('Yalnızca bu hesabın taslak aktarımı için anahtar (bir kez gösterilir):');
    $this->line($token);
})->purpose('Cihaza özel taslak aktarım anahtarı oluşturur veya iptal eder; yayın/EİDS yetkisi vermez.');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Listing::query()
        ->where('is_active', true)
        ->whereHas('eidsVerification', fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()))
        ->eachById(fn (Listing $listing) => $listing->update(['is_active' => false, 'publication_status' => 'expired']));
})->hourly()->name('expire-eids-listings')->withoutOverlapping();
