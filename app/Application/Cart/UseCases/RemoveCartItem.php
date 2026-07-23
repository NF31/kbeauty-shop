<?php

namespace App\Application\Cart\UseCases;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Models\CartItem;

class RemoveCartItem
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
    ) {}

    public function __invoke(CartItem $item): void
    {
        $this->carts->deleteItem($item);
    }
}
