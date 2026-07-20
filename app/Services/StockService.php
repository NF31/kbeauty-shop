<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

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

            $previousStock = $locked->stock_quantity;

            $locked->update(['stock_quantity' => $newStock]);

            $movement = InventoryMovement::create([
                'product_variant_id' => $locked->id,
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note,
            ]);

            $this->notifyIfCrossingLowStockThreshold($locked, $previousStock, $newStock);

            return $movement;
        });
    }

    /**
     * Alerte les admins uniquement au moment où le stock franchit le seuil
     * bas vers le bas — pas à chaque mouvement une fois déjà en dessous,
     * pour éviter de spammer une notification par vente.
     */
    private function notifyIfCrossingLowStockThreshold(ProductVariant $variant, int $previousStock, int $newStock): void
    {
        $threshold = config('inventory.low_stock_threshold');

        if ($previousStock > $threshold && $newStock <= $threshold) {
            Notification::send(User::role('admin')->get(), new LowStockAlert($variant));
        }
    }
}
