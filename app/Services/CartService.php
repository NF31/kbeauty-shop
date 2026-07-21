<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public const COOKIE_NAME = 'cart_token';

    /**
     * ~400 jours : durée maximale acceptée par les navigateurs pour un cookie
     * persistant (Chrome plafonne à 400 jours depuis 2023).
     */
    private const COOKIE_LIFETIME_MINUTES = 60 * 24 * 400;

    /**
     * Résout le panier du visiteur courant : panier lié au compte si connecté,
     * sinon panier lié au cookie `cart_token` (créé à la volée pour un invité
     * qui n'en a pas encore). La source de vérité reste toujours ce panier
     * serveur, jamais un état client (voir ARCHITECTURE.md §3).
     */
    public function current(Request $request): Cart
    {
        if ($request->user()) {
            return Cart::query()->firstOrCreate(['user_id' => $request->user()->id]);
        }

        $token = $request->cookie(self::COOKIE_NAME);

        if ($token) {
            $cart = Cart::query()->where('session_token', $token)->first();

            if ($cart) {
                return $cart;
            }
        }

        $token = Str::random(64);

        Cookie::queue(Cookie::make(self::COOKIE_NAME, $token, self::COOKIE_LIFETIME_MINUTES));

        return Cart::query()->create(['session_token' => $token]);
    }

    /**
     * Ajoute une variante au panier, ou cumule la quantité si elle y est déjà
     * (rafraîchissant au passage le prix snapshot). Verrou pessimiste sur la
     * variante et la ligne de panier pour éviter qu'un ajout concurrent ne
     * dépasse le stock disponible.
     */
    public function addItem(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        return DB::transaction(function () use ($cart, $variant, $quantity) {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $existing = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('product_variant_id', $lockedVariant->id)
                ->lockForUpdate()
                ->first();

            $requestedTotal = ($existing !== null ? $existing->quantity : 0) + $quantity;

            if ($requestedTotal > $lockedVariant->stock_quantity) {
                throw new InsufficientStockException($lockedVariant, $requestedTotal);
            }

            if ($existing) {
                $existing->update([
                    'quantity' => $requestedTotal,
                    'unit_price_cents' => $lockedVariant->price_cents,
                ]);

                return $existing->refresh();
            }

            return CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_variant_id' => $lockedVariant->id,
                'quantity' => $quantity,
                'unit_price_cents' => $lockedVariant->price_cents,
            ]);
        });
    }

    /**
     * Remplace la quantité d'une ligne existante (pas un cumul), en
     * revalidant le stock disponible et en rafraîchissant le prix snapshot.
     */
    public function updateQuantity(CartItem $item, int $quantity): CartItem
    {
        return DB::transaction(function () use ($item, $quantity) {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($item->product_variant_id);

            if ($quantity > $lockedVariant->stock_quantity) {
                throw new InsufficientStockException($lockedVariant, $quantity);
            }

            $item->update([
                'quantity' => $quantity,
                'unit_price_cents' => $lockedVariant->price_cents,
            ]);

            return $item->refresh();
        });
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    /**
     * Fusionne le panier invité dans le panier du compte qui vient de se
     * connecter (déclenché par MergeGuestCartOnLogin). Les quantités des
     * variantes déjà présentes dans le panier utilisateur sont cumulées, pas
     * dupliquées. Si le stock a changé entre-temps et ne permet plus de
     * cumuler la totalité, l'excédent est silencieusement abandonné plutôt
     * que de faire échouer la connexion.
     */
    public function mergeIntoUserCart(Cart $guestCart, User $user): void
    {
        DB::transaction(function () use ($guestCart, $user) {
            $userCart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            foreach ($guestCart->items()->with('variant')->get() as $guestItem) {
                try {
                    $this->addItem($userCart, $guestItem->variant, $guestItem->quantity);
                } catch (InsufficientStockException) {
                    // Stock insuffisant pour cumuler entièrement : on ignore l'excédent.
                }
            }

            $guestCart->delete();
        });
    }
}
