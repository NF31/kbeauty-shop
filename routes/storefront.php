<?php

use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SkinGuideController;
use Illuminate\Support\Facades\Route;

Route::get('produits', [CatalogController::class, 'index'])
    ->name('storefront.products.index');

Route::get('guide-de-choix', [SkinGuideController::class, 'index'])
    ->name('storefront.skin-guide');

Route::get('produits/{product:slug}', [ProductController::class, 'show'])
    ->name('storefront.products.show');
