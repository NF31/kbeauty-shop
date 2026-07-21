<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Enums\SkinType;
use App\Http\Controllers\Controller;
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

        $products = Product::query()
            ->where('status', ProductStatus::Published)
            ->when($skinType, fn ($query) => $query->whereJsonContains('skin_types', $skinType->value))
            ->with(['brand:id,name', 'primaryImage', 'variants'])
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

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
        ]);
    }
}
