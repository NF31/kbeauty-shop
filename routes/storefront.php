<?php

use App\Http\Controllers\Storefront\CartController;
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

Route::get('panier', [CartController::class, 'index'])
    ->name('storefront.cart.index');

Route::post('panier', [CartController::class, 'store'])
    ->name('storefront.cart.store');

Route::patch('panier/{cartItem}', [CartController::class, 'update'])
    ->name('storefront.cart.update');

Route::delete('panier/{cartItem}', [CartController::class, 'destroy'])
    ->name('storefront.cart.destroy');
