<?php

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('creating a variant with an initial stock records an adjustment movement', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/variants", [
        'sku' => 'SKU-TEST-1',
        'price_cents' => 1000,
        'stock_quantity' => 12,
        'is_default' => true,
    ])->assertRedirect();

    $variant = ProductVariant::query()->where('sku', 'SKU-TEST-1')->firstOrFail();

    expect($variant->stock_quantity)->toBe(12);

    $movement = InventoryMovement::query()->where('product_variant_id', $variant->id)->sole();
    expect($movement->type)->toBe(InventoryMovementType::Adjustment);
    expect($movement->quantity)->toBe(12);
});

test('creating a variant without an initial stock records no movement', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/variants", [
        'sku' => 'SKU-TEST-2',
        'price_cents' => 1000,
        'is_default' => true,
    ])->assertRedirect();

    $variant = ProductVariant::query()->where('sku', 'SKU-TEST-2')->firstOrFail();

    expect($variant->stock_quantity)->toBe(0);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(0);
});

test('increasing a variant stock on update records a positive adjustment movement', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 5]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 8,
        'is_default' => $variant->is_default,
    ])->assertRedirect();

    expect($variant->fresh()->stock_quantity)->toBe(8);

    $movement = InventoryMovement::query()->where('product_variant_id', $variant->id)->sole();
    expect($movement->type)->toBe(InventoryMovementType::Adjustment);
    expect($movement->quantity)->toBe(3);
});

test('decreasing a variant stock on update records a negative adjustment movement', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 10]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 4,
        'is_default' => $variant->is_default,
    ])->assertRedirect();

    expect($variant->fresh()->stock_quantity)->toBe(4);

    $movement = InventoryMovement::query()->where('product_variant_id', $variant->id)->sole();
    expect($movement->type)->toBe(InventoryMovementType::Adjustment);
    expect($movement->quantity)->toBe(-6);
});

test('updating a variant without changing stock records no movement', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 7]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => $variant->price_cents,
        'stock_quantity' => 7,
        'is_default' => $variant->is_default,
    ])->assertRedirect();

    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect(InventoryMovement::query()->where('product_variant_id', $variant->id)->count())->toBe(0);
});
