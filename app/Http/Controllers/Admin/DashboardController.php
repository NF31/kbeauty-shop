<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $lowStockThreshold = config('inventory.low_stock_threshold');

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'productsCount' => Product::query()->count(),
                'publishedProductsCount' => Product::query()->where('status', ProductStatus::Published)->count(),
                'lowStockVariantsCount' => ProductVariant::query()->where('stock_quantity', '<=', $lowStockThreshold)->count(),
            ],
        ]);
    }
}
