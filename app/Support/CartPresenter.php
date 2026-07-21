<?php

namespace App\Support;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CloudinaryService;

/**
 * Sérialise un panier pour le front (page /panier ET prop Inertia partagée
 * du mini-panier) — un seul format, pour ne jamais faire diverger les deux.
 */
class CartPresenter
{
    /**
     * @return array{items: list<array<string, mixed>>, subtotalCents: int, totalCents: int, currency: string, itemCount: int}
     */
    public static function present(?Cart $cart, CloudinaryService $cloudinary): array
    {
        if (! $cart) {
            return [
                'items' => [],
                'subtotalCents' => 0,
                'totalCents' => 0,
                'currency' => 'EUR',
                'itemCount' => 0,
            ];
        }

        $cart->loadMissing(['items.variant.product.primaryImage']);

        $items = array_values($cart->items->map(fn (CartItem $item) => [
            'id' => $item->id,
            'productName' => $item->variant->product->name,
            'productSlug' => $item->variant->product->slug,
            'sku' => $item->variant->sku,
            'quantity' => $item->quantity,
            'unitPriceCents' => $item->unit_price_cents,
            'lineTotalCents' => $item->lineTotalCents($cart->currency),
            'stockQuantity' => $item->variant->stock_quantity,
            'thumbnailUrl' => $item->variant->product->primaryImage
                ? $cloudinary->url($item->variant->product->primaryImage->path, 200, 200)
                : null,
        ])->all());

        return [
            'items' => $items,
            'subtotalCents' => $cart->subtotalCents(),
            'totalCents' => $cart->totalCents(),
            'currency' => $cart->currency,
            'itemCount' => (int) $cart->items->sum('quantity'),
        ];
    }
}
