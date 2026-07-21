<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a guest is redirected to login', function () {
    $this->get('/mon-compte/commandes')->assertRedirect('/login');
});

test('a user only sees their own orders, most recent first', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $olderOrder = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => 'KB-2026-00001',
        'placed_at' => now()->subDay(),
    ]);
    OrderItem::factory()->create([
        'order_id' => $olderOrder->id,
        'product_name' => 'Nettoyant moussant',
        'variant_label' => '150ml',
        'quantity' => 2,
    ]);

    $recentOrder = Order::factory()->create([
        'user_id' => $user->id,
        'order_number' => 'KB-2026-00002',
        'placed_at' => now(),
    ]);

    Order::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get('/mon-compte/commandes')
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/account-orders')
            ->has('orders.data', 2)
            ->where('orders.data.0.orderNumber', $recentOrder->order_number)
            ->where('orders.data.1.orderNumber', $olderOrder->order_number)
            ->where('orders.data.1.items.0.productName', 'Nettoyant moussant')
        );
});

test('the status label is localized in French', function () {
    $user = User::factory()->create();
    Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Shipped]);

    $this->actingAs($user)
        ->get('/mon-compte/commandes')
        ->assertInertia(fn (Assert $page) => $page
            ->where('orders.data.0.statusLabel', 'Expédiée')
        );
});

test('a user with no orders sees an empty state', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/mon-compte/commandes')
        ->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 0)
        );
});

test('a guest is redirected to login when viewing an order detail', function () {
    $order = Order::factory()->create();

    $this->get("/mon-compte/commandes/{$order->id}")->assertRedirect('/login');
});

test('a user can view their own order detail', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_name' => 'Sérum vitamine C',
        'variant_label' => '30ml',
    ]);

    $this->actingAs($user)
        ->get("/mon-compte/commandes/{$order->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/account-order')
            ->where('order.orderNumber', $order->order_number)
            ->where('order.items.0.productName', 'Sérum vitamine C')
            ->where('order.shippingAddress.fullName', $order->shippingAddress->full_name)
        );
});

test('a user cannot view another user\'s order detail', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($intruder)
        ->get("/mon-compte/commandes/{$order->id}")
        ->assertForbidden();
});
