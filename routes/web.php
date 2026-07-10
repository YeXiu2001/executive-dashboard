<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/general-fund', function () {
    return view('pages.general-fund.index');
})->middleware(['auth', 'verified'])->name('general-fund.index');

Route::get('/data-entry/general-fund', function () {
    return view('pages.data-entry.general-fund.index');
})->middleware(['auth', 'verified'])->name('data-entry.general-fund');

Route::get('/analytics/general-fund', function () {
    return view('pages.analytics.general-fund.index');
})->middleware(['auth', 'verified'])->name('analytics.general-fund');

Route::get('/analytics/gen-fund-comparison', function () {
    return view('pages.analytics.gen-fund-comparison.index');
})->middleware(['auth', 'verified'])->name('analytics.gen-fund-comparison');