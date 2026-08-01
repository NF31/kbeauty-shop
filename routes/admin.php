<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductLineController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductVariantController;
use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

Route::middleware(['auth', 'role:admin|staff|support'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('permission:products.manage')->group(function () {
            // Throttle dedie : protege le CRUD catalogue d'un compte compromis ou d'un
            // bug frontend en boucle, meme si l'impact est moins critique qu'un
            // remboursement (throttle plus large que celui des uploads/refund).
            Route::middleware('throttle:60,1,admin-catalog-write')->group(function () {
                Route::resource('categories', CategoryController::class)->except('show');
                Route::resource('brands', BrandController::class)->except('show');
                Route::resource('product-lines', ProductLineController::class)->except('show');
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
            });

            // Throttle dedie : chaque upload appelle l'API Cloudinary (cout direct).
            Route::post('products/{product}/images', [ProductImageController::class, 'store'])
                ->middleware('throttle:20,1,admin-product-images')
                ->name('products.images.store');
            Route::patch('products/{product}/images/{image}/primary', [ProductImageController::class, 'makePrimary'])
                ->name('products.images.make-primary');
            Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])
                ->name('products.images.destroy');
        });

        Route::middleware('permission:orders.manage')->group(function () {
            Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            // Throttle dedie : un changement de statut declenche des effets de bord
            // (emails, mouvements de stock) — protege d'un compte compromis ou d'un
            // bug frontend en boucle, meme logique que le throttle refund ci-dessous.
            Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])
                ->middleware('throttle:30,1,admin-order-status')
                ->name('orders.update-status');
            Route::get('orders/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('orders.invoice');
        });

        Route::middleware('permission:orders.refund')->group(function () {
            // Throttle dedie : chaque appel declenche un remboursement Stripe reel et
            // irreversible — un compte admin compromis ou un bug frontend en boucle ne
            // doit pas pouvoir vider les paiements en rafale.
            Route::post('orders/{order}/refund', [OrderController::class, 'refund'])
                ->middleware('throttle:10,1,admin-refund')
                ->name('orders.refund');
        });
    });

// Vue infra (spatie/laravel-health, 22.5) - reservee au role admin strict
// (pas staff/support) : expose l'etat de la queue, de Horizon et des
// sauvegardes, hors perimetre operationnel du reste du back-office.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->get('health', HealthCheckResultsController::class)
    ->name('admin.health');
