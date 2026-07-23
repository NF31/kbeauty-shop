<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(ProductVariant $variant, int $requestedQuantity)
    {
        parent::__construct(__(
            "Stock insuffisant pour la variante ':sku' (stock actuel : :stock, quantité demandée : :requested).",
            [
                'sku' => $variant->sku,
                'stock' => $variant->stock_quantity,
                'requested' => abs($requestedQuantity),
            ]
        ));
    }
}
