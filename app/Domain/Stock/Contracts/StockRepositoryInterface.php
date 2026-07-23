<?php

namespace App\Domain\Stock\Contracts;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;

interface StockRepositoryInterface
{
    public function lockVariant(int $variantId): ProductVariant;

    public function updateQuantity(ProductVariant $variant, int $newQuantity): void;

    public function createMovement(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        ?string $note,
    ): InventoryMovement;
}
