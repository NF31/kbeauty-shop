<?php

namespace App\Enums;

use App\Application\Stock\UseCases\RecordStockMovement;

/**
 * Étiquette d'un `InventoryMovement` ; le signe du delta appliqué au stock
 * n'est pas dérivé de ce type mais passé séparément (voir
 * {@see RecordStockMovement}).
 */
enum InventoryMovementType: string
{
    case Restock = 'restock';
    case Sale = 'sale';
    case Returned = 'return';
    case Adjustment = 'adjustment';
}
