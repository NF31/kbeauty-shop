<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\OrderRepositoryInterface;
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
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function __invoke(Cart $cart, Address $shippingAddress, Address $billingAddress, ?Order $existingOrder = null): Order
    {
        return DB::transaction(function () use ($cart, $shippingAddress, $billingAddress, $existingOrder) {
            $subtotalCents = $cart->subtotalCents();
            $totalCents = $cart->totalCents();

            if ($existingOrder && $existingOrder->status === OrderStatus::Pending) {
                $order = $this->orders->updatePending($existingOrder, [
                    'shipping_address_id' => $shippingAddress->id,
                    'billing_address_id' => $billingAddress->id,
                    'subtotal_cents' => $subtotalCents,
                    'total_cents' => $totalCents,
                ]);
            } else {
                $order = $this->orders->createPending([
                    'user_id' => $cart->user_id,
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
            }

            $cart->loadMissing(['items.variant.product.primaryImage', 'items.variant.optionValues']);

            $itemRows = [];

            foreach ($cart->items as $item) {
                $variantLabel = $item->variant->optionValues->pluck('value')->implode(' / ');

                $itemRows[] = [
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->variant->product->name,
                    'variant_label' => $variantLabel !== '' ? $variantLabel : $item->variant->sku,
                    'product_image_path' => $item->variant->product->primaryImage?->path,
                    'unit_price_cents' => $item->unit_price_cents,
                    'quantity' => $item->quantity,
                    'total_cents' => $item->lineTotalCents($cart->currency),
                    'is_gift' => false,
                ];
            }

            $this->orders->replaceItems($order, $itemRows);

            $order->load('items');

            return $order;
        });
    }
}
