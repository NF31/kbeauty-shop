<?php

namespace App\Http\Controllers\Admin;

use App\Application\Stock\UseCases\RecordStockMovement;
use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductVariantController extends Controller
{
    public function store(StoreProductVariantRequest $request, Product $product, RecordStockMovement $recordStockMovement): RedirectResponse
    {
        $variant = $product->variants()->create([
            ...$request->safe()->except(['option_value_ids', 'stock_quantity']),
            'stock_quantity' => 0,
        ]);

        $variant->optionValues()->sync($request->validated('option_value_ids', []));

        $initialStock = (int) $request->validated('stock_quantity', 0);

        if ($initialStock > 0) {
            $recordStockMovement($variant, InventoryMovementType::Adjustment, $initialStock, 'Stock initial à la création de la variante');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Variante « {$variant->sku} » ajoutée."]);

        return back();
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant, RecordStockMovement $recordStockMovement): RedirectResponse
    {
        abort_if($variant->product_id !== $product->id, 404);

        $requestedStock = $request->validated('stock_quantity');

        $variant->update($request->safe()->except(['option_value_ids', 'stock_quantity']));

        $variant->optionValues()->sync($request->validated('option_value_ids', []));

        if ($requestedStock !== null) {
            $delta = (int) $requestedStock - $variant->stock_quantity;

            if ($delta !== 0) {
                $recordStockMovement($variant, InventoryMovementType::Adjustment, $delta, 'Ajustement manuel depuis la fiche produit');
            }
        }

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
