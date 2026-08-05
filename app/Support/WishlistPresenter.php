<?php

namespace App\Support;

use App\Models\Product;
use App\Models\User;
use App\Services\CloudinaryService;

/**
 * Serialise la wishlist d'un utilisateur pour le front, reutilise a la fois
 * par la page compte (mon-compte/favoris) et la page de partage public — un
 * seul format, meme pattern que CartPresenter.
 */
class WishlistPresenter
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function present(User $user, CloudinaryService $cloudinary): array
    {
        $products = Product::query()
            ->published()
            ->whereHas('wishlists', fn ($query) => $query->where('user_id', $user->id))
            ->with(['brand', 'primaryImage', 'variants'])
            ->get();

        return array_values($products->map(function (Product $product) use ($cloudinary) {
            $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'brand' => $product->brand ? [
                    'id' => $product->brand->id,
                    'name' => $product->brand->name,
                ] : null,
                'priceCents' => $defaultVariant?->price_cents,
                'thumbnailUrl' => $product->primaryImage
                    ? $cloudinary->url($product->primaryImage->path, 400, 400)
                    : null,
            ];
        })->all());
    }
}
