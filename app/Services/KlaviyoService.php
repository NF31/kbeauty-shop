<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps Klaviyo's Events API (docs/FEATURES.md 15.2) — used to fire the
 * "Placed Order" event that later triggers the Klaviyo Reviews request flow
 * (15.3, configured in Klaviyo itself, no code on our side).
 */
class KlaviyoService
{
    private const API_REVISION = '2025-10-15';

    public function trackPlacedOrder(Order $order): void
    {
        $user = $order->user;

        if (! $user) {
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Klaviyo-API-Key '.config('services.klaviyo.private_key'),
            'revision' => self::API_REVISION,
        ])->post('https://a.klaviyo.com/api/events/', [
            'data' => [
                'type' => 'event',
                'attributes' => [
                    'properties' => [
                        'OrderNumber' => $order->order_number,
                        'Items' => $order->items->map(fn ($item) => [
                            'ProductName' => $item->product_name,
                            'VariantLabel' => $item->variant_label,
                            'Quantity' => $item->quantity,
                            'ItemPrice' => $item->unit_price_cents / 100,
                        ])->all(),
                    ],
                    'metric' => [
                        'data' => [
                            'type' => 'metric',
                            'attributes' => ['name' => 'Placed Order'],
                        ],
                    ],
                    'profile' => [
                        'data' => [
                            'type' => 'profile',
                            'attributes' => [
                                'email' => $user->email,
                                'first_name' => $user->name,
                            ],
                        ],
                    ],
                    'value' => $order->total_cents / 100,
                    'unique_id' => "order-{$order->id}-placed",
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::warning('Klaviyo : échec de l\'envoi de l\'événement Placed Order.', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
