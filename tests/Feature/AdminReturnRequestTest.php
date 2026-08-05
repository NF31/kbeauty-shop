<?php

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Domain\Payments\RefundResult;
use App\Enums\PaymentStatus;
use App\Enums\ReturnRequestStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ReturnRequest;
use App\Models\User;
use App\Notifications\RefundConfirmation;
use App\Notifications\ReturnRequestStatusUpdated;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->support = User::factory()->create();
    $this->support->assignRole('support');
});

function mockReturnRefund(string $status = 'succeeded'): void
{
    test()->mock(PaymentGatewayInterface::class, function ($mock) use ($status) {
        $mock->shouldReceive('refund')->once()->andReturn(
            new RefundResult(id: 're_return123', status: $status)
        );
    });
}

test('a guest is redirected to login', function () {
    $returnRequest = ReturnRequest::factory()->create();

    test()->get("/admin/return-requests/{$returnRequest->id}")->assertRedirect('/login');
});

test('support can list and view but not accept a return request', function () {
    $order = Order::factory()->create(['total_cents' => 5000]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Succeeded, 'amount_cents' => 5000]);
    $returnRequest = ReturnRequest::factory()->create(['order_id' => $order->id, 'user_id' => $order->user_id]);

    test()->actingAs($this->support)->get('/admin/return-requests')->assertOk();
    test()->actingAs($this->support)->get("/admin/return-requests/{$returnRequest->id}")->assertOk();
    test()->actingAs($this->support)
        ->post("/admin/return-requests/{$returnRequest->id}/accept")
        ->assertForbidden();
});

test('support can refuse a return request', function () {
    Notification::fake();

    $order = Order::factory()->create();
    $returnRequest = ReturnRequest::factory()->create(['order_id' => $order->id, 'user_id' => $order->user_id]);

    test()->actingAs($this->support)
        ->post("/admin/return-requests/{$returnRequest->id}/refuse", ['admin_note' => 'Délai dépassé.'])
        ->assertRedirect();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Refused);
    expect($returnRequest->fresh()->admin_note)->toBe('Délai dépassé.');
    Notification::assertSentTo($order->user, ReturnRequestStatusUpdated::class);
});

test('accepting a return request refunds the returned items and marks it accepted', function () {
    Notification::fake();
    mockReturnRefund();

    $order = Order::factory()->create(['total_cents' => 5000]);
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Succeeded, 'amount_cents' => 5000]);
    $orderItem = OrderItem::factory()->create(['order_id' => $order->id, 'unit_price_cents' => 1000, 'quantity' => 2]);
    $returnRequest = ReturnRequest::factory()->create(['order_id' => $order->id, 'user_id' => $order->user_id]);
    $returnRequest->items()->create(['order_item_id' => $orderItem->id, 'quantity' => 1, 'amount_cents' => 1000]);

    test()->actingAs($this->admin)
        ->post("/admin/return-requests/{$returnRequest->id}/accept")
        ->assertRedirect();

    expect($returnRequest->fresh()->status)->toBe(ReturnRequestStatus::Accepted);
    expect($returnRequest->fresh()->decided_at)->not->toBeNull();
    expect($order->refunds()->sum('amount_cents'))->toBe(1000);

    Notification::assertSentTo($order->user, ReturnRequestStatusUpdated::class);
    Notification::assertSentTo($order->user, RefundConfirmation::class);
});

test('a return request that is not submitted cannot be accepted or refused again', function () {
    $order = Order::factory()->create();
    $returnRequest = ReturnRequest::factory()->create([
        'order_id' => $order->id,
        'user_id' => $order->user_id,
        'status' => ReturnRequestStatus::Accepted,
    ]);

    test()->actingAs($this->admin)
        ->post("/admin/return-requests/{$returnRequest->id}/accept")
        ->assertStatus(422);

    test()->actingAs($this->admin)
        ->post("/admin/return-requests/{$returnRequest->id}/refuse")
        ->assertStatus(422);
});
