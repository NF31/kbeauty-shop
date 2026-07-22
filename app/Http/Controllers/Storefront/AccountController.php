<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Page d'accueil de l'espace client apres connexion : dernieres commandes
     * et raccourcis vers commandes/adresses (les admins/staff/support sont
     * eux redirigés vers /admin par App\Http\Responses\LoginResponse).
     */
    public function dashboard(Request $request, CloudinaryService $cloudinary): Response
    {
        $user = $request->user();

        $recentOrders = $user->orders()
            ->with('items')
            ->orderByDesc('placed_at')
            ->limit(3)
            ->get();

        return Inertia::render('storefront/dashboard', [
            'ordersCount' => $user->orders()->count(),
            'addressesCount' => $user->addresses()->count(),
            'recentOrders' => $recentOrders->map(fn (Order $order) => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
                'placedAt' => $order->placed_at?->toIso8601String(),
                'items' => $order->items->map(fn (OrderItem $item) => $this->formatItem($item, $cloudinary)),
            ]),
        ]);
    }

    public function orders(Request $request, CloudinaryService $cloudinary): Response
    {
        $orders = $request->user()
            ->orders()
            ->with('items')
            ->orderByDesc('placed_at')
            ->paginate(10)
            ->withQueryString();

        $orders->through(fn (Order $order) => [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'status' => $order->status->value,
            'statusLabel' => $order->status->label(),
            'totalCents' => $order->total_cents,
            'currency' => $order->currency,
            'placedAt' => $order->placed_at?->toIso8601String(),
            'itemsCount' => $order->items->sum('quantity'),
            'items' => $order->items->map(fn (OrderItem $item) => $this->formatItem($item, $cloudinary)),
        ]);

        return Inertia::render('storefront/account-orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Une commande n'est consultable que par son propriétaire — empêche de
     * voir le détail d'une commande d'un autre client en devinant son id.
     */
    public function show(Request $request, Order $order, CloudinaryService $cloudinary): Response
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items', 'shippingAddress', 'billingAddress', 'payments']);

        $formatAddress = fn (?Address $address) => $address ? [
            'fullName' => $address->full_name,
            'line1' => $address->line1,
            'line2' => $address->line2,
            'postalCode' => $address->postal_code,
            'city' => $address->city,
            'countryCode' => $address->country_code,
            'phone' => $address->phone,
        ] : null;

        return Inertia::render('storefront/account-order', [
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'status' => $order->status->value,
                'statusLabel' => $order->status->label(),
                'currency' => $order->currency,
                'subtotalCents' => $order->subtotal_cents,
                'discountCents' => $order->discount_cents,
                'shippingCents' => $order->shipping_cents,
                'taxCents' => $order->tax_cents,
                'totalCents' => $order->total_cents,
                'placedAt' => $order->placed_at?->toIso8601String(),
                'shippingAddress' => $formatAddress($order->shippingAddress),
                'billingAddress' => $formatAddress($order->billingAddress),
                'items' => $order->items->map(fn (OrderItem $item) => $this->formatItem($item, $cloudinary)),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatItem(OrderItem $item, CloudinaryService $cloudinary): array
    {
        return [
            'productName' => $item->product_name,
            'variantLabel' => $item->variant_label,
            'imageUrl' => $item->product_image_path
                ? $cloudinary->url($item->product_image_path, 200, 200)
                : null,
            'quantity' => $item->quantity,
            'unitPriceCents' => $item->unit_price_cents,
            'totalCents' => $item->total_cents,
        ];
    }
}
