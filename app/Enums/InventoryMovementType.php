<?php

namespace App\Enums;

enum InventoryMovementType: string
{
    case Restock = 'restock';
    case Sale = 'sale';
    case Returned = 'return';
    case Adjustment = 'adjustment';
}
