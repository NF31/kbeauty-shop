<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function index(): Response
    {
        $brands = Brand::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/brands/index', [
            'brands' => $brands,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/brands/create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        Brand::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marque créée.']);

        return to_route('admin.brands.index');
    }

    public function edit(Brand $brand): Response
    {
        return Inertia::render('admin/brands/edit', [
            'brand' => $brand,
        ]);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marque mise à jour.']);

        return to_route('admin.brands.index');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Marque supprimée.']);

        return to_route('admin.brands.index');
    }
}
