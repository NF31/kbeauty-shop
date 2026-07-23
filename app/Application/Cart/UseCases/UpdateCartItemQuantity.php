<?php

namespace App\Application\Cart\UseCases;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\CartItem;

/**
 * Remplace la quantité d'une ligne existante (pas un cumul), en revalidant
 * le stock disponible et en rafraîchissant le prix snapshot.
 */
class UpdateCartItemQuantity
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {}

    public function __invoke(CartItem $item, int $quantity): CartItem
    {
        return $this->unitOfWork->run(function () use ($item, $quantity) {
            $lockedVariant = $this->carts->lockVariant($item->product_variant_id);

            if ($quantity > $lockedVariant->stock_quantity) {
                throw new InsufficientStockException($lockedVariant, $quantity);
            }

            return $this->carts->updateItem($item, $quantity, $lockedVariant->price_cents);
        });
    }
}
