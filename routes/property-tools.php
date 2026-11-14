<?php

use App\Http\Controllers\PropertyMapController;
use App\Http\Controllers\PropertyNotificationsController;
use App\Http\Controllers\ProjectParcelLookupController;
use App\Http\Controllers\VoiceSearchController;
use App\Livewire\RentCalculator;
use Illuminate\Support\Facades\Route;

Route::post('/ilan-arama/metin', VoiceSearchController::class)->middleware('throttle:30,1')->name('search.voice');
Route::get('/araclar/parsel', fn () => view('tools.parcel'))->name('tools.parcel');
Route::get('/araclar/kira-artisi', RentCalculator::class)->name('tools.rent');
Route::get('/ilan-haritasi', [PropertyMapController::class, 'index'])->name('tools.map');
Route::get('/api/projeler/parsel-sorgula', ProjectParcelLookupController::class)->middleware('throttle:20,1')->name('projects.parcel.lookup');
Route::get('/ilan-haritasi/kayitlar', [PropertyMapController::class, 'records'])->middleware('throttle:60,1')->name('tools.map.records');
Route::middleware(['auth', 'throttle:30,1'])->group(function () {
    Route::get('/hesap/etkilesim-bildirimleri', [PropertyNotificationsController::class, 'index'])->name('property.notifications');
    Route::patch('/hesap/etkilesim-bildirimleri', [PropertyNotificationsController::class, 'update'])->name('property.notifications.update');
    Route::post('/hesap/etkilesim-bildirimleri/{id}/okundu', [PropertyNotificationsController::class, 'read'])->whereNumber('id')->name('property.notifications.read');
});
