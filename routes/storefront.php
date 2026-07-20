<?php

use App\Http\Controllers\Storefront\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('produits/{product:slug}', [ProductController::class, 'show'])
    ->name('storefront.products.show');
