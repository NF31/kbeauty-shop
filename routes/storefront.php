<?php

use App\Http\Controllers\Storefront\AccountAddressController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\BrandController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\DiagnosticController;
use App\Http\Controllers\Storefront\LegalController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SkinGuideController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;
use Spatie\Honeypot\ProtectAgainstSpam;

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

// Favoris (26.3) : ajout/retrait reserves aux comptes connectes (pas de wishlist
// invite, contrairement au panier) - throttle par utilisateur pour eviter le
// spam de clics sur le coeur d'une fiche produit.
Route::middleware(['auth', 'locale:fr', 'throttle:30,1,storefront-wishlist'])->group(function () {
    Route::post('favoris/{product:slug}', [WishlistController::class, 'store'])
        ->name('storefront.wishlist.store');

    Route::delete('favoris/{product:slug}', [WishlistController::class, 'destroy'])
        ->name('storefront.wishlist.destroy');
});

// Lien de partage public (26.3) : lecture seule, pas d'auth requise - n'importe
// qui en possession du token peut consulter la wishlist, jamais la modifier.
Route::get('favoris/partages/{token}', [WishlistController::class, 'public'])
    ->middleware('locale:fr')
    ->name('storefront.wishlist.public');

// Formulaire newsletter du footer (13.2), accessible depuis n'importe quelle page - throttle
// dedie + honeypot (13.4) : les champs sont fournis via le prop Inertia partage 'honeypot'
// (voir HandleInertiaRequests) et rendus caches dans storefront-footer.tsx.
Route::middleware(['locale:fr', 'throttle:10,1,storefront-newsletter', ProtectAgainstSpam::class])->group(function () {
    Route::post('newsletter', [NewsletterController::class, 'store'])
        ->name('storefront.newsletter.store');
});

// Double opt-in (13.3) : lien signe recu par email, pas de version /en - les notifications ne
// sont pas encore localisees dans le projet (voir OrderConfirmation, RefundConfirmation...).
Route::get('newsletter/confirmer/{subscriber}', [NewsletterController::class, 'confirm'])
    ->middleware(['locale:fr', 'signed'])
    ->name('storefront.newsletter.confirm');

// Formulaire de contact (26.5) : pas de table dediee, le message part directement en email aux
// admins (ContactController::store) - meme throttle/honeypot que newsletter (formulaire public).
Route::get('contact', [ContactController::class, 'index'])
    ->middleware('locale:fr')
    ->name('storefront.contact.index');

Route::middleware(['locale:fr', 'throttle:10,1,storefront-contact', ProtectAgainstSpam::class])->group(function () {
    Route::post('contact', [ContactController::class, 'store'])
        ->name('storefront.contact.store');
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

    Route::middleware(['auth', 'throttle:30,1,storefront-wishlist'])->group(function () {
        Route::post('favoris/{product:slug}', [WishlistController::class, 'store'])
            ->name('storefront.wishlist.store');

        Route::delete('favoris/{product:slug}', [WishlistController::class, 'destroy'])
            ->name('storefront.wishlist.destroy');
    });

    Route::get('favoris/partages/{token}', [WishlistController::class, 'public'])
        ->name('storefront.wishlist.public');

    Route::middleware(['throttle:10,1,storefront-newsletter', ProtectAgainstSpam::class])->group(function () {
        Route::post('newsletter', [NewsletterController::class, 'store'])
            ->name('storefront.newsletter.store');
    });

    Route::get('contact', [ContactController::class, 'index'])
        ->name('storefront.contact.index');

    Route::middleware(['throttle:10,1,storefront-contact', ProtectAgainstSpam::class])->group(function () {
        Route::post('contact', [ContactController::class, 'store'])
            ->name('storefront.contact.store');
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

    Route::get('marques', [BrandController::class, 'index'])
        ->name('storefront.brands.index');

    Route::get('marques/{brand:slug}', [BrandController::class, 'show'])
        ->name('storefront.brands.show');

    Route::get('guide-de-choix', [SkinGuideController::class, 'index'])
        ->name('storefront.skin-guide');

    Route::get('diagnostic-peau', [DiagnosticController::class, 'index'])
        ->name('storefront.diagnostic.index');

    Route::post('diagnostic-peau', [DiagnosticController::class, 'analyze'])
        ->middleware('throttle:3,60,storefront-diagnostic')
        ->name('storefront.diagnostic.analyze');

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

        Route::get('mon-compte/favoris', [WishlistController::class, 'index'])
            ->name('storefront.account.wishlist.index');

        Route::post('mon-compte/favoris/partager', [WishlistController::class, 'regenerateShareLink'])
            ->name('storefront.account.wishlist.share');
    });
});

Route::get('marques', [BrandController::class, 'index'])
    ->middleware('locale:fr')
    ->name('storefront.brands.index');

Route::get('marques/{brand:slug}', [BrandController::class, 'show'])
    ->middleware('locale:fr')
    ->name('storefront.brands.show');

Route::get('guide-de-choix', [SkinGuideController::class, 'index'])
    ->middleware('locale:fr')
    ->name('storefront.skin-guide');

// Diagnostic peau (montee en competence, Phase 3 - voir docs/montee-competence/ROADMAP.md).
// Le formulaire (GET) n'est pas throttle, seule l'analyse (POST) l'est : c'est
// elle qui appelle kbeauty-ai-core-service et qu'on veut proteger d'un abus.
Route::get('diagnostic-peau', [DiagnosticController::class, 'index'])
    ->middleware('locale:fr')
    ->name('storefront.diagnostic.index');

Route::post('diagnostic-peau', [DiagnosticController::class, 'analyze'])
    ->middleware(['locale:fr', 'throttle:3,60,storefront-diagnostic'])
    ->name('storefront.diagnostic.analyze');

Route::get('mentions-legales', [LegalController::class, 'mentions'])
    ->middleware('locale:fr')
    ->name('storefront.legal.mentions');

Route::get('cgv', [LegalController::class, 'cgv'])
    ->middleware('locale:fr')
    ->name('storefront.legal.cgv');

Route::get('confidentialite', [LegalController::class, 'confidentialite'])
    ->middleware('locale:fr')
    ->name('storefront.legal.confidentialite');

Route::get('livraison', [LegalController::class, 'livraison'])
    ->middleware('locale:fr')
    ->name('storefront.legal.livraison');

Route::get('retours', [LegalController::class, 'retours'])
    ->middleware('locale:fr')
    ->name('storefront.legal.retours');

Route::middleware(['auth', 'locale:fr'])->group(function () {
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

    Route::get('mon-compte/favoris', [WishlistController::class, 'index'])
        ->name('storefront.account.wishlist.index');

    Route::post('mon-compte/favoris/partager', [WishlistController::class, 'regenerateShareLink'])
        ->name('storefront.account.wishlist.share');
});
