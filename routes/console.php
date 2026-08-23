<?php

use App\Models\Listing;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Listing::query()
        ->where('is_active', true)
        ->whereHas('eidsVerification', fn ($query) => $query->whereNotNull('expires_at')->where('expires_at', '<=', now()))
        ->eachById(fn (Listing $listing) => $listing->update(['is_active' => false, 'publication_status' => 'expired']));
})->hourly()->name('expire-eids-listings')->withoutOverlapping();
