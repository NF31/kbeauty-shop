<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a guest is redirected to login', function () {
    $order = Order::factory()->create();

    $this->get("/admin/orders/{$order->id}/invoice")->assertRedirect('/login');
});

test('an admin can download an order invoice', function () {
    Storage::fake('invoices');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order = Order::factory()->create();
    $invoice = Invoice::factory()->create(['order_id' => $order->id, 'path' => 'invoices/KB-2026-00001.pdf']);
    Storage::disk('invoices')->put($invoice->path, '%PDF-1.4 fake content');

    $this->actingAs($admin)
        ->get("/admin/orders/{$order->id}/invoice")
        ->assertOk()
        ->assertHeader('content-disposition');
});

test('a user without orders.manage permission cannot download an invoice', function () {
    $customer = User::factory()->create();
    $order = Order::factory()->create();
    Invoice::factory()->create(['order_id' => $order->id]);

    $this->actingAs($customer)
        ->get("/admin/orders/{$order->id}/invoice")
        ->assertForbidden();
});

test('downloading the invoice of an order with no invoice yet returns 404', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $order = Order::factory()->create();

    $this->actingAs($admin)
        ->get("/admin/orders/{$order->id}/invoice")
        ->assertNotFound();
});
