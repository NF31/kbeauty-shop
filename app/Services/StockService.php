<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Enregistre un mouvement de stock et applique son delta signé à
     * `stock_quantity` de façon atomique (verrou pessimiste : deux ventes
     * concurrentes sur la même variante ne peuvent pas passer le stock en
     * négatif). `$quantity` est signé : positif pour un réassort/retour,
     * négatif pour une vente.
     */
    public function recordMovement(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        ?string $note = null,
    ): InventoryMovement {
        return DB::transaction(function () use ($variant, $type, $quantity, $note) {
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);

            $newStock = $locked->stock_quantity + $quantity;

            if ($newStock < 0) {
                throw new InsufficientStockException($locked, $quantity);
            }

            $locked->update(['stock_quantity' => $newStock]);

            return InventoryMovement::create([
                'product_variant_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note,
            ]);
        });
    }
}
