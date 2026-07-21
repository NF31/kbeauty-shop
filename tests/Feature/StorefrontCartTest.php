<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest can add a product variant to the cart and see it on /panier', function () {
    $variant = ProductVariant::factory()->create(['price_cents' => 1500, 'stock_quantity' => 5]);

    $response = $this->post('/panier', [
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $response->assertRedirect();

    $token = $response->getCookie(CartService::COOKIE_NAME)->getValue();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->get('/panier')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/cart')
            ->has('items', 1)
            ->where('items.0.quantity', 2)
            ->where('items.0.unitPriceCents', 1500)
            ->where('items.0.lineTotalCents', 3000)
            ->where('subtotalCents', 3000)
            ->where('totalCents', 3000)
            ->where('currency', 'EUR')
        );
});

test('adding the same variant twice cumulates the quantity instead of duplicating the line', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $first = $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $token = $first->getCookie(CartService::COOKIE_NAME)->getValue();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 2]);

    expect(CartItem::query()->count())->toBe(1);
    expect(CartItem::query()->sole()->quantity)->toBe(3);
});

test('adding more than the available stock is rejected and does not create a cart item', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 2]);

    $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 5])
        ->assertSessionHasErrors('quantity');

    expect(CartItem::query()->count())->toBe(0);
});

test('a guest can update the quantity of a cart item', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $response = $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $token = $response->getCookie(CartService::COOKIE_NAME)->getValue();
    $item = CartItem::query()->sole();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->patch("/panier/{$item->id}", ['quantity' => 4])
        ->assertRedirect();

    expect($item->refresh()->quantity)->toBe(4);
});

test('updating a cart item beyond the available stock is rejected', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 3]);

    $response = $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $token = $response->getCookie(CartService::COOKIE_NAME)->getValue();
    $item = CartItem::query()->sole();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->patch("/panier/{$item->id}", ['quantity' => 10])
        ->assertSessionHasErrors('quantity');

    expect($item->refresh()->quantity)->toBe(1);
});

test('a guest can remove a cart item', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $response = $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $token = $response->getCookie(CartService::COOKIE_NAME)->getValue();
    $item = CartItem::query()->sole();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->delete("/panier/{$item->id}")
        ->assertRedirect();

    expect(CartItem::query()->count())->toBe(0);
});

test('a visitor cannot modify another visitor cart item', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);
    $item = CartItem::query()->sole();

    // Second visitor, no shared cookie: gets a brand new empty cart of their own.
    $this->patch("/panier/{$item->id}", ['quantity' => 2])
        ->assertForbidden();

    expect($item->refresh()->quantity)->toBe(1);
});

test('a guest cart is merged into the account cart on login', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $response = $this->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 2]);
    $token = $response->getCookie(CartService::COOKIE_NAME)->getValue();

    $user = User::factory()->create();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticated();

    $userCart = Cart::query()->where('user_id', $user->id)->first();

    expect($userCart)->not->toBeNull();
    expect($userCart->items()->sole()->quantity)->toBe(2);
    expect(Cart::query()->whereNotNull('session_token')->count())->toBe(0);
});
