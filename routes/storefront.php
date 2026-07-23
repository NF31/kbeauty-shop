<?php

use App\Http\Controllers\Storefront\AccountAddressController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\LegalController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SkinGuideController;
use Illuminate\Support\Facades\Route;

Route::get('produits', [CatalogController::class, 'index'])
    ->middleware('locale:fr')
    ->name('storefront.products.index');

Route::get('produits/{product:slug}', [ProductController::class, 'show'])
    ->middleware('locale:fr')
    ->name('storefront.products.show');

Route::get('panier', [CartController::class, 'index'])
    ->middleware('locale:fr')
    ->name('storefront.cart.index');

// Écriture panier (invité ou connecté) : throttle par IP pour empêcher un
// script de spammer cart_items ou de forcer la revalidation stock en boucle.
// Prefixe explicite ('storefront-cart') : le throttle basique de Laravel ne clé
// que sur IP/utilisateur (pas sur la route), donc sans préfixe distinct ce
// compteur serait partagé avec n'importe quel autre groupe `throttle:X,1`.
Route::middleware(['locale:fr', 'throttle:30,1,storefront-cart'])->group(function () {
    Route::post('panier', [CartController::class, 'store'])
        ->name('storefront.cart.store');

    Route::patch('panier/{cartItem}', [CartController::class, 'update'])
        ->name('storefront.cart.update');

    Route::delete('panier/{cartItem}', [CartController::class, 'destroy'])
        ->name('storefront.cart.destroy');
});

// Le tunnel de commande exige un compte (pas de checkout invité) — un
// visiteur non connecté est redirigé vers /login, puis renvoyé ici une fois
// connecté/inscrit via le mécanisme "intended URL" de Laravel (voir
// RequireAccountForCheckout). Le panier reste accessible sans compte.
// Throttle un peu plus serré ici : commande/paiement appelle l'API Stripe
// (création/relecture de PaymentIntent) à chaque requête, donc un abus a un
// coût direct et un risque d'être flaggé "suspicious activity" côté Stripe.
Route::middleware(['locale:fr', 'checkout.auth', 'throttle:20,1,storefront-checkout'])->group(function () {
    Route::get('commande', [CheckoutController::class, 'index'])
        ->name('storefront.checkout.index');

    Route::post('commande/adresse', [CheckoutController::class, 'storeAddress'])
        ->name('storefront.checkout.store-address');

    Route::match(['get', 'post'], 'commande/paiement', [CheckoutController::class, 'pay'])
        ->name('storefront.checkout.pay');

    Route::get('commande/confirmation', [CheckoutController::class, 'confirmation'])
        ->name('storefront.checkout.confirmation');
});

// Pilote i18n (25.1) puis extension au tunnel d'achat : version anglaise des
// memes pages, prefixee /en. Le francais reste la locale par defaut, sans
// prefixe (voir SetLocale).
Route::prefix('en')->name('en.')->middleware('locale:en')->group(function () {
    Route::get('produits', [CatalogController::class, 'index'])
        ->name('storefront.products.index');

    Route::get('produits/{product:slug}', [ProductController::class, 'show'])
        ->name('storefront.products.show');

    Route::get('panier', [CartController::class, 'index'])
        ->name('storefront.cart.index');

    Route::middleware('throttle:30,1,storefront-cart')->group(function () {
        Route::post('panier', [CartController::class, 'store'])
            ->name('storefront.cart.store');

        Route::patch('panier/{cartItem}', [CartController::class, 'update'])
            ->name('storefront.cart.update');

        Route::delete('panier/{cartItem}', [CartController::class, 'destroy'])
            ->name('storefront.cart.destroy');
    });

    Route::middleware(['checkout.auth', 'throttle:20,1,storefront-checkout'])->group(function () {
        Route::get('commande', [CheckoutController::class, 'index'])
            ->name('storefront.checkout.index');

        Route::post('commande/adresse', [CheckoutController::class, 'storeAddress'])
            ->name('storefront.checkout.store-address');

        Route::match(['get', 'post'], 'commande/paiement', [CheckoutController::class, 'pay'])
            ->name('storefront.checkout.pay');

        Route::get('commande/confirmation', [CheckoutController::class, 'confirmation'])
            ->name('storefront.checkout.confirmation');
    });
});

Route::get('guide-de-choix', [SkinGuideController::class, 'index'])
    ->name('storefront.skin-guide');

Route::get('mentions-legales', [LegalController::class, 'mentions'])
    ->name('storefront.legal.mentions');

Route::get('cgv', [LegalController::class, 'cgv'])
    ->name('storefront.legal.cgv');

Route::get('confidentialite', [LegalController::class, 'confidentialite'])
    ->name('storefront.legal.confidentialite');

Route::get('livraison', [LegalController::class, 'livraison'])
    ->name('storefront.legal.livraison');

Route::get('retours', [LegalController::class, 'retours'])
    ->name('storefront.legal.retours');

Route::middleware('auth')->group(function () {
    Route::get('mon-compte/commandes', [AccountController::class, 'orders'])
        ->name('storefront.account.orders');

    Route::get('mon-compte/commandes/{order}', [AccountController::class, 'show'])
        ->name('storefront.account.orders.show');

    Route::get('mon-compte/commandes/{order}/facture', [AccountController::class, 'downloadInvoice'])
        ->name('storefront.account.orders.invoice');

    Route::get('mon-compte/adresses', [AccountAddressController::class, 'index'])
        ->name('storefront.account.addresses.index');

    Route::middleware('throttle:30,1,storefront-account-address')->group(function () {
        Route::post('mon-compte/adresses', [AccountAddressController::class, 'store'])
            ->name('storefront.account.addresses.store');

        Route::put('mon-compte/adresses/{address}', [AccountAddressController::class, 'update'])
            ->name('storefront.account.addresses.update');

        Route::delete('mon-compte/adresses/{address}', [AccountAddressController::class, 'destroy'])
            ->name('storefront.account.addresses.destroy');
    });
});
