<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductOptionRequest;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductOptionController extends Controller
{
    public function store(StoreProductOptionRequest $request, Product $product): RedirectResponse
    {
        $option = $product->options()->create([
            'name' => $request->validated('name'),
            'position' => $product->options()->count(),
        ]);

        foreach ($request->validated('values') as $index => $value) {
            $option->values()->create(['value' => $value, 'position' => $index]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Axe de variante « {$option->name} » ajouté."]);

        return back();
    }

    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        abort_if($option->product_id !== $product->id, 404);

        $option->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Axe de variante supprimé.']);

        return back();
    }
}
