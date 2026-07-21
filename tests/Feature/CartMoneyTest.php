<?php

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('cart item line total multiplies unit price by quantity without touching floats', function () {
    $item = CartItem::factory()->make(['unit_price_cents' => 1999, 'quantity' => 3]);

    expect($item->lineTotalCents('EUR'))->toBe(5997);
});

test('cart subtotal and total sum every line and match while no discount exists yet', function () {
    $cart = Cart::factory()->create(['currency' => 'EUR']);
    CartItem::factory()->for($cart)->create(['unit_price_cents' => 1500, 'quantity' => 2]);
    CartItem::factory()->for($cart)->create(['unit_price_cents' => 799, 'quantity' => 1]);

    $cart->refresh()->load('items');

    expect($cart->subtotalCents())->toBe(3799);
    expect($cart->totalCents())->toBe($cart->subtotalCents());
});

test('an empty cart has a zero subtotal and total', function () {
    $cart = Cart::factory()->create(['currency' => 'EUR']);

    expect($cart->subtotalCents())->toBe(0);
    expect($cart->totalCents())->toBe(0);
});
