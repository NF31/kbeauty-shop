<?php

use App\Enums\OrderStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\ReturnRequestStatusUpdated;
use App\Notifications\ReturnRequestSubmitted;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

function deliveredOrder(?User $user = null): Order
{
    $order = Order::factory()->create([
        'user_id' => ($user ?? User::factory()->create())->id,
        'status' => OrderStatus::Delivered,
    ]);
    $order->touch();

    return $order;
}

test('a guest is redirected to login', function () {
    $order = deliveredOrder();

    test()->get("/mon-compte/commandes/{$order->id}/retour")->assertRedirect('/login');
});

test('a customer cannot request a return on someone else\'s order', function () {
    $owner = User::factory()->create();
    $order = deliveredOrder($owner);
    $stranger = User::factory()->create();

    test()->actingAs($stranger)
        ->get("/mon-compte/commandes/{$order->id}/retour")
        ->assertForbidden();
});

test('a pending order is not eligible for a return request', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Pending]);

    test()->actingAs($user)
        ->get("/mon-compte/commandes/{$order->id}/retour")
        ->assertRedirect("/mon-compte/commandes/{$order->id}");
});

test('a delivered order past the 14 day window is not eligible', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);
    $order->timestamps = false;
    $order->forceFill(['updated_at' => now()->subDays(20)])->save();

    test()->actingAs($user)
        ->get("/mon-compte/commandes/{$order->id}/retour")
        ->assertRedirect("/mon-compte/commandes/{$order->id}");
});

test('submitting a return request creates it with its items and notifies the customer and admins', function () {
    Notification::fake();

    $user = User::factory()->create();
    $order = deliveredOrder($user);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2, 'unit_price_cents' => 1000]);

    test()->actingAs($user)
        ->post("/mon-compte/commandes/{$order->id}/retour", [
            'reason' => 'Produit endommagé à la livraison.',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ])
        ->assertRedirect("/mon-compte/commandes/{$order->id}");

    $returnRequest = ReturnRequest::query()->sole();
    expect($returnRequest->order_id)->toBe($order->id);
    expect($returnRequest->user_id)->toBe($user->id);
    expect($returnRequest->status)->toBe(ReturnRequestStatus::Submitted);
    expect($returnRequest->items()->sole()->quantity)->toBe(1);
    expect($returnRequest->items()->sole()->amount_cents)->toBe(1000);

    Notification::assertSentTo($user, ReturnRequestStatusUpdated::class);
    Notification::assertSentTo($this->admin, ReturnRequestSubmitted::class);
});

test('a gift item cannot be included in a return request', function () {
    $user = User::factory()->create();
    $order = deliveredOrder($user);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'is_gift' => true]);

    test()->actingAs($user)
        ->post("/mon-compte/commandes/{$order->id}/retour", [
            'reason' => 'Motif',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ])
        ->assertSessionHasErrors('items.0.order_item_id');
});

test('a return quantity greater than what was ordered is rejected', function () {
    $user = User::factory()->create();
    $order = deliveredOrder($user);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    test()->actingAs($user)
        ->post("/mon-compte/commandes/{$order->id}/retour", [
            'reason' => 'Motif',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 5],
            ],
        ])
        ->assertSessionHasErrors('items.0.quantity');
});

test('a second return request cannot be submitted while one is already in progress', function () {
    $user = User::factory()->create();
    $order = deliveredOrder($user);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    ReturnRequest::factory()->create(['order_id' => $order->id, 'user_id' => $user->id, 'status' => ReturnRequestStatus::Submitted]);

    test()->actingAs($user)
        ->post("/mon-compte/commandes/{$order->id}/retour", [
            'reason' => 'Motif',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 1],
            ],
        ])
        ->assertSessionHasErrors('order');

    expect(ReturnRequest::query()->count())->toBe(1);
});
