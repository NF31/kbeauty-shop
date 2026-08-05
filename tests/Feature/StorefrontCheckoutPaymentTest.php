<?php

use App\Domain\Payments\CheckoutSessionResult;
use App\Domain\Payments\CheckoutSessionStatusResult;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fakeCheckoutSession(string $sessionId = 'cs_fake123'): CheckoutSessionResult
{
    return new CheckoutSessionResult(
        id: $sessionId,
        clientSecret: $sessionId.'_secret',
    );
}

function fakeCheckoutSessionStatus(string $paymentStatus = 'unpaid', ?string $paymentIntentId = null, string $sessionId = 'cs_fake123'): CheckoutSessionStatusResult
{
    return new CheckoutSessionStatusResult(
        id: $sessionId,
        paymentStatus: $paymentStatus,
        paymentIntentId: $paymentIntentId,
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

test('paying creates a pending order, its items and a payment tied to a Stripe Checkout Session', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5, 'price_cents' => 2500]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(fakeCheckoutSession());
    });

    $this->actingAs($user)
        ->post('/commande/paiement')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'payment')
            ->where('clientSecret', 'cs_fake123_secret')
        );

    $order = Order::query()->sole();
    expect($order->status)->toBe(OrderStatus::Pending);
    expect($order->user_id)->toBe($user->id);
    expect($order->total_cents)->toBe(2500);
    expect($order->items()->count())->toBe(1);

    $payment = Payment::query()->sole();
    expect($payment->order_id)->toBe($order->id);
    // Tant que le paiement est `pending`, `provider_payment_id` stocke l'id
    // de la Checkout Session (le PaymentIntent n'existe pas encore côté
    // Stripe à ce stade, cf. incident du 2026-08-05).
    expect($payment->provider_payment_id)->toBe('cs_fake123');
    expect($payment->status)->toBe(PaymentStatus::Pending);
});

test('reloading the payment step with a GET request re-renders it instead of returning 405', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    // Une Checkout Session ne peut pas être mise à jour en place (contrairement
    // à l'ancien PaymentIntent) : chaque passage tant que le paiement n'a pas
    // réussi en crée une nouvelle.
    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->twice()->andReturn(fakeCheckoutSession());
        $mock->shouldReceive('retrieveCheckoutSession')->once()->andReturn(fakeCheckoutSessionStatus());
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
        $mock->shouldReceive('createCheckoutSession')->twice()->andReturn(fakeCheckoutSession('cs_first'));
        $mock->shouldReceive('retrieveCheckoutSession')->once()->andReturn(fakeCheckoutSessionStatus(sessionId: 'cs_first'));
    });

    $this->actingAs($user)->post('/commande/paiement');
    $this->actingAs($user)->post('/commande/paiement');

    expect(Order::query()->count())->toBe(1);
    expect(Payment::query()->count())->toBe(1);
});

test('visiting the confirmation page empties the cart once the Checkout Session has been paid', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(fakeCheckoutSession('cs_done'));
        $mock->shouldReceive('retrieveCheckoutSession')->once()->andReturn(fakeCheckoutSessionStatus('paid', 'pi_done', 'cs_done'));
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
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(fakeCheckoutSession('cs_done'));
        $mock->shouldReceive('retrieveCheckoutSession')->once()->andReturn(fakeCheckoutSessionStatus('paid', 'pi_done', 'cs_done'));
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

test('paying for a product with no english translation still snapshots a product name (2026-08-05 incident)', function () {
    // Reproduit l'incident prod : APP_FALLBACK_LOCALE absent des variables
    // d'environnement Laravel Cloud, donc Laravel retombait sur son defaut
    // "en" plutot que "fr" - un produit jamais traduit en anglais (nom
    // uniquement en fr, comportement par defaut de ProductFactory) faisait
    // planter le paiement (product_name NOT NULL) des qu'un client passait
    // commande depuis la version /en du site.
    config(['app.fallback_locale' => 'en']);

    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/en/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->actingAs($user)->post('/en/commande/adresse', [
        'shipping' => [
            'full_name' => 'Jane Doe',
            'line1' => '10 Downing Street',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country_code' => 'FR',
        ],
        'billing_same_as_shipping' => true,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(fakeCheckoutSession());
    });

    $this->actingAs($user)
        ->post('/en/commande/paiement')
        ->assertOk();

    $orderItem = Order::query()->sole()->items()->sole();
    expect($orderItem->product_name)->not->toBeNull();
    expect($orderItem->product_name)->toBe($variant->product->getTranslation('name', 'fr', false));
});

test('a customer can resume payment on their own pending order', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->twice()->andReturn(fakeCheckoutSession());
        $mock->shouldReceive('retrieveCheckoutSession')->once()->andReturn(fakeCheckoutSessionStatus());
    });

    $this->actingAs($user)->post('/commande/paiement');
    $order = Order::query()->sole();

    $this->actingAs($user)
        ->get("/commande/{$order->id}/reprendre")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'payment')
            ->where('clientSecret', 'cs_fake123_secret')
            ->where('order.orderNumber', $order->order_number)
        );

    expect(Order::query()->count())->toBe(1);
});

test('resuming payment on someone else\'s order is forbidden', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $owner = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(fakeCheckoutSession());
    });

    $this->actingAs($owner)->post('/commande/paiement');
    $order = Order::query()->sole();

    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get("/commande/{$order->id}/reprendre")
        ->assertForbidden();
});

test('resuming payment on a non-pending order redirects to the order detail page instead of touching Stripe', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Paid]);

    $this->actingAs($order->user)
        ->get("/commande/{$order->id}/reprendre")
        ->assertRedirect("/mon-compte/commandes/{$order->id}");
});

test('paying again after the Checkout Session already succeeded redirects to the confirmation page instead of erroring', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = reachPaymentStep($variant);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')->once()->andReturn(fakeCheckoutSession('cs_paid'));
        $mock->shouldReceive('retrieveCheckoutSession')->once()->andReturn(fakeCheckoutSessionStatus('paid', 'pi_paid', 'cs_paid'));
    });

    $this->actingAs($user)->post('/commande/paiement');

    $this->actingAs($user)
        ->post('/commande/paiement')
        ->assertRedirect('/commande/confirmation');
});
