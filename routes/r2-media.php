<?php

use App\Http\Controllers\R2MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/media/r2/{path}', R2MediaController::class)
    ->where('path', '.*')->middleware('signed:relative')->name('r2.media');
