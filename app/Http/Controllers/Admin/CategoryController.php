<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::query()
            ->withCount('products')
            ->with('parent:id,name')
            ->orderBy('position')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/categories/index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/create', [
            'parentOptions' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catégorie créée.']);

        return to_route('admin.categories.index');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('admin/categories/edit', [
            'category' => $category,
            'parentOptions' => Category::query()
                ->whereNot('id', $category->id)
                ->whereNotIn('id', $category->descendantIds())
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catégorie mise à jour.']);

        return to_route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catégorie supprimée.']);

        return to_route('admin.categories.index');
    }
}
