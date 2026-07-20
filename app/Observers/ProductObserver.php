<?php

namespace App\Observers;

use App\Enums\ProductStatus;
use App\Exceptions\MissingInciListException;
use App\Models\Product;

class ProductObserver
{
    public function saving(Product $product): void
    {
        if ($product->status === ProductStatus::Published && blank($product->ingredients_inci)) {
            throw new MissingInciListException;
        }
    }
}
