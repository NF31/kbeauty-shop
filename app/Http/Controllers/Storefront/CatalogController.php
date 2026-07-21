<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Enums\SkinType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request, CloudinaryService $cloudinary): Response
    {
        $skinType = SkinType::tryFrom((string) $request->query('skin_type'));

        $categorySlug = $request->query('category');
        $category = $categorySlug
            ? Category::query()->where('slug', $categorySlug)->first()
            : null;

        $brandSlug = $request->query('brand');
        $brand = $brandSlug
            ? Brand::query()->where('slug', $brandSlug)->first()
            : null;

        $priceMin = $this->toCents($request->query('price_min'));
        $priceMax = $this->toCents($request->query('price_max'));

        $sort = $request->query('sort');

        $search = trim((string) $request->query('q'));

        $searchIds = $search !== '' ? Product::search($search)->keys() : null;

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

        $products = $products->paginate(24)->withQueryString();

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

        return Inertia::render('storefront/catalog', [
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
        ]);
    }

    private function toCents(?string $euros): ?int
    {
        if ($euros === null || $euros === '' || ! is_numeric($euros)) {
            return null;
        }

        return (int) round(((float) $euros) * 100);
    }
}
