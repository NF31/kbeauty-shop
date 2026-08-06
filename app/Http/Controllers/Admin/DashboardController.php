<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $lowStockThreshold = config('inventory.low_stock_threshold');

        // Le CA compte une commande dès qu'elle est payée, même si elle n'est
        // pas encore livrée — Pending (pas payée), Cancelled et Refunded en
        // sont exclues.
        $revenueStatuses = [
            OrderStatus::Paid,
            OrderStatus::Processing,
            OrderStatus::Shipped,
            OrderStatus::Delivered,
        ];

        $periodStart = Carbon::now()->subDays(13)->startOfDay();

        $ordersInPeriod = Order::query()
            ->whereIn('status', $revenueStatuses)
            ->where('placed_at', '>=', $periodStart)
            ->get(['total_cents', 'placed_at']);

        $revenueByDay = collect(range(0, 13))->map(function (int $offset) use ($periodStart, $ordersInPeriod) {
            $day = $periodStart->copy()->addDays($offset);

            return [
                'date' => $day->toDateString(),
                'revenueCents' => $ordersInPeriod
                    ->filter(fn (Order $order) => $order->placed_at !== null && $order->placed_at->isSameDay($day))
                    ->sum('total_cents'),
            ];
        });

        $lowStockVariants = ProductVariant::query()
            ->with('product:id,name')
            ->where('stock_quantity', '<=', $lowStockThreshold)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        return Inertia::render('admin/dashboard', [
            'stats' => [
                'productsCount' => Product::query()->count(),
                'publishedProductsCount' => Product::query()->published()->count(),
                'lowStockVariantsCount' => ProductVariant::query()->where('stock_quantity', '<=', $lowStockThreshold)->count(),
                'ordersCount' => Order::query()->count(),
                'pendingOrdersCount' => Order::query()->where('status', OrderStatus::Pending)->count(),
                'revenueLast30DaysCents' => Order::query()
                    ->whereIn('status', $revenueStatuses)
                    ->where('placed_at', '>=', Carbon::now()->subDays(30))
                    ->sum('total_cents'),
            ],
            'revenueByDay' => $revenueByDay,
            'recentOrders' => Order::query()
                ->with('user:id,name,email')
                ->orderByDesc('placed_at')
                ->limit(5)
                ->get()
                ->map(fn (Order $order) => [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'customerName' => $order->user?->name,
                    'status' => $order->status->value,
                    'statusLabel' => $order->status->label(),
                    'totalCents' => $order->total_cents,
                    'currency' => $order->currency,
                    'placedAt' => $order->placed_at?->toIso8601String(),
                ]),
            'lowStockVariants' => $lowStockVariants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'productName' => $variant->product?->name,
                'stockQuantity' => $variant->stock_quantity,
            ]),
        ]);
    }
}
