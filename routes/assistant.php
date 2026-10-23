<?php

use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;

Route::post('/asistan/mesaj', AssistantController::class)->middleware('throttle:20,1')->name('assistant.message');
Route::delete('/asistan/sohbet', [AssistantController::class, 'destroy'])->middleware('throttle:10,1')->name('assistant.clear');

Route::post('/asistan/geri-bildirim', [AssistantController::class, 'feedback'])->middleware('throttle:10,1')->name('assistant.feedback');
