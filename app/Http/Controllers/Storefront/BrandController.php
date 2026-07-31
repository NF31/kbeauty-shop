<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Services\CloudinaryService;
use App\Support\CatalogPresenter;
use App\Support\CatalogQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $brands = Brand::query()
            ->whereHas('products')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'logo_path', 'country_of_origin']);

        return Inertia::render('storefront/brands-index', [
            'brands' => $brands,
            'seo' => [
                'title' => __('Nos marques'),
                'description' => __('Toutes les marques de beauté coréenne disponibles sur Korea Beauty.'),
                'image' => null,
            ],
        ]);
    }

    public function show(Request $request, Brand $brand, CloudinaryService $cloudinary): Response
    {
        $categorySlug = $request->query('category');
        $category = $categorySlug
            ? Category::query()->where('slug', $categorySlug)->first()
            : null;

        $products = CatalogQuery::paginate(
            null,
            $category,
            $brand,
            $request->query('price_min'),
            $request->query('price_max'),
            null,
            $request->query('sort'),
        );

        // Gammes : uniquement les categories qui ont au moins un produit de
        // cette marque, pour ne pas proposer des filtres qui viderait la liste.
        $categoryOptions = Category::query()
            ->whereHas('products', fn ($q) => $q->where('brand_id', $brand->id))
            ->orderBy('position')
            ->get(['slug', 'name']);

        return Inertia::render('storefront/brand', array_merge(
            CatalogPresenter::present($products, null, $category, $brand, null, $request, $cloudinary),
            [
                'brand' => [
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                    'logoUrl' => $brand->logo_path,
                    'countryOfOrigin' => $brand->country_of_origin,
                ],
                'categoryOptions' => $categoryOptions,
                'seo' => [
                    'title' => $brand->name,
                    'description' => $brand->description
                        ?? __('Découvrez les produits :brand disponibles sur Korea Beauty.', ['brand' => $brand->name]),
                    'image' => $brand->logo_path,
                ],
            ],
        ));
    }
}
