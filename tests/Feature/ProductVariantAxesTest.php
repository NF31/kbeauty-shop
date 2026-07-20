<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a product belongs to a brand', function () {
    $brand = Brand::factory()->create(['name' => 'Beauty of Joseon']);
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    expect($product->brand->name)->toBe('Beauty of Joseon');
    expect($brand->products)->toHaveCount(1);
});

test('categories form a tree via parent_id', function () {
    $parent = Category::factory()->create(['name' => 'Soin visage']);
    $child = Category::factory()->create(['parent_id' => $parent->id, 'name' => 'Sérums']);

    expect($child->parent->id)->toBe($parent->id);
    expect($parent->children)->toHaveCount(1);
    expect($parent->children->first()->id)->toBe($child->id);
});

test('a product can belong to several categories and vice versa', function () {
    $product = Product::factory()->create();
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    $product->categories()->attach([$categoryA->id, $categoryB->id]);

    expect($product->categories)->toHaveCount(2);
    expect($categoryA->products)->toHaveCount(1);
});

test('a product with a single axis (contenance) generates one variant per value', function () {
    $product = Product::factory()->create();
    $option = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Contenance']);
    $value30 = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => '30 ml']);
    $value50 = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => '50 ml']);

    $variant30 = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'SERUM-30ML']);
    $variant30->optionValues()->attach($value30->id);

    $variant50 = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'sku' => 'SERUM-50ML']);
    $variant50->optionValues()->attach($value50->id);

    expect($product->variants)->toHaveCount(2);
    expect($variant30->optionValues->first()->value)->toBe('30 ml');
    expect($variant50->is_default)->toBeTrue();
});

test('a variant can combine two axes (contenance x teinte) without an extra migration', function () {
    $product = Product::factory()->create();

    $contenance = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Contenance']);
    $contenance50 = ProductOptionValue::factory()->create(['product_option_id' => $contenance->id, 'value' => '50 ml']);

    $teinte = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Teinte']);
    $teinte01 = ProductOptionValue::factory()->create(['product_option_id' => $teinte->id, 'value' => 'Teinte 01']);

    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $variant->optionValues()->attach([$contenance50->id, $teinte01->id]);

    expect($variant->optionValues)->toHaveCount(2);
    expect($variant->optionValues->pluck('value')->sort()->values()->all())
        ->toBe(['50 ml', 'Teinte 01']);
});

test('the variant sku must be unique', function () {
    ProductVariant::factory()->create(['sku' => 'SERUM-50ML']);

    expect(fn () => ProductVariant::factory()->create(['sku' => 'SERUM-50ML']))
        ->toThrow(QueryException::class);
});
