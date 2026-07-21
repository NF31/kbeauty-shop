<?php

use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
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

// Le tunnel de commande exige un compte (pas de checkout invité) — un
// visiteur non connecté est redirigé vers /login, puis renvoyé ici une fois
// connecté/inscrit via le mécanisme "intended URL" de Laravel (voir
// RequireAccountForCheckout). Le panier reste accessible sans compte.
Route::middleware('checkout.auth')->group(function () {
    Route::get('commande', [CheckoutController::class, 'index'])
        ->name('storefront.checkout.index');

    Route::post('commande/adresse', [CheckoutController::class, 'storeAddress'])
        ->name('storefront.checkout.store-address');

    // GET + POST sur la même URI : un rechargement de page pendant l'étape
    // paiement (GET) doit pouvoir réafficher le PaymentIntent en cours plutôt
    // que de répondre 405 — pay() est idempotent (réutilise la commande/le
    // paiement pending existants), donc sûr à rejouer sur un simple GET.
    Route::match(['get', 'post'], 'commande/paiement', [CheckoutController::class, 'pay'])
        ->name('storefront.checkout.pay');

    Route::get('commande/confirmation', [CheckoutController::class, 'confirmation'])
        ->name('storefront.checkout.confirmation');
});
