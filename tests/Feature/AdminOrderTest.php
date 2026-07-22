<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\StripeService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Refund as StripeRefund;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function mockStripeRefund(string $status = 'succeeded'): void
{
    test()->mock(StripeService::class, function ($mock) use ($status) {
        $mock->shouldReceive('refundPayment')->once()->andReturn(
            StripeRefund::constructFrom(['id' => 're_fake123', 'status' => $status])
        );
    });
}

test('a guest is redirected to login', function () {
    $order = Order::factory()->create();

    $this->get("/admin/orders/{$order->id}")->assertRedirect('/login');
});

test('support can list and view orders but cannot refund', function () {
    $support = User::factory()->create();
    $support->assignRole('support');

    $order = Order::factory()->create();

    $this->actingAs($support)->get('/admin/orders')->assertOk();
    $this->actingAs($support)->get("/admin/orders/{$order->id}")->assertOk();
    $this->actingAs($support)->post("/admin/orders/{$order->id}/refund", ['amount_cents' => 100])
        ->assertForbidden();
});

test('admin sees the sum of successful refunds on the order list', function () {
    $order = Order::factory()->create(['total_cents' => 5000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Succeeded]);
    Refund::factory()->create(['order_id' => $order->id, 'payment_id' => $payment->id, 'amount_cents' => 1000]);

    $this->actingAs($this->admin)->get('/admin/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/orders/index')
            ->where('orders.data.0.refundedCents', 1000)
        );
});

test('admin can update an order status', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->actingAs($this->admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => OrderStatus::Processing->value])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('the refunded status cannot be set manually', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->actingAs($this->admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => OrderStatus::Refunded->value])
        ->assertSessionHasErrors('status');
});

test('a partial refund does not change the order or payment status', function () {
    mockStripeRefund();

    $order = Order::factory()->create(['status' => OrderStatus::Paid, 'total_cents' => 5000]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Succeeded,
        'amount_cents' => 5000,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", ['amount_cents' => 1000, 'reason' => 'Article manquant'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
    expect(Refund::query()->where('order_id', $order->id)->sum('amount_cents'))->toBe(1000);
});

test('refunding the full remaining amount marks the order and payment as refunded', function () {
    mockStripeRefund();

    $order = Order::factory()->create(['status' => OrderStatus::Paid, 'total_cents' => 5000]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Succeeded,
        'amount_cents' => 5000,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", ['amount_cents' => 5000])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Refunded);
});

test('a refund amount greater than what remains refundable is rejected', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid, 'total_cents' => 5000]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Succeeded,
        'amount_cents' => 5000,
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", ['amount_cents' => 6000])
        ->assertSessionHasErrors('amount_cents');

    expect(Refund::query()->count())->toBe(0);
});

test('an order with no successful payment does not expose a refundable amount', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total_cents' => 5000]);

    $this->actingAs($this->admin)->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/orders/show')
            ->where('order.hasSucceededPayment', false)
            ->where('order.refundableCents', 0)
        );
});

test('an order with no successful payment cannot be refunded', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending, 'total_cents' => 5000]);

    $this->actingAs($this->admin)
        ->post("/admin/orders/{$order->id}/refund", ['amount_cents' => 1000])
        ->assertRedirect("/admin/orders/{$order->id}");

    expect(Refund::query()->count())->toBe(0);
});
