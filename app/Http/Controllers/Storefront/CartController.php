<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\CloudinaryService;
use App\Support\CartPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request, CartService $cartService, CloudinaryService $cloudinary): Response
    {
        $cart = $cartService->current($request);

        return Inertia::render('storefront/cart', CartPresenter::present($cart, $cloudinary));
    }

    public function store(AddCartItemRequest $request, CartService $cartService): RedirectResponse
    {
        $cart = $cartService->current($request);
        $variant = ProductVariant::query()->findOrFail((int) $request->validated('product_variant_id'));

        try {
            $cartService->addItem($cart, $variant, (int) $request->validated('quantity'));
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return back();
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem, CartService $cartService): RedirectResponse
    {
        $this->authorizeCartItem($request, $cartItem, $cartService);

        try {
            $cartService->updateQuantity($cartItem, (int) $request->validated('quantity'));
        } catch (InsufficientStockException $e) {
            throw ValidationException::withMessages(['quantity' => $e->getMessage()]);
        }

        return back();
    }

    public function destroy(Request $request, CartItem $cartItem, CartService $cartService): RedirectResponse
    {
        $this->authorizeCartItem($request, $cartItem, $cartService);

        $cartService->removeItem($cartItem);

        return back();
    }

    /**
     * Un panier n'est accessible que par son propriétaire (compte ou cookie invité) — empêche
     * de modifier/supprimer la ligne de panier d'un autre visiteur en devinant son id.
     */
    private function authorizeCartItem(Request $request, CartItem $cartItem, CartService $cartService): void
    {
        $cart = $cartService->current($request);

        abort_if($cartItem->cart_id !== $cart->id, 403);
    }
}
