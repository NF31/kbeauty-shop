<?php

namespace App\Support;

use App\Enums\ProductStatus;
use App\Models\Category;

/**
 * Arbre de categories (24.3) partage sur toutes les pages storefront via
 * HandleInertiaRequests, sur le meme modele que `cart`/`honeypot`. Ne
 * remonte que les categories ayant reellement au moins un produit publie
 * (directement ou via un enfant) — sinon un lien du mega-menu redirigerait
 * vers une page catalogue vide.
 */
class MegaMenuPresenter
{
    /**
     * @return array<int, array{id: int, name: string, slug: string, hasOwnProducts: bool, children: array<int, array{id: int, name: string, slug: string}>}>
     */
    public static function categories(): array
    {
        return Category::query()
            ->whereNull('parent_id')
            ->where(fn ($query) => $query
                ->whereHas('products', fn ($q) => $q->where('status', ProductStatus::Published))
                ->orWhereHas('children.products', fn ($q) => $q->where('status', ProductStatus::Published)))
            ->with(['children' => fn ($query) => $query
                ->whereHas('products', fn ($q) => $q->where('status', ProductStatus::Published))
                ->orderBy('position')])
            ->withExists(['products as has_own_products' => fn ($q) => $q->published()])
            ->orderBy('position')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'hasOwnProducts' => (bool) $category->getAttribute('has_own_products'),
                'children' => $category->children
                    ->map(fn (Category $child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                    ])
                    ->all(),
            ])
            ->all();
    }
}
