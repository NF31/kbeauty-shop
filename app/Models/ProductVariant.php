<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $product_id
 * @property string $sku
 * @property int $price_cents
 * @property int|null $compare_at_price_cents
 * @property string $currency
 * @property int|null $weight_grams
 * @property int $stock_quantity
 * @property bool $is_default
 * @property int $position
 */
#[Fillable([
    'product_id', 'sku', 'price_cents', 'compare_at_price_cents', 'currency',
    'weight_grams', 'stock_quantity', 'is_default', 'position',
])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory, LogsActivity;

    /**
     * La creation d'une variante (avec son stock initial) est deja tracee par
     * InventoryMovement (mouvement "adjustment") - seules les modifications
     * de stock sur une variante existante nous interessent ici (16.5).
     *
     * @var array<int, string>
     */
    protected static array $doNotRecordEvents = ['created'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Audit trail (16.5) : seul `stock_quantity` nous interesse ici (qui a
     * change le stock et quand) - les autres champs (prix, position...)
     * changent trop souvent pour etre pertinents dans ce journal.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['stock_quantity'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('stock');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values');
    }

    /**
     * @return HasMany<InventoryMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
