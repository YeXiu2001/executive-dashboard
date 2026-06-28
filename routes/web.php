<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/general-fund', function () {
    return view('pages.general-fund.index');
})->middleware(['auth', 'verified'])->name('general-fund.index');
