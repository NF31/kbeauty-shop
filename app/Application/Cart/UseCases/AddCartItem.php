<?php

namespace App\Application\Cart\UseCases;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;

/**
 * Ajoute une variante au panier, ou cumule la quantité si elle y est déjà
 * (rafraîchissant au passage le prix snapshot). Verrou pessimiste sur la
 * variante et la ligne de panier pour éviter qu'un ajout concurrent ne
 * dépasse le stock disponible.
 */
class AddCartItem
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {}

    public function __invoke(Cart $cart, ProductVariant $variant, int $quantity): CartItem
    {
        return $this->unitOfWork->run(function () use ($cart, $variant, $quantity) {
            $lockedVariant = $this->carts->lockVariant($variant->id);

            $existing = $this->carts->lockExistingItem($cart, $lockedVariant->id);

            $requestedTotal = ($existing !== null ? $existing->quantity : 0) + $quantity;

            if ($requestedTotal > $lockedVariant->stock_quantity) {
                throw new InsufficientStockException($lockedVariant, $requestedTotal);
            }

            if ($existing) {
                return $this->carts->updateItem($existing, $requestedTotal, $lockedVariant->price_cents);
            }

            return $this->carts->createItem($cart, $lockedVariant, $quantity);
        });
    }
}
