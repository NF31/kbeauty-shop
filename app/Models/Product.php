<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Translatable\HasTranslations;

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
#[ObservedBy(ProductObserver::class)]
#[Sluggable(from: 'name', to: 'slug')]
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
    use HasFactory, HasTranslations, Searchable, SoftDeletes;

    /**
     * @var array<int, string>
     */
    public array $translatable = ['name'];

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

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }

    /**
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    /**
     * The cover image — the one with the lowest `position` — used as the
     * thumbnail in the admin product list (docs/FEATURES.md 5.1).
     *
     * @return HasOne<ProductImage, $this>
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->ofMany('position', 'min');
    }

    /**
     * Seuls les produits publiés doivent être trouvables via la recherche catalogue.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'short_description' => $this->short_description,
        ];
    }
}
