<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(ProductVariant $variant, int $requestedQuantity)
    {
        parent::__construct(sprintf(
            "Stock insuffisant pour la variante '%s' (stock actuel : %d, quantité demandée : %d).",
            $variant->sku,
            $variant->stock_quantity,
            abs($requestedQuantity)
        ));
    }
}
