<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ProductLineController;
use App\Http\Controllers\Admin\ProductOptionController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\ReturnRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin|staff|support'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Messages du formulaire de contact (26.5) : pas de permission dediee, meme
        // niveau d'acces que le dashboard (consultation seule, non destructif).
        Route::get('messages', [ContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('messages/{contactMessage}', [ContactMessageController::class, 'show'])->name('contact-messages.show');
        // Throttle dedie : chaque reponse declenche un envoi d'email reel (cout direct),
        // meme logique que admin-product-images/admin-order-status.
        Route::post('messages/{contactMessage}/reply', [ContactMessageController::class, 'reply'])
            ->middleware('throttle:20,1,admin-contact-reply')
            ->name('contact-messages.reply');

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

            Route::get('return-requests', [ReturnRequestController::class, 'index'])->name('return-requests.index');
            Route::get('return-requests/{returnRequest}', [ReturnRequestController::class, 'show'])->name('return-requests.show');
            // Le refus ne declenche aucun remboursement — reste sous orders.manage
            // (staff/support peuvent traiter une demande), contrairement a
            // l'acceptation ci-dessous qui, elle, engage un remboursement Stripe reel.
            Route::post('return-requests/{returnRequest}/refuse', [ReturnRequestController::class, 'refuse'])
                ->middleware('throttle:20,1,admin-return-requests')
                ->name('return-requests.refuse');
        });

        Route::middleware('permission:orders.refund')->group(function () {
            // Throttle dedie : chaque appel declenche un remboursement Stripe reel et
            // irreversible — un compte admin compromis ou un bug frontend en boucle ne
            // doit pas pouvoir vider les paiements en rafale.
            Route::post('orders/{order}/refund', [OrderController::class, 'refund'])
                ->middleware('throttle:10,1,admin-refund')
                ->name('orders.refund');

            // Accepter une demande de retour declenche RefundOrder (26.2) — meme
            // permission et meme throttle que le remboursement manuel ci-dessus.
            Route::post('return-requests/{returnRequest}/accept', [ReturnRequestController::class, 'accept'])
                ->middleware('throttle:10,1,admin-refund')
                ->name('return-requests.accept');
        });
    });

// Vue infra (spatie/laravel-health, 22.5) - reservee au role admin strict
// (pas staff/support) : expose l'etat de la queue, de Horizon et des
// sauvegardes, hors perimetre operationnel du reste du back-office.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->get('health', [HealthController::class, 'index'])
    ->name('admin.health');

// Audit trail (spatie/laravel-activitylog, 16.5) - qui a change quoi (stock,
// statut commande, remboursement, publication produit) et quand. Reserve au
// role admin strict, meme perimetre que la page Sante ci-dessus.
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->get('activity-log', [ActivityLogController::class, 'index'])
    ->name('admin.activity-log');
