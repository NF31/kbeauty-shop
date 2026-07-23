<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('a guest is redirected to login', function () {
    $order = Order::factory()->create();

    $this->get("/mon-compte/commandes/{$order->id}/facture")->assertRedirect('/login');
});

test('the order owner can download their invoice', function () {
    Storage::fake('invoices');

    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);
    $invoice = Invoice::factory()->create(['order_id' => $order->id, 'path' => 'invoices/KB-2026-00001.pdf']);
    Storage::disk('invoices')->put($invoice->path, '%PDF-1.4 fake content');

    $this->actingAs($user)
        ->get("/mon-compte/commandes/{$order->id}/facture")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('a user cannot download another user\'s invoice', function () {
    Storage::fake('invoices');

    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $owner->id]);
    $invoice = Invoice::factory()->create(['order_id' => $order->id, 'path' => 'invoices/KB-2026-00001.pdf']);
    Storage::disk('invoices')->put($invoice->path, '%PDF-1.4 fake content');

    $this->actingAs($intruder)
        ->get("/mon-compte/commandes/{$order->id}/facture")
        ->assertForbidden();
});

test('downloading the invoice of an order with no invoice yet returns 404', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/mon-compte/commandes/{$order->id}/facture")
        ->assertNotFound();
});
