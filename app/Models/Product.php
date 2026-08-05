<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Observers\ProductObserver;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Sluggable\Attributes\Sluggable;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int|null $brand_id
 * @property int|null $product_line_id
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
    'product_line_id',
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
    use HasFactory, HasTranslations, LogsActivity, Searchable, SoftDeletes;

    /**
     * Le statut initial fixe a la creation du produit n'est pas une
     * transition de publication interessante a auditer (16.5) - seuls les
     * changements de statut sur un produit existant le sont.
     *
     * @var array<int, string>
     */
    protected static array $doNotRecordEvents = ['created'];

    /**
     * @var array<int, string>
     */
    public array $translatable = ['name', 'short_description', 'description', 'ingredients_inci', 'how_to_use'];

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
     * @return BelongsTo<ProductLine, $this>
     */
    public function productLine(): BelongsTo
    {
        return $this->belongsTo(ProductLine::class);
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
     * @return HasMany<Wishlist, $this>
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Audit trail (16.5) : seul le statut de publication nous interesse
     * (qui a publie/depublie le produit et quand) - pas les champs de
     * contenu (nom, description...) qui changent trop souvent.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('product');
    }

    /**
     * Seuls les produits publiés doivent être trouvables via la recherche catalogue.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
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
