<?php

namespace App\Application\Stock\UseCases;

use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Domain\Stock\Contracts\StockRepositoryInterface;
use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Notification;

/**
 * Enregistre un mouvement de stock et applique son delta signé à
 * `stock_quantity` de façon atomique (verrou pessimiste : deux ventes
 * concurrentes sur la même variante ne peuvent pas passer le stock en
 * négatif). `$quantity` est signé : positif pour un réassort/retour,
 * négatif pour une vente.
 */
class RecordStockMovement
{
    public function __construct(
        private readonly StockRepositoryInterface $stock,
        private readonly UnitOfWorkInterface $unitOfWork,
    ) {}

    public function __invoke(
        ProductVariant $variant,
        InventoryMovementType $type,
        int $quantity,
        ?string $note = null,
    ): InventoryMovement {
        return $this->unitOfWork->run(function () use ($variant, $type, $quantity, $note) {
            $locked = $this->stock->lockVariant($variant->id);

            $newStock = $locked->stock_quantity + $quantity;

            if ($newStock < 0) {
                throw new InsufficientStockException($locked, $quantity);
            }

            $previousStock = $locked->stock_quantity;

            $this->stock->updateQuantity($locked, $newStock);

            $movement = $this->stock->createMovement($locked, $type, $quantity, $note);

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
