<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, CloudinaryService $cloudinary): Response
    {
        abort_if($product->status !== ProductStatus::Published, 404);

        $product->load(['brand:id,name', 'variants', 'images']);

        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

        $images = $product->images->map(fn ($image) => [
            'id' => $image->id,
            'url' => $cloudinary->url($image->path, 800, 800),
            'alt_text' => $image->alt_text,
            'product_variant_id' => $image->product_variant_id,
        ]);

        return Inertia::render('storefront/product', [
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'ingredients_inci' => $product->ingredients_inci,
                'how_to_use' => $product->how_to_use,
                'brand' => $product->brand,
            ],
            'defaultVariantId' => $defaultVariant?->id,
            'priceCents' => $defaultVariant?->price_cents,
            'compareAtPriceCents' => $defaultVariant?->compare_at_price_cents,
            'stockQuantity' => $defaultVariant?->stock_quantity,
            'images' => $images,
            'isWishlisted' => $request->user()
                ?->wishlists()
                ->where('product_id', $product->id)
                ->exists() ?? false,
            'seo' => [
                'title' => $product->meta_title ?? $product->name,
                'description' => $product->meta_description
                    ?? $product->short_description
                    ?? Str::limit(strip_tags($product->description), 160),
                'image' => $images->first()['url'] ?? null,
            ],
        ]);
    }
}
