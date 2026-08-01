<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('support role cannot manage product lines', function () {
    $support = User::factory()->create();
    $support->assignRole('support');

    $this->actingAs($support)->get('/admin/product-lines')->assertForbidden();
});

test('admin can list product lines with brand and product count', function () {
    $brand = Brand::factory()->create(['name' => 'COSRX']);
    ProductLine::factory()->create(['name' => 'Advanced Snail', 'brand_id' => $brand->id]);

    $this->actingAs($this->admin)->get('/admin/product-lines')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/product-lines/index')
            ->has('productLines.data', 1)
        );
});

test('admin can create a product line with an auto-generated slug', function () {
    $brand = Brand::factory()->create();

    $this->actingAs($this->admin)->post('/admin/product-lines', [
        'brand_id' => $brand->id,
        'name' => 'Advanced Snail',
    ])->assertRedirect('/admin/product-lines');

    $productLine = ProductLine::query()->where('name', 'Advanced Snail')->firstOrFail();
    expect($productLine->slug)->toBe('advanced-snail');
    expect($productLine->brand_id)->toBe($brand->id);
});

test('a product line requires a valid brand', function () {
    $this->actingAs($this->admin)->post('/admin/product-lines', [
        'brand_id' => 999999,
        'name' => 'Advanced Snail',
    ])->assertInvalid(['brand_id']);
});

test('admin can update a product line and the slug follows the new name', function () {
    $productLine = ProductLine::factory()->create(['name' => 'Advanced Snail', 'slug' => 'advanced-snail']);

    $this->actingAs($this->admin)->put("/admin/product-lines/{$productLine->id}", [
        'brand_id' => $productLine->brand_id,
        'name' => 'Advanced Snail Mucin',
    ])->assertRedirect('/admin/product-lines');

    expect($productLine->fresh()->slug)->toBe('advanced-snail-mucin');
});

test('deleting a product line detaches its products instead of deleting them', function () {
    $productLine = ProductLine::factory()->create();
    $product = Product::factory()->create(['product_line_id' => $productLine->id]);

    $this->actingAs($this->admin)->delete("/admin/product-lines/{$productLine->id}")
        ->assertRedirect('/admin/product-lines');

    expect(ProductLine::query()->find($productLine->id))->toBeNull();
    expect($product->fresh()->product_line_id)->toBeNull();
});
