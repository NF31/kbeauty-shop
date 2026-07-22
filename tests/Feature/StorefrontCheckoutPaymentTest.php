<?php

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\PaymentIntentResult;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fakePaymentIntent(string $id = 'pi_fake123', string $status = 'requires_payment_method'): PaymentIntentResult
{
    return new PaymentIntentResult(
        id: $id,
        clientSecret: $id.'_secret',
        status: $status,
    );
}

function reachPaymentStep(ProductVariant $variant): User
{
    $user = User::factory()->create();

    test()->actingAs($user)
        ->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    test()->actingAs($user)
        ->post('/commande/adresse', [
            'shipping' => [
                'full_name' => 'Jeanne Dupont',
                'line1' => '12 rue des Lilas',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country_code' => 'FR',
            ],
            'billing_same_as_shipping' => true,
        ]);

    return $user;
}

test('a user is redirected to /commande if addresses are not set before paying', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = User::factory()->create();

    $this->actingAs($user)->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post('/commande/paiement')
        ->assertRedirect('/commande');

    expect(Order::query()->count())->toBe(0);
});

test('paying creates a pending order, its items and a payment tied to a Stripe PaymentIntent', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5, 'price_cents' => 2500]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')->once()->andReturn(fakePaymentIntent());
    });

    $this->actingAs($user)
        ->post('/commande/paiement')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'payment')
            ->where('clientSecret', 'pi_fake123_secret')
        );

    $order = Order::query()->sole();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->user_id)->toBe($user->id);
    expect($order->total_cents)->toBe(2500);
    expect($order->items()->count())->toBe(1);

    $payment = Payment::query()->sole();
    expect($payment->order_id)->toBe($order->id);
    expect($payment->provider_payment_id)->toBe('pi_fake123');
    expect($payment->status)->toBe(PaymentStatus::Pending);
});

test('reloading the payment step with a GET request re-renders it instead of returning 405', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')->once()->andReturn(fakePaymentIntent());
        $mock->shouldReceive('retrievePaymentIntent')->once()->andReturn(fakePaymentIntent());
        $mock->shouldReceive('updatePaymentIntentAmount')->once()->andReturn(fakePaymentIntent());
    });

    $this->actingAs($user)->post('/commande/paiement');

    $this->actingAs($user)
        ->get('/commande/paiement')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'payment')
        );

    expect(Order::query()->count())->toBe(1);
});

test('paying twice reuses the same pending order instead of creating a duplicate', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')->once()->andReturn(fakePaymentIntent('pi_first'));
        $mock->shouldReceive('retrievePaymentIntent')->once()->andReturn(fakePaymentIntent('pi_first'));
        $mock->shouldReceive('updatePaymentIntentAmount')->once()->andReturn(fakePaymentIntent('pi_first'));
    });

    $this->actingAs($user)->post('/commande/paiement');
    $this->actingAs($user)->post('/commande/paiement');

    expect(Order::query()->count())->toBe(1);
    expect(Payment::query()->count())->toBe(1);
});

test('visiting the confirmation page empties the cart once the PaymentIntent has succeeded', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')->once()->andReturn(fakePaymentIntent('pi_done'));
        $mock->shouldReceive('retrievePaymentIntent')->once()->andReturn(fakePaymentIntent('pi_done', 'succeeded'));
    });

    $this->actingAs($user)->post('/commande/paiement');

    expect(Cart::query()->where('user_id', $user->id)->sole()->items()->count())->toBe(1);

    $this->actingAs($user)
        ->get('/commande/confirmation')
        ->assertOk();

    expect(Cart::query()->where('user_id', $user->id)->sole()->items()->count())->toBe(0);
});

test('a new cart started after a completed order does not reuse the old paid order', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')->once()->andReturn(fakePaymentIntent('pi_done'));
        $mock->shouldReceive('retrievePaymentIntent')->once()->andReturn(fakePaymentIntent('pi_done', 'succeeded'));
    });

    $this->actingAs($user)->post('/commande/paiement');
    $this->actingAs($user)->get('/commande/confirmation');

    expect(Order::query()->count())->toBe(1);

    $newVariant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $this->actingAs($user)
        ->post('/panier', ['product_variant_id' => $newVariant->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->get('/commande')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'address')
        );

    expect(Order::query()->count())->toBe(1);
});

test('paying again after the PaymentIntent already succeeded redirects to the confirmation page instead of erroring', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createPaymentIntent')->once()->andReturn(fakePaymentIntent('pi_paid'));
        $mock->shouldReceive('retrievePaymentIntent')->once()->andReturn(fakePaymentIntent('pi_paid', 'succeeded'));
        $mock->shouldNotReceive('updatePaymentIntentAmount');
    });

    $this->actingAs($user)->post('/commande/paiement');

    $this->actingAs($user)
        ->post('/commande/paiement')
        ->assertRedirect('/commande/confirmation');
});
