<?php

namespace App\Models;

use App\Services\CloudinaryService;
use Database\Factories\ProductImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $path
 * @property string|null $alt_text
 * @property int $position
 */
#[Fillable(['product_id', 'product_variant_id', 'path', 'alt_text', 'position'])]
class ProductImage extends Model
{
    /** @use HasFactory<ProductImageFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Cloudinary delivery URL built at request time from the stored
     * `public_id` (`path`) — no transformed file is ever duplicated in
     * storage, per docs/FEATURES.md 5.1.
     */
    public function url(int $width = 800, int $height = 800): string
    {
        return app(CloudinaryService::class)->url($this->path, $width, $height);
    }
}
