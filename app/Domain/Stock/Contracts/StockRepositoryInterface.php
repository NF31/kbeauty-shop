<?php

namespace App\Domain\Stock\Contracts;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;

interface StockRepositoryInterface
{
    /**
     * Verrouille la ligne (`SELECT ... FOR UPDATE`) pour la durée de la
     * transaction courante, afin que deux commandes concurrentes ne
     * décrémentent pas le même stock en double.
     */
    public function lockVariant(int $variantId): ProductVariant;

    public function updateQuantity(ProductVariant $variant, int $newQuantity): void;

    /**
     * Trace le mouvement de stock (vente, retour, ajustement manuel...)
     * sans lui-même modifier `stock_quantity` — à appeler en plus de
     * {@see updateQuantity()}, pas à sa place.
     */
    public function createMovement(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        ?string $note,
    ): InventoryMovement;
}
