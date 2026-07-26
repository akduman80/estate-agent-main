<?php

use App\Livewire\BlogList;
use App\Livewire\PropertySearch;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Haber & Blog Sayfası
Route::get('/haberler', BlogList::class)->name('blogs.index');

// Genel Tek Login Route'u
Route::get('/login', fn() => redirect('/admin/login'))->name('login');
