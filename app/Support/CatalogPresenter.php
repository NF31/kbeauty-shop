<?php

namespace App\Support;

use App\Enums\SkinType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Sérialise le résultat du catalogue (produits paginés + état des filtres)
 * pour la prop Inertia de /catalogue.
 */
class CatalogPresenter
{
    /**
     * @param  LengthAwarePaginator<int, Product>  $products
     * @return array<string, mixed>
     */
    public static function present(
        LengthAwarePaginator $products,
        ?SkinType $skinType,
        ?Category $category,
        ?Brand $brand,
        ?string $search,
        Request $request,
        CloudinaryService $cloudinary,
    ): array {
        $products->through(function (Product $product) use ($cloudinary) {
            $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'brand' => $product->brand,
                'priceCents' => $defaultVariant?->price_cents,
                'compareAtPriceCents' => $defaultVariant?->compare_at_price_cents,
                'thumbnailUrl' => $product->primaryImage
                    ? $cloudinary->url($product->primaryImage->path, 400, 400)
                    : null,
            ];
        });

        $sort = $request->query('sort');

        return [
            'products' => $products,
            'activeSkinType' => $skinType
                ? ['value' => $skinType->value, 'label' => $skinType->label()]
                : null,
            'activeCategory' => $category
                ? ['slug' => $category->slug, 'name' => $category->name]
                : null,
            'activeBrand' => $brand
                ? ['slug' => $brand->slug, 'name' => $brand->name]
                : null,
            'search' => $search !== '' ? $search : null,
            'priceMin' => $request->query('price_min'),
            'priceMax' => $request->query('price_max'),
            'sort' => in_array($sort, ['price_asc', 'price_desc', 'name_asc'], true) ? $sort : null,
            'skinTypeOptions' => array_map(
                fn (SkinType $type) => ['value' => $type->value, 'label' => $type->label()],
                SkinType::cases(),
            ),
            'categoryOptions' => Category::query()
                ->orderBy('position')
                ->get(['slug', 'name'])
                ->map(fn (Category $c) => ['slug' => $c->slug, 'name' => $c->name]),
            'brandOptions' => Brand::query()
                ->orderBy('name')
                ->get(['slug', 'name'])
                ->map(fn (Brand $b) => ['slug' => $b->slug, 'name' => $b->name]),
        ];
    }
}
