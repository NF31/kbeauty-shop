<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

uses(RefreshDatabase::class);

function fakeStripeEvent(string $type, array $paymentIntentOverrides = []): Event
{
    return Event::constructFrom([
        'id' => 'evt_'.uniqid(),
        'type' => $type,
        'data' => [
            'object' => array_merge([
                'id' => 'pi_fake123',
                'object' => 'payment_intent',
                'status' => 'succeeded',
            ], $paymentIntentOverrides),
        ],
    ]);
}

test('a request without a Stripe-Signature header is rejected', function () {
    $this->postJson('/stripe/webhook', ['type' => 'payment_intent.succeeded'])
        ->assertStatus(400);
});

test('a request with an invalid signature is rejected', function () {
    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('constructWebhookEvent')->once()->andThrow(
            SignatureVerificationException::factory('invalide', null, null)
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'bad_signature'])
        ->assertStatus(400);
});

test('an unhandled event type is acknowledged without side effects', function () {
    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn(
            fakeStripeEvent('payment_intent.payment_failed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();
});

test('payment_intent.succeeded marks the order/payment as paid and decrements stock', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'pi_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn(
            fakeStripeEvent('payment_intent.succeeded', ['id' => 'pi_fake123'])
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(1);
});

test('replaying the same succeeded event does not decrement stock twice', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'pi_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('constructWebhookEvent')->twice()->andReturn(
            fakeStripeEvent('payment_intent.succeeded', ['id' => 'pi_fake123'])
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();
    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();

    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(1);
});

test('a succeeded PaymentIntent with no matching Payment is acknowledged without error', function () {
    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn(
            fakeStripeEvent('payment_intent.succeeded', ['id' => 'pi_unknown'])
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect(Payment::query()->count())->toBe(0);
});
