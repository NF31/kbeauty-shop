<?php

use App\Application\Stock\UseCases\RecordStockMovement;
use App\Enums\InventoryMovementType;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('crossing the low stock threshold notifies admins only', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    (app(RecordStockMovement::class))($variant, InventoryMovementType::Sale, -7);

    expect($variant->fresh()->stock_quantity)->toBe(3);

    Notification::assertSentTo($admin, LowStockAlert::class);
    Notification::assertNotSentTo($staff, LowStockAlert::class);
});

test('a sale that stays above the threshold does not notify', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    (app(RecordStockMovement::class))($variant, InventoryMovementType::Sale, -2);

    Notification::assertNothingSent();
});

test('a sale that stays below the threshold does not re-notify', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $variant = ProductVariant::factory()->create(['stock_quantity' => 4]);
    $recordStockMovement = app(RecordStockMovement::class);

    $recordStockMovement($variant, InventoryMovementType::Sale, -1);

    Notification::assertNothingSent();
});

test('a restock crossing back above the threshold does not notify', function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $variant = ProductVariant::factory()->create(['stock_quantity' => 2]);

    (app(RecordStockMovement::class))($variant, InventoryMovementType::Restock, 20);

    Notification::assertNothingSent();
});
