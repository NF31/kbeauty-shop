<?php

use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->middleware('locale:fr')->name('home');

Route::get('sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [AccountController::class, 'dashboard'])
        ->middleware('locale:fr')
        ->name('dashboard');
});

Route::prefix('en')->name('en.')->middleware('locale:en')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', [AccountController::class, 'dashboard'])->name('dashboard');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/storefront.php';
require __DIR__.'/webhooks.php';
