<?php

use App\Application\Stock\UseCases\RecordStockMovement;
use App\Enums\InventoryMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a restock movement increases stock_quantity and is recorded', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $movement = (app(RecordStockMovement::class))($variant, InventoryMovementType::Restock, 5, 'Réassort fournisseur');

    expect($variant->fresh()->stock_quantity)->toBe(15);
    expect($movement->type)->toBe(InventoryMovementType::Restock);
    expect($movement->quantity)->toBe(5);
    expect($movement->note)->toBe('Réassort fournisseur');
});

test('a sale movement decreases stock_quantity via a negative quantity', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 10]);

    $movement = (app(RecordStockMovement::class))($variant, InventoryMovementType::Sale, -3);

    expect($variant->fresh()->stock_quantity)->toBe(7);
    expect($movement->type)->toBe(InventoryMovementType::Sale);
});

test('a sale that would oversell throws and leaves stock untouched', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 2]);

    expect(fn () => (app(RecordStockMovement::class))($variant, InventoryMovementType::Sale, -5))
        ->toThrow(InsufficientStockException::class);

    expect($variant->fresh()->stock_quantity)->toBe(2);
    expect($variant->fresh()->movements)->toHaveCount(0);
});

test('a variant can list its movements history', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 0]);
    $recordStockMovement = app(RecordStockMovement::class);

    $recordStockMovement($variant, InventoryMovementType::Restock, 20);
    $recordStockMovement($variant, InventoryMovementType::Sale, -4);
    $recordStockMovement($variant, InventoryMovementType::Returned, 1);

    expect($variant->fresh()->movements)->toHaveCount(3);
    expect($variant->fresh()->stock_quantity)->toBe(17);
});
