<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration is rate limited', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->post(route('register.store'), []);
    }

    $this->post(route('register.store'), [])->assertTooManyRequests();
});

test('password reset link requests are rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), ['email' => 'nobody@example.com']);
    }

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertTooManyRequests();
});

test('adding items to the cart is rate limited', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 100]);

    for ($i = 0; $i < 30; $i++) {
        $this->post(route('storefront.cart.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
    }

    $this->post(route('storefront.cart.store'), [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ])->assertTooManyRequests();
});

test('checkout has its own rate limit bucket separate from the cart', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 100]);
    $user = User::factory()->create();

    // Exhaust the cart bucket first.
    for ($i = 0; $i < 30; $i++) {
        $this->actingAs($user)->post(route('storefront.cart.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
    }

    // The checkout bucket must still accept requests despite the cart bucket being exhausted.
    $this->actingAs($user)->post(route('storefront.checkout.store-address'), [])
        ->assertStatus(302);
});

test('creating addresses in the account is rate limited', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 30; $i++) {
        $this->actingAs($user)->post(route('storefront.account.addresses.store'), []);
    }

    $this->actingAs($user)->post(route('storefront.account.addresses.store'), [])
        ->assertTooManyRequests();
});

test('admin refunds are rate limited', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $order = Order::factory()->create();

    for ($i = 0; $i < 10; $i++) {
        $this->actingAs($admin)->post(route('admin.orders.refund', $order), []);
    }

    $this->actingAs($admin)->post(route('admin.orders.refund', $order), [])
        ->assertTooManyRequests();
});

test('admin product image uploads are rate limited', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $product = Product::factory()->create();

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($admin)->post(route('admin.products.images.store', $product), []);
    }

    $this->actingAs($admin)->post(route('admin.products.images.store', $product), [])
        ->assertTooManyRequests();
});
