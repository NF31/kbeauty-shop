<?php

use App\Application\Cart\UseCases\SendAbandonedCartReminders;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('a cart inactive for more than 2 hours gets a reminder email', function () {
    Notification::fake();

    $cart = Cart::factory()->forUser()->create();
    CartItem::factory()->for($cart)->create();
    $cart->items()->update(['updated_at' => now()->subHours(3)]);

    $sent = (new SendAbandonedCartReminders)();

    expect($sent)->toBe(1);
    Notification::assertSentTo($cart->fresh()->user, AbandonedCartReminder::class);
    expect($cart->fresh()->abandoned_cart_reminder_sent_at)->not->toBeNull();
});

test('a cart still active within the last 2 hours is not reminded', function () {
    Notification::fake();

    $cart = Cart::factory()->forUser()->create();
    CartItem::factory()->for($cart)->create();

    $sent = (new SendAbandonedCartReminders)();

    expect($sent)->toBe(0);
    Notification::assertNothingSent();
});

test('a guest cart with no account is never reminded', function () {
    Notification::fake();

    $cart = Cart::factory()->create(['user_id' => null]);
    CartItem::factory()->for($cart)->create();
    $cart->items()->update(['updated_at' => now()->subHours(3)]);

    $sent = (new SendAbandonedCartReminders)();

    expect($sent)->toBe(0);
    Notification::assertNothingSent();
});

test('an empty cart is never reminded', function () {
    Notification::fake();

    Cart::factory()->forUser()->create();

    $sent = (new SendAbandonedCartReminders)();

    expect($sent)->toBe(0);
    Notification::assertNothingSent();
});

test('a cart already reminded is not reminded again until it is touched again', function () {
    Notification::fake();

    $cart = Cart::factory()->forUser()->create();
    CartItem::factory()->for($cart)->create();
    $cart->items()->update(['updated_at' => now()->subHours(3)]);
    $cart->update(['abandoned_cart_reminder_sent_at' => now()->subHours(1)]);

    $sent = (new SendAbandonedCartReminders)();

    expect($sent)->toBe(0);
    Notification::assertNothingSent();
});

test('a cart touched again after a reminder is reminded a second time once inactive again', function () {
    Notification::fake();

    $cart = Cart::factory()->forUser()->create([
        'abandoned_cart_reminder_sent_at' => now()->subHours(5),
    ]);
    CartItem::factory()->for($cart)->create();
    $cart->items()->update(['updated_at' => now()->subHours(3)]);

    $sent = (new SendAbandonedCartReminders)();

    expect($sent)->toBe(1);
    Notification::assertSentTo($cart->fresh()->user, AbandonedCartReminder::class);
});

test('the artisan command reports how many reminders were sent', function () {
    Notification::fake();

    $variant = ProductVariant::factory()->create();
    $cart = Cart::factory()->forUser()->create();
    CartItem::factory()->for($cart)->create(['product_variant_id' => $variant->id]);
    $cart->items()->update(['updated_at' => now()->subHours(3)]);

    $this->artisan('cart:send-abandoned-reminders')
        ->expectsOutputToContain('1 email(s) de relance panier envoyé(s).')
        ->assertSuccessful();
});
