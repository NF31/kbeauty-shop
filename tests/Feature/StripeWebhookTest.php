<?php

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\WebhookEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\NewPaidOrderAlert;
use App\Notifications\OrderConfirmation;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Stripe\Exception\SignatureVerificationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function fakeWebhookEvent(string $type, ?string $paymentIntentId = 'pi_fake123'): WebhookEvent
{
    return new WebhookEvent(type: $type, paymentIntentId: $paymentIntentId);
}

test('a request without a Stripe-Signature header is rejected', function () {
    $this->postJson('/stripe/webhook', ['type' => 'payment_intent.succeeded'])
        ->assertStatus(400);
});

test('a request with an invalid signature is rejected', function () {
    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andThrow(
            SignatureVerificationException::factory('invalide', null, null)
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'bad_signature'])
        ->assertStatus(400);
});

test('an unhandled event type is acknowledged without side effects', function () {
    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('payment_intent.payment_failed', null)
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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
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

test('payment_intent.succeeded sends an order confirmation email to the order owner', function () {
    Notification::fake();

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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    Notification::assertSentTo($order->user, OrderConfirmation::class);
});

test('replaying the same succeeded event does not resend the confirmation email', function () {
    Notification::fake();

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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();
    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();

    Notification::assertSentToTimes($order->user, OrderConfirmation::class, 1);
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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();
    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();

    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(1);
});

test('payment_intent.succeeded notifies admins of the new paid order', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staff = User::factory()->create();
    $staff->assignRole('staff');

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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    Notification::assertSentTo($admin, NewPaidOrderAlert::class);
    Notification::assertNotSentTo($staff, NewPaidOrderAlert::class);
});

test('a succeeded PaymentIntent with no matching Payment is acknowledged without error', function () {
    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_unknown')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect(Payment::query()->count())->toBe(0);
});

test('payment_intent.succeeded generates and stores the order invoice', function () {
    Storage::fake('invoices');

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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    $invoice = Invoice::query()->where('order_id', $order->id)->first();

    expect($invoice)->not->toBeNull();
    expect($invoice->number)->toBe($order->fresh()->order_number);
    expect($invoice->total_cents)->toBe($order->fresh()->total_cents);
    Storage::disk('invoices')->assertExists($invoice->path);
});

test('replaying the same succeeded event does not generate a duplicate invoice', function () {
    Storage::fake('invoices');

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

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(
            fakeWebhookEvent('payment_intent.succeeded', 'pi_fake123')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();
    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();

    expect(Invoice::query()->where('order_id', $order->id)->count())->toBe(1);
});
