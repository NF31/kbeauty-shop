<?php

namespace App\Infrastructure\Stock;

use App\Domain\Stock\Contracts\StockRepositoryInterface;
use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;

class EloquentStockRepository implements StockRepositoryInterface
{
    public function lockVariant(int $variantId): ProductVariant
    {
        return ProductVariant::query()->lockForUpdate()->findOrFail($variantId);
    }

    public function updateQuantity(ProductVariant $variant, int $newQuantity): void
    {
        $variant->update(['stock_quantity' => $newQuantity]);
    }

    public function createMovement(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        ?string $note,
    ): InventoryMovement {
        return InventoryMovement::create([
            'product_variant_id' => $variant->id,
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note,
        ]);
    }
}
