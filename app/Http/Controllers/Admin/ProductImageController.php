<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ProductImageController extends Controller
{
    public function store(StoreProductImageRequest $request, Product $product, CloudinaryService $cloudinary): RedirectResponse
    {
        $publicId = $cloudinary->upload($request->file('image'), 'products/'.$product->id);

        $product->images()->create([
            'product_variant_id' => $request->validated('product_variant_id'),
            'path' => $publicId,
            'alt_text' => $request->validated('alt_text'),
            'position' => $product->images()->count(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Image ajoutée.']);

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
