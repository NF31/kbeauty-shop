<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with('brand:id,name')
            ->withCount('variants')
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('admin/products/index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create', [
            'brandOptions' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categoryOptions' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => ProductStatus::cases(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::create($request->safe()->except('category_ids'));

        $product->categories()->sync($request->validated('category_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produit créé.']);

        return to_route('admin.products.edit', $product);
    }

    public function edit(Product $product): Response
    {
        $product->load([
            'brand:id,name',
            'categories:id,name',
            'options.values',
            'variants.optionValues',
            'images',
        ]);

        return Inertia::render('admin/products/edit', [
            'product' => $product,
            'imageUrls' => $product->images->mapWithKeys(fn ($image) => [$image->id => $image->url(400, 400)]),
            'brandOptions' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categoryOptions' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => ProductStatus::cases(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->safe()->except('category_ids'));

        $product->categories()->sync($request->validated('category_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produit mis à jour.']);

        return to_route('admin.products.edit', $product);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Produit supprimé.']);

        return to_route('admin.products.index');
    }
}
