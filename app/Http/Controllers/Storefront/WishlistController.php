<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Support\WishlistPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(Request $request, CloudinaryService $cloudinary): Response
    {
        $user = $request->user();

        return Inertia::render('storefront/wishlist', [
            'products' => WishlistPresenter::present($user, $cloudinary),
            'shareToken' => $user->wishlist_share_token,
        ]);
    }

    /**
     * Idempotent (firstOrCreate sur la contrainte unique user_id+product_id) :
     * cliquer deux fois sur le coeur d'une fiche produit ne doit jamais lever
     * d'erreur de doublon, juste ne rien faire de plus la seconde fois.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlists()->firstOrCreate(['product_id' => $product->id]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Ajouté à tes favoris.')]);

        return back();
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $request->user()->wishlists()->where('product_id', $product->id)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Retiré de tes favoris.')]);

        return back();
    }

    /**
     * (Re)genere le token de partage — utilise pour revoquer un lien deja
     * partage sans supprimer la wishlist elle-meme (l'utilisateur clique de
     * nouveau sur "Partager" pour en obtenir un nouveau).
     */
    public function regenerateShareLink(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['wishlist_share_token' => Str::random(40)])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Nouveau lien de partage généré.')]);

        return back();
    }

    /**
     * Vue publique en lecture seule, sans authentification — n'importe qui
     * en possession du lien peut consulter la wishlist, jamais la modifier.
     */
    public function public(string $token, CloudinaryService $cloudinary): Response
    {
        $user = User::query()->where('wishlist_share_token', $token)->firstOrFail();

        return Inertia::render('storefront/wishlist-public', [
            'ownerName' => $user->name,
            'products' => WishlistPresenter::present($user, $cloudinary),
        ]);
    }
}
