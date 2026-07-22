<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows the user\'s order and address counts and their 3 most recent orders', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Address::factory()->count(2)->create(['user_id' => $user->id]);

    Order::factory()->create(['user_id' => $user->id, 'placed_at' => now()->subDays(3)]);
    $thirdMostRecentOrder = Order::factory()->create(['user_id' => $user->id, 'placed_at' => now()->subDays(2)]);
    Order::factory()->create(['user_id' => $user->id, 'placed_at' => now()->subDay()]);
    $mostRecentOrder = Order::factory()->create(['user_id' => $user->id, 'placed_at' => now()]);
    OrderItem::factory()->create([
        'order_id' => $mostRecentOrder->id,
        'product_name' => 'Sérum vitamine C',
        'variant_label' => '30ml',
    ]);

    Order::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/dashboard')
            ->where('ordersCount', 4)
            ->where('addressesCount', 2)
            ->has('recentOrders', 3)
            ->where('recentOrders.0.orderNumber', $mostRecentOrder->order_number)
            ->where('recentOrders.0.items.0.productName', 'Sérum vitamine C')
            ->where('recentOrders.2.orderNumber', $thirdMostRecentOrder->order_number)
        );
});

test('a user with no orders sees an empty recent orders list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('ordersCount', 0)
            ->has('recentOrders', 0)
        );
});
