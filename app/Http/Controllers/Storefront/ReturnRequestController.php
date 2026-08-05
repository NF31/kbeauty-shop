<?php

namespace App\Http\Controllers\Storefront;

use App\Application\Returns\UseCases\SubmitReturnRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SubmitReturnRequestRequest;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReturnRequestController extends Controller
{
    public function create(Request $request, Order $order): Response|RedirectResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        if (! $order->isEligibleForReturnRequest()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cette commande n\'est pas éligible à une demande de retour.']);

            return to_route($this->localizedRoute('storefront.account.orders.show'), $order);
        }

        $order->loadMissing('items');

        return Inertia::render('storefront/return-request-create', [
            'order' => [
                'id' => $order->id,
                'orderNumber' => $order->order_number,
                'currency' => $order->currency,
            ],
            'items' => $order->items
                ->reject(fn (OrderItem $item) => $item->is_gift)
                ->values()
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'productName' => $item->product_name,
                    'variantLabel' => $item->variant_label,
                    'quantity' => $item->quantity,
                    'unitPriceCents' => $item->unit_price_cents,
                ]),
        ]);
    }

    public function store(SubmitReturnRequestRequest $request, Order $order, SubmitReturnRequest $submitReturnRequest): RedirectResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->loadMissing('items');

        /** @var list<array{order_item_id: int|string, quantity: int|string}> $itemsInput */
        $itemsInput = $request->validated('items');

        $items = array_values(collect($itemsInput)
            ->map(function (array $item) use ($order) {
                $orderItem = $order->items->first(fn (OrderItem $orderItem) => $orderItem->id === (int) $item['order_item_id']);
                $quantity = (int) $item['quantity'];

                return [
                    'order_item_id' => $orderItem->id,
                    'quantity' => $quantity,
                    'amount_cents' => $orderItem->unit_price_cents * $quantity,
                ];
            })
            ->all());

        $submitReturnRequest($order, $request->validated('reason'), $items);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Votre demande de retour a bien été envoyée.']);

        return to_route($this->localizedRoute('storefront.account.orders.show'), $order);
    }

    /**
     * Même logique que CheckoutController::localizedRoute() (25.1) : les
     * routes EN partagent le même nom que leur équivalent FR, préfixé `en.`.
     */
    private function localizedRoute(string $name): string
    {
        return app()->getLocale() === 'en' ? "en.{$name}" : $name;
    }
}
