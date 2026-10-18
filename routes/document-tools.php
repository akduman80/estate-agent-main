<?php

use App\Http\Controllers\NativeLeaseDocumentController;
use Illuminate\Support\Facades\Route;

Route::post('/dokumanlar/islemler/kira', NativeLeaseDocumentController::class)
    ->middleware('throttle:30,1')->name('documents.native-lease');

Route::post('/dokumanlar/islemler/sablon/{id}/{version}', \App\Http\Controllers\NativeTemplateDocumentController::class)
    ->where('id', '[a-f0-9]{32}')->whereNumber('version')
    ->middleware('throttle:20,1')->name('documents.native-template');
