<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\Attributes\Sluggable;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $position
 */
#[Fillable(['parent_id', 'name', 'slug', 'position'])]
#[Sluggable(from: 'name', to: 'slug')]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    /**
     * IDs of all categories nested under this one, at any depth — used to
     * reject a parent selection that would turn the tree into a cycle.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $childIds = static::query()->where('parent_id', $this->id)->pluck('id')->all();

        foreach ($childIds as $childId) {
            $child = static::query()->where('id', $childId)->first();
            $childIds = [...$childIds, ...($child?->descendantIds() ?? [])];
        }

        return $childIds;
    }
}
