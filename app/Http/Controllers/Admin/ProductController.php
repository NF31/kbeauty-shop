<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Enums\SkinType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(CloudinaryService $cloudinary): Response
    {
        $products = Product::query()
            ->with(['brand:id,name', 'primaryImage'])
            ->withCount('variants')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $thumbnailUrls = $products
            ->getCollection()
            ->filter(fn (Product $product) => $product->primaryImage !== null)
            ->mapWithKeys(fn (Product $product) => [
                $product->id => $cloudinary->url($product->primaryImage->path, 150, 150),
            ]);

        // `name` est traduisible (spatie/laravel-translatable) : Product::toArray()
        // renvoie le tableau brut {"fr": "...", "en": "..."} au lieu de la chaine
        // resolue par la locale courante (contrairement a l'acces direct $product->name).
        // Sans ce map, Inertia serialise l'objet brut et React plante en l'affichant.
        $products->through(fn (Product $product) => [
            'id' => $product->id,
            'name' => $product->name,
            'status' => $product->status->value,
            'is_featured' => $product->is_featured,
            'brand' => $product->brand,
            'variants_count' => $product->variants_count,
        ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
            'thumbnailUrls' => $thumbnailUrls,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create', [
            'brandOptions' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categoryOptions' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => ProductStatus::cases(),
            'skinTypeOptions' => array_map(
                fn (SkinType $type) => ['value' => $type->value, 'label' => $type->label()],
                SkinType::cases(),
            ),
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
            // `name`/`short_description`/`description`/`ingredients_inci`/`how_to_use` sont
            // traduisibles (spatie/laravel-translatable) : passer $product brut renverrait
            // {"fr": "...", "en": "..."} au lieu de la chaine resolue par la locale courante.
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'ingredients_inci' => $product->ingredients_inci,
                'how_to_use' => $product->how_to_use,
                'skin_types' => $product->skin_types,
                'status' => $product->status->value,
                'is_featured' => $product->is_featured,
                'brand' => $product->brand,
                'categories' => $product->categories,
                'options' => $product->options,
                'variants' => $product->variants,
                'images' => $product->images,
            ],
            'imageUrls' => $product->images->mapWithKeys(fn ($image) => [$image->id => $image->url(400, 400)]),
            'brandOptions' => Brand::query()->orderBy('name')->get(['id', 'name']),
            'categoryOptions' => Category::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => ProductStatus::cases(),
            'skinTypeOptions' => array_map(
                fn (SkinType $type) => ['value' => $type->value, 'label' => $type->label()],
                SkinType::cases(),
            ),
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
