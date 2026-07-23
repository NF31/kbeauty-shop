<?php

namespace App\Support;

use App\Enums\ProductStatus;
use App\Enums\SkinType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Construit et pagine la requête du catalogue (filtres, tri, recherche) —
 * isolé du controller pour que /catalogue reste testable et lisible
 * indépendamment du mapping Inertia (voir CatalogPresenter).
 */
class CatalogQuery
{
    /**
     * @param  Collection<int, int|string>|null  $searchIds
     * @return LengthAwarePaginator<int, Product>
     */
    public static function paginate(
        ?SkinType $skinType,
        ?Category $category,
        ?Brand $brand,
        ?string $priceMinEuros,
        ?string $priceMaxEuros,
        ?Collection $searchIds,
        ?string $sort,
    ): LengthAwarePaginator {
        $priceMin = self::toCents($priceMinEuros);
        $priceMax = self::toCents($priceMaxEuros);

        $products = Product::query()
            ->where('status', ProductStatus::Published)
            ->when($searchIds !== null, fn ($query) => $query->whereIn('products.id', $searchIds))
            ->when($skinType, fn ($query) => $query->whereJsonContains('skin_types', $skinType->value))
            ->when($category, fn ($query) => $query->whereHas(
                'categories',
                fn ($q) => $q->where('categories.id', $category->id),
            ))
            ->when($brand, fn ($query) => $query->where('brand_id', $brand->id))
            ->when($priceMin !== null || $priceMax !== null, fn ($query) => $query->whereHas(
                'variants',
                fn ($q) => $q
                    ->when($priceMin !== null, fn ($qq) => $qq->where('price_cents', '>=', $priceMin))
                    ->when($priceMax !== null, fn ($qq) => $qq->where('price_cents', '<=', $priceMax)),
            ))
            ->with(['brand:id,name,slug', 'primaryImage', 'variants']);

        match ($sort) {
            'price_asc', 'price_desc' => $products
                ->leftJoin('product_variants as default_variant', function ($join) {
                    $join->on('default_variant.product_id', '=', 'products.id')
                        ->where('default_variant.is_default', true);
                })
                ->select('products.*')
                ->orderBy('default_variant.price_cents', $sort === 'price_asc' ? 'asc' : 'desc'),
            'name_asc' => $products->orderBy('products.name'),
            default => $products->orderByDesc('products.created_at'),
        };

        return $products->paginate(24)->withQueryString();
    }

    private static function toCents(?string $euros): ?int
    {
        if ($euros === null || $euros === '' || ! is_numeric($euros)) {
            return null;
        }

        return (int) round(((float) $euros) * 100);
    }
}
