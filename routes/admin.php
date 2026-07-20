<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductVariantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|staff|support'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('permission:products.manage')->group(function () {
            Route::resource('categories', CategoryController::class)->except('show');
            Route::resource('products', ProductController::class)->except('show');

            Route::post('products/{product}/options', [ProductOptionController::class, 'store'])
                ->name('products.options.store');
            Route::delete('products/{product}/options/{option}', [ProductOptionController::class, 'destroy'])
                ->name('products.options.destroy');

            Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])
                ->name('products.variants.store');
            Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])
                ->name('products.variants.update');
            Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])
                ->name('products.variants.destroy');

            Route::post('products/{product}/images', [ProductImageController::class, 'store'])
                ->name('products.images.store');
            Route::patch('products/{product}/images/{image}/primary', [ProductImageController::class, 'makePrimary'])
                ->name('products.images.make-primary');
            Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
                ->name('products.images.destroy');
        });
    });
