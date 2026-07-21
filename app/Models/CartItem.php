<?php

namespace App\Models;

use Brick\Money\Money;
use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $product_variant_id
 * @property int $quantity
 * @property int $unit_price_cents
 */
#[Fillable(['cart_id', 'product_variant_id', 'quantity', 'unit_price_cents'])]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Cart, $this>
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function lineTotal(string $currency): Money
    {
        return Money::ofMinor($this->unit_price_cents, $currency)->multipliedBy($this->quantity);
    }

    public function lineTotalCents(string $currency): int
    {
        return $this->lineTotal($currency)->getMinorAmount()->toInt();
    }
}
