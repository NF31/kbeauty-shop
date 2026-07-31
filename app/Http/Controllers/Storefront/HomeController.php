<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Services\CloudinaryService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(CloudinaryService $cloudinary): Response
    {
        $products = Product::query()
            ->published()
            ->with(['brand:id,name,slug', 'primaryImage', 'variants'])
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(function (Product $product) use ($cloudinary) {
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

        $brands = Brand::query()
            ->whereHas('products')
            ->orderBy('name')
            ->limit(6)
            ->get(['name', 'slug']);

        return Inertia::render('storefront/home', [
            'products' => $products,
            'brands' => $brands,
        ]);
    }
}
