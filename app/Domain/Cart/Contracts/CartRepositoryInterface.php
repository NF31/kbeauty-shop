<?php

namespace App\Domain\Cart\Contracts;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;

interface CartRepositoryInterface
{
    public function findOrCreateForUser(int $userId): Cart;

    /**
     * @return Collection<int, CartItem>
     */
    public function itemsWithVariant(Cart $cart): Collection;

    public function lockVariant(int $variantId): ProductVariant;

    public function lockExistingItem(Cart $cart, int $variantId): ?CartItem;

    public function createItem(Cart $cart, ProductVariant $variant, int $quantity): CartItem;

    public function updateItem(CartItem $item, int $quantity, int $unitPriceCents): CartItem;

    public function deleteItem(CartItem $item): void;

    public function deleteCart(Cart $cart): void;
}
