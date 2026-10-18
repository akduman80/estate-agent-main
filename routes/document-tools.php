<?php

use App\Http\Controllers\NativeLeaseDocumentController;
use Illuminate\Support\Facades\Route;

Route::post('/dokumanlar/islemler/kira', NativeLeaseDocumentController::class)
    ->middleware('throttle:30,1')->name('documents.native-lease');
