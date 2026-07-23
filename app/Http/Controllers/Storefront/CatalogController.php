<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\SkinType;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CloudinaryService;
use App\Support\CatalogPresenter;
use App\Support\CatalogQuery;
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

        $search = trim((string) $request->query('q'));

        $searchIds = $search !== '' ? Product::search($search)->keys() : null;

        $products = CatalogQuery::paginate(
            $skinType,
            $category,
            $brand,
            $request->query('price_min'),
            $request->query('price_max'),
            $searchIds,
            $request->query('sort'),
        );

        return Inertia::render('storefront/catalog', CatalogPresenter::present(
            $products,
            $skinType,
            $category,
            $brand,
            $search,
            $request,
            $cloudinary,
        ));
    }
}
