<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\KlaviyoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('trackPlacedOrder sends a Placed Order event to the Klaviyo Events API', function () {
    Http::fake(['a.klaviyo.com/*' => Http::response([], 202)]);

    $user = User::factory()->create(['email' => 'client@example.com', 'name' => 'Client Test']);
    $variant = ProductVariant::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'total_cents' => 4599]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'product_name' => 'Sérum vitamine C',
        'quantity' => 2,
        'unit_price_cents' => 1500,
    ]);

    app(KlaviyoService::class)->trackPlacedOrder($order->fresh(['items', 'user']));

    Http::assertSent(function ($request) use ($order) {
        $attributes = $request->data()['data']['attributes'];

        return $request->url() === 'https://a.klaviyo.com/api/events/'
            && $request->hasHeader('Authorization', 'Klaviyo-API-Key '.config('services.klaviyo.private_key'))
            && $attributes['metric']['data']['attributes']['name'] === 'Placed Order'
            && $attributes['profile']['data']['attributes']['email'] === 'client@example.com'
            && $attributes['value'] === 45.99
            && $attributes['unique_id'] === "order-{$order->id}-placed";
    });
});

test('trackPlacedOrder does nothing when the order has no linked user', function () {
    Http::fake();

    $order = Order::factory()->create(['user_id' => null]);

    app(KlaviyoService::class)->trackPlacedOrder($order->fresh());

    Http::assertNothingSent();
});
