<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product, CloudinaryService $cloudinary): RedirectResponse
    {
        $position = $product->images()->count();

        foreach ($request->file('images') as $file) {
            $publicId = $cloudinary->upload($file, 'products/'.$product->id);

            $product->images()->create([
                'product_variant_id' => $request->validated('product_variant_id'),
                'path' => $publicId,
                'alt_text' => $request->validated('alt_text'),
                'position' => $position++,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Image(s) ajoutée(s).']);

        return back();
    }

    /**
     * Make this image the product's cover/primary image by moving it to
     * `position` 0 — the future storefront carousel (5.2) sorts images by
     * `position` and shows this one first.
     */
    public function makePrimary(Product $product, ProductImage $image): RedirectResponse
    {
        abort_if($image->product_id !== $product->id, 404);

        DB::transaction(function () use ($product, $image) {
            $product->images()
                ->where('position', '<', $image->position)
                ->increment('position');

            $image->update(['position' => 0]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Image définie comme principale.']);

        return back();
    }

    public function destroy(Product $product, ProductImage $image, CloudinaryService $cloudinary): RedirectResponse
    {
        abort_if($image->product_id !== $product->id, 404);

        $cloudinary->destroy($image->path);
        $image->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Image supprimée.']);

        return back();
    }
}
