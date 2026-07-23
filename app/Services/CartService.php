<?php

namespace App\Services;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
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
     * Panier du visiteur courant s'il en a déjà un, sans jamais en créer un
     * (contrairement à current()) — pour le badge/mini-panier du header,
     * qu'on ne veut pas déclencher pour chaque visiteur qui ne touche jamais
     * au panier.
     */
    public function findExisting(Request $request): ?Cart
    {
        if ($request->user()) {
            return Cart::query()->where('user_id', $request->user()->id)->first();
        }

        $token = $request->cookie(self::COOKIE_NAME);

        return $token ? Cart::query()->where('session_token', $token)->first() : null;
    }
}
