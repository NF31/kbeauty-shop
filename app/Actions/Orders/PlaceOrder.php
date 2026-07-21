<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Crée (ou met à jour) la commande `pending` correspondant à un panier, en
 * snapshotant nom produit / libellé variante / prix — ces valeurs ne doivent
 * plus jamais changer même si le produit est modifié ou supprimé du
 * catalogue par la suite (docs/DATA_MODEL.md).
 */
class PlaceOrder
{
    public function __invoke(Cart $cart, Address $shippingAddress, Address $billingAddress, ?Order $existingOrder = null): Order
    {
        return DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $existingOrder) {
            $subtotalCents = $cart->subtotalCents();
            $totalCents = $cart->totalCents();

            if ($existingOrder && $existingOrder->status === OrderStatus::Pending) {
                $existingOrder->update([
                    'shipping_address_id' => $shippingAddress->id,
                    'billing_address_id' => $billingAddress->id,
                    'subtotal_cents' => $subtotalCents,
                    'total_cents' => $totalCents,
                ]);
                $existingOrder->items()->delete();
                $order = $existingOrder;
            } else {
                $order = Order::query()->create([
                    'user_id' => $cart->user_id,
                    'order_number' => 'PENDING',
                    'status' => OrderStatus::Pending,
                    'shipping_address_id' => $shippingAddress->id,
                    'billing_address_id' => $billingAddress->id,
                    'subtotal_cents' => $subtotalCents,
                    'discount_cents' => 0,
                    // Frais de port réels calculés en Phase 4 (Sendcloud, 11.1) — pas encore branchés.
                    'shipping_cents' => 0,
                    'tax_cents' => 0,
                    'total_cents' => $totalCents,
                    'currency' => $cart->currency,
                    'placed_at' => now(),
                ]);

                $order->update(['order_number' => sprintf('KB-%d-%05d', now()->year, $order->id)]);
            }

            $cart->loadMissing(['items.variant.product', 'items.variant.optionValues']);

            foreach ($cart->items as $item) {
                $variantLabel = $item->variant->optionValues->pluck('value')->implode(' / ');

                $order->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->variant->product->name,
                    'variant_label' => $variantLabel !== '' ? $variantLabel : $item->variant->sku,
                    'unit_price_cents' => $item->unit_price_cents,
                    'quantity' => $item->quantity,
                    'total_cents' => $item->lineTotalCents($cart->currency),
                    'is_gift' => false,
                ]);
            }

            $order->load('items');

            return $order;
        });
    }
}
