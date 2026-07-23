<?php

namespace App\Infrastructure\Cart;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

class EloquentCartRepository implements CartRepositoryInterface
{
    public function findOrCreateForUser(int $userId): Cart
    {
        return Cart::query()->firstOrCreate(['user_id' => $userId]);
    }

    public function lockVariant(int $variantId): ProductVariant
    {
        return ProductVariant::query()->lockForUpdate()->findOrFail($variantId);
    }

    public function lockExistingItem(Cart $cart, int $variantId): ?CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();
    }

    public function createItem(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        return CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => $quantity,
            'unit_price_cents' => $variant->price_cents,
        ]);
    }

    public function updateItem(CartItem $item, int $quantity, int $unitPriceCents): CartItem
    {
        $item->update([
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
        ]);

        return $item->refresh();
    }

    public function deleteItem(CartItem $item): void
    {
        $item->delete();
    }

    public function deleteCart(Cart $cart): void
    {
        $cart->delete();
    }
}
