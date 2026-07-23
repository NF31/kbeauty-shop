<?php

namespace App\Application\Cart\UseCases;

use App\Domain\Cart\Contracts\CartRepositoryInterface;
use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\User;

/**
 * Fusionne le panier invité dans le panier du compte qui vient de se
 * connecter (déclenché par MergeGuestCartOnLogin). Les quantités des
 * variantes déjà présentes dans le panier utilisateur sont cumulées, pas
 * dupliquées. Si le stock a changé entre-temps et ne permet plus de
 * cumuler la totalité, l'excédent est silencieusement abandonné plutôt
 * que de faire échouer la connexion.
 */
class MergeGuestCartIntoUserCart
{
    public function __construct(
        private readonly CartRepositoryInterface $carts,
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly AddCartItem $addCartItem,
    ) {}

    public function __invoke(Cart $guestCart, User $user): void
    {
        $this->unitOfWork->run(function () use ($guestCart, $user) {
            $userCart = $this->carts->findOrCreateForUser($user->id);

            foreach ($guestCart->items()->with('variant')->get() as $guestItem) {
                try {
                    ($this->addCartItem)($userCart, $guestItem->variant, $guestItem->quantity);
                } catch (InsufficientStockException) {
                    // Stock insuffisant pour cumuler entièrement : on ignore l'excédent.
                }
            }

            $this->carts->deleteCart($guestCart);
        });
    }
}
