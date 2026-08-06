<?php

namespace App\Application\Cart\UseCases;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Models\CartItem;

/**
 * Supprime une ligne de panier. Pas de restitution de stock ici : le stock
 * n'est décrémenté qu'à la confirmation de commande (9.4), jamais réservé
 * tant qu'un article reste au panier.
 */
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
