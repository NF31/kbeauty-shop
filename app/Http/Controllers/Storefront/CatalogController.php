<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(CloudinaryService $cloudinary): Response
    {
        $products = Product::query()
            ->where('status', ProductStatus::Published)
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
        ]);
    }
}
