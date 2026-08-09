<?php

use App\Application\Orders\UseCases\ConfirmOrderPayment;
use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\WebhookEvent;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\ProcessStripeWebhookJob;
use App\Jobs\SendPlacedOrderEventToKlaviyo;
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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\WebhookClient\Models\WebhookCall;
use Stripe\Exception\SignatureVerificationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function fakeWebhookEvent(
    string $type,
    ?string $sessionId = 'cs_fake123',
    ?string $paymentIntentId = 'pi_fake123',
    ?string $paymentStatus = 'paid',
): WebhookEvent {
    return new WebhookEvent(
        type: $type,
        sessionId: $sessionId,
        paymentIntentId: $paymentIntentId,
        paymentStatus: $paymentStatus,
    );
}

test('a request without a Stripe-Signature header is rejected', function () {
    $this->postJson('/stripe/webhook', ['type' => 'checkout.session.completed'])
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
            fakeWebhookEvent('payment_intent.payment_failed', null, null, null)
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();
});

test('a checkout.session.completed event with payment_status unpaid is acknowledged without side effects', function () {
    // Cas d'un moyen de paiement à confirmation différée (ex. virement SEPA) :
    // le premier événement arrive avant que le paiement soit réellement
    // confirmé — seul `payment_status === 'paid'` doit déclencher la
    // confirmation (cf. checkout.session.async_payment_succeeded).
    Payment::factory()->create(['provider_payment_id' => 'cs_fake123', 'status' => PaymentStatus::Pending]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed', 'cs_fake123', null, 'unpaid')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect(Payment::query()->sole()->status)->toBe(PaymentStatus::Pending);
});

test('checkout.session.completed marks the order/payment as paid, swaps provider_payment_id to the PaymentIntent id and decrements stock', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);
    expect($payment->fresh()->provider_payment_id)->toBe('pi_fake123');
    expect($payment->fresh()->paid_at)->not->toBeNull();
    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(1);
});

test('checkout.session.async_payment_succeeded also confirms the payment (delayed payment methods)', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.async_payment_succeeded')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('checkout.session.completed sends an order confirmation email to the order owner', function () {
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
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
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
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
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
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();
    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();

    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(1);
});

test('checkout.session.completed notifies admins of the new paid order', function () {
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
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    Notification::assertSentTo($admin, NewPaidOrderAlert::class);
    Notification::assertNotSentTo($staff, NewPaidOrderAlert::class);
});

test('checkout.session.completed dispatches the Klaviyo Placed Order event job', function () {
    // Fake sélectif : ProcessStripeWebhookJob doit tourner pour de vrai (sync en
    // test) pour atteindre le dispatch de SendPlacedOrderEventToKlaviyo qu'il
    // contient — un Bus::fake() global l'aurait intercepté avant.
    Bus::fake([SendPlacedOrderEventToKlaviyo::class]);

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    Bus::assertDispatched(SendPlacedOrderEventToKlaviyo::class, fn ($job) => $job->order->is($order));
});

test('a late/duplicate succeeded event on an already refunded payment does not revert the order to paid', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Refunded]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'pi_fake123',
        'status' => PaymentStatus::Refunded,
    ]);

    // Une fois `Succeeded`/`Refunded`, `provider_payment_id` est déjà le
    // PaymentIntent (plus la session) — un événement en retard référence
    // toujours la session d'origine, donc `findByProviderPaymentId` ne
    // retrouve plus rien : c'est cette absence de match qui protège ici.
    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
    expect($variant->fresh()->stock_quantity)->toBe(10);
});

test('a succeeded Checkout Session with no matching Payment is acknowledged without error', function () {
    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed', 'cs_unknown')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])
        ->assertOk();

    expect(Payment::query()->count())->toBe(0);
});

test('checkout.session.completed generates and stores the order invoice', function () {
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
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
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
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $this->mock(PaymentGatewayInterface::class, function ($mock) {
        $mock->shouldReceive('verifyWebhookSignature')->twice()->andReturn(
            fakeWebhookEvent('checkout.session.completed')
        );
    });

    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();
    $this->postJson('/stripe/webhook', [], ['Stripe-Signature' => 'sig'])->assertOk();

    expect(Invoice::query()->where('order_id', $order->id)->count())->toBe(1);
});

test('a Stripe event replayed with the same event id is not reprocessed even if the payment was reset to pending', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider_payment_id' => 'cs_fake123',
        'status' => PaymentStatus::Pending,
    ]);

    $event = fakeWebhookEvent('checkout.session.completed');
    $payload = ['id' => 'evt_dedup_test', 'type' => 'checkout.session.completed'];

    $firstCall = WebhookCall::create(['name' => 'stripe', 'url' => 'https://example.test/stripe/webhook', 'headers' => [], 'payload' => $payload]);
    (new ProcessStripeWebhookJob($firstCall, $event))->handle(app(ConfirmOrderPayment::class));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Succeeded);

    // Remet le paiement à Pending pour isoler le dédup par event.id de la
    // protection "métier" habituelle (Payment déjà Succeeded/Refunded, testée
    // plus haut) : sans le dédup, ce deuxième appel repasserait la commande
    // à paid et re-décrémenterait le stock. `refresh()` d'abord : ConfirmOrderPayment
    // (dans le premier handle()) a modifié la ligne via sa propre instance
    // (repository), donc $payment ici est encore figé sur son état de création
    // (Pending) - sans refresh(), Eloquent ne verrait aucun changement à écrire.
    $payment->refresh()->update(['status' => PaymentStatus::Pending]);

    $secondCall = WebhookCall::create(['name' => 'stripe', 'url' => 'https://example.test/stripe/webhook', 'headers' => [], 'payload' => $payload]);
    (new ProcessStripeWebhookJob($secondCall, $event))->handle(app(ConfirmOrderPayment::class));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
    expect($variant->fresh()->stock_quantity)->toBe(7);
});

test('an exception during processing is recorded on the webhook call and rethrown for the queue to retry', function () {
    $this->mock(PaymentRepositoryInterface::class, function ($mock) {
        $mock->shouldReceive('findByProviderPaymentId')->andThrow(new Exception('DB indisponible'));
    });

    $event = fakeWebhookEvent('checkout.session.completed');
    $webhookCall = WebhookCall::create([
        'name' => 'stripe',
        'url' => 'https://example.test/stripe/webhook',
        'headers' => [],
        'payload' => ['id' => 'evt_failure_test', 'type' => 'checkout.session.completed'],
    ]);

    expect(fn () => (new ProcessStripeWebhookJob($webhookCall, $event))->handle(app(ConfirmOrderPayment::class)))
        ->toThrow(Exception::class, 'DB indisponible');

    expect($webhookCall->fresh()->exception['message'])->toBe('DB indisponible');
});
