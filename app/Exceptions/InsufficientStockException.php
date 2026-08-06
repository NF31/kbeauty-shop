<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use RuntimeException;

/**
 * Levée par AddCartItem/UpdateCartItemQuantity quand la quantité demandée
 * dépasse le stock verrouillé ; capturée par CartController pour renvoyer
 * un message d'erreur au client plutôt que de laisser planter la requête.
 */
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
