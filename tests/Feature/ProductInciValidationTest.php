<?php

use App\Enums\ProductStatus;
use App\Exceptions\MissingInciListException;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a draft product does not require an INCI list', function () {
    $product = Product::factory()->create(['ingredients_inci' => null]);

    expect($product->status)->toBe(ProductStatus::Draft);
    expect($product->ingredients_inci)->toBeNull();
});

test('a product cannot be published without an INCI list', function () {
    expect(fn () => Product::factory()->create([
        'status' => ProductStatus::Published,
        'published_at' => now(),
        'ingredients_inci' => null,
    ]))->toThrow(MissingInciListException::class);
});

test('a product cannot be published with a blank INCI list', function () {
    expect(fn () => Product::factory()->create([
        'status' => ProductStatus::Published,
        'published_at' => now(),
        'ingredients_inci' => '   ',
    ]))->toThrow(MissingInciListException::class);
});

test('a product can be published once it has an INCI list', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Published,
        'published_at' => now(),
        'ingredients_inci' => 'Aqua, Glycerin, Niacinamide',
    ]);

    expect($product->status)->toBe(ProductStatus::Published);
});

test('an already published product cannot lose its INCI list on update', function () {
    $product = Product::factory()->create([
        'status' => ProductStatus::Published,
        'published_at' => now(),
        'ingredients_inci' => 'Aqua, Glycerin',
    ]);

    expect(fn () => $product->update(['ingredients_inci' => null]))
        ->toThrow(MissingInciListException::class);
});
