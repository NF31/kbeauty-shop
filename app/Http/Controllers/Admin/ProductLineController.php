<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductLineRequest;
use App\Http\Requests\Admin\UpdateProductLineRequest;
use App\Models\Brand;
use App\Models\ProductLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductLineController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));

        $productLines = ProductLine::query()
            ->withCount('products')
            ->with('brand:id,name')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/product-lines/index', [
            'productLines' => $productLines,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/product-lines/create', [
            'brandOptions' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreProductLineRequest $request): RedirectResponse
    {
        ProductLine::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gamme créée.']);

        return to_route('admin.product-lines.index');
    }

    public function edit(ProductLine $productLine): Response
    {
        return Inertia::render('admin/product-lines/edit', [
            'productLine' => $productLine,
            'brandOptions' => Brand::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateProductLineRequest $request, ProductLine $productLine): RedirectResponse
    {
        $productLine->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gamme mise à jour.']);

        return to_route('admin.product-lines.index');
    }

    public function destroy(ProductLine $productLine): RedirectResponse
    {
        $productLine->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Gamme supprimée.']);

        return to_route('admin.product-lines.index');
    }
}
