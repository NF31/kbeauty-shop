<?php

namespace App\Models;

use Database\Factories\ProductLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\Attributes\Sluggable;

/**
 * Une gamme (ex. "Advanced Snail" chez COSRX, "Rice Water Bright" chez
 * Etude) : une collection de produits au sein d'une seule marque,
 * orthogonale a `Category` (le type de produit : toner, serum...).
 *
 * @property int $id
 * @property int $brand_id
 * @property string $name
 * @property string $slug
 */
#[Fillable(['brand_id', 'name', 'slug'])]
#[Sluggable(from: 'name', to: 'slug')]
class ProductLine extends Model
{
    /** @use HasFactory<ProductLineFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
