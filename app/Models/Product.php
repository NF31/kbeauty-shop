<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $brand_id
 * @property string $name
 * @property string $slug
 * @property string|null $short_description
 * @property string $description
 * @property string|null $ingredients_inci
 * @property string|null $how_to_use
 * @property array<int, string>|null $skin_types
 * @property string|null $period_after_opening
 * @property ProductStatus $status
 * @property bool $is_featured
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'brand_id',
    'name',
    'slug',
    'short_description',
    'description',
    'ingredients_inci',
    'how_to_use',
    'skin_types',
    'period_after_opening',
    'status',
    'is_featured',
    'meta_title',
    'meta_description',
    'published_at',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'skin_types' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
