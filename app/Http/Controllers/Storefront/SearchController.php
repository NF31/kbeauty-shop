<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Autocomplete du header (26.x) : produits (Scout), marques et gammes
     * correspondant au texte tape, pour eviter de forcer un aller-retour
     * complet sur /produits juste pour voir s'il y a des resultats.
     */
    public function suggest(Request $request, CloudinaryService $cloudinary): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json(['products' => [], 'brands' => [], 'productLines' => []]);
        }

        // Marque et prix par defaut joints directement dans la requete Scout (au lieu
        // de 2 ->load() separes) - chaque aller-retour vers Neon coute ~75ms, non
        // negligeable sur un endpoint tape a chaque frappe. primaryImage reste un
        // ->load() a part : sa logique (plus petite position) vit deja dans le
        // modele, pas dupliquee ici en SQL brut.
        $products = Product::search($term)
            ->query(fn ($query) => $query
                ->select('products.*', 'brands.name as brand_name', 'default_variant.price_cents as default_price_cents')
                ->join('brands', 'brands.id', '=', 'products.brand_id')
                ->leftJoin('product_variants as default_variant', fn ($join) => $join
                    ->on('default_variant.product_id', '=', 'products.id')
                    ->where('default_variant.is_default', true)))
            ->take(5)
            ->get()
            ->load('primaryImage')
            ->map(fn (Product $product) => [
                'slug' => $product->slug,
                'name' => $product->name,
                'brand' => $product->getAttribute('brand_name'),
                'priceCents' => $product->getAttribute('default_price_cents'),
                'thumbnailUrl' => $product->primaryImage
                    ? $cloudinary->url($product->primaryImage->path, 80, 80)
                    : null,
            ])
            ->values();

        $brands = Brand::query()
            ->whereLike('name', "%{$term}%")
            ->orderBy('name')
            ->limit(3)
            ->get(['slug', 'name']);

        $productLines = ProductLine::query()
            ->whereLike('name', "%{$term}%")
            ->orderBy('name')
            ->limit(3)
            ->get(['slug', 'name']);

        return response()->json([
            'products' => $products,
            'brands' => $brands,
            'productLines' => $productLines,
        ]);
    }
}
