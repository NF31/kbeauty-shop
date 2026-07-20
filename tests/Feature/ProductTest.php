<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a product can be created with the default draft status', function () {
    $product = Product::factory()->create();

    expect($product->status)->toBe(ProductStatus::Draft);
    expect($product->is_featured)->toBeFalse();
    expect($product->published_at)->toBeNull();
});

test('a product can be published', function () {
    $product = Product::factory()->published()->create();

    expect($product->status)->toBe(ProductStatus::Published);
    expect($product->published_at)->not->toBeNull();
});

test('skin_types is cast to an array', function () {
    $product = Product::factory()->create([
        'skin_types' => ['sèche', 'grasse'],
    ]);

    expect($product->fresh()->skin_types)->toBe(['sèche', 'grasse']);
});

test('a deleted product is soft deleted, not removed', function () {
    $product = Product::factory()->create();

    $product->delete();

    expect(Product::count())->toBe(0);
    expect(Product::withTrashed()->count())->toBe(1);
    expect(Product::withTrashed()->first()->deleted_at)->not->toBeNull();
});

test('the slug is auto-generated and unique-suffixed on collision', function () {
    Product::create(['name' => 'Sérum centella', 'description' => 'Un sérum.']);
    $duplicate = Product::create(['name' => 'Sérum centella', 'description' => 'Un autre sérum.']);

    expect($duplicate->slug)->toBe('serum-centella-1');
});
