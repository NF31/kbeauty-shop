<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CloudinaryService;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product, CloudinaryService $cloudinary): Response
    {
        abort_if($product->status !== ProductStatus::Published, 404);

        $product->load(['brand:id,name', 'variants', 'images']);

        $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

        return Inertia::render('storefront/product', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'ingredients_inci' => $product->ingredients_inci,
                'how_to_use' => $product->how_to_use,
                'brand' => $product->brand,
            ],
            'priceCents' => $defaultVariant?->price_cents,
            'stockQuantity' => $defaultVariant?->stock_quantity,
            'images' => $product->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $cloudinary->url($image->path, 800, 800),
                'alt_text' => $image->alt_text,
                'product_variant_id' => $image->product_variant_id,
            ]),
        ]);
    }
}
