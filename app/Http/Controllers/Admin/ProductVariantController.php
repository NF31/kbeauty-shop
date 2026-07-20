<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, Product $product): RedirectResponse
    {
        $variant = $product->variants()->create($request->safe()->except('option_value_ids'));

        $variant->optionValues()->sync($request->validated('option_value_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => "Variante « {$variant->sku} » ajoutée."]);

        return back();
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $variant->update($request->safe()->except('option_value_ids'));

        $variant->optionValues()->sync($request->validated('option_value_ids', []));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Variante mise à jour.']);

        return back();
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $variant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Variante supprimée.']);

        return back();
    }
}
