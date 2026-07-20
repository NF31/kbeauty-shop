<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
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

test('support role cannot manage products', function () {
    $support = User::factory()->create();
    $support->assignRole('support');

    $this->actingAs($support)->get('/admin/products')->assertForbidden();
});

test('admin can list products with brand and variant count', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);
    ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

    $this->actingAs($this->admin)->get('/admin/products')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/products/index')
            ->has('products', 1)
        );
});

test('admin can create a draft product with an auto-generated slug and categories', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->post('/admin/products', [
        'name' => 'Sérum vitamine C',
        'description' => 'Un sérum éclaircissant.',
        'status' => 'draft',
        'category_ids' => [$category->id],
    ])->assertRedirect();

    $product = Product::query()->where('name', 'Sérum vitamine C')->firstOrFail();
    expect($product->slug)->toBe('serum-vitamine-c');
    expect($product->categories->pluck('id')->all())->toBe([$category->id]);
});

test('publishing a product without an INCI list is rejected', function () {
    $this->actingAs($this->admin)->post('/admin/products', [
        'name' => 'Crème hydratante',
        'description' => 'Une crème.',
        'status' => 'published',
    ])->assertInvalid(['ingredients_inci']);

    expect(Product::query()->where('name', 'Crème hydratante')->exists())->toBeFalse();
});

test('publishing a product with an INCI list succeeds', function () {
    $this->actingAs($this->admin)->post('/admin/products', [
        'name' => 'Crème hydratante',
        'description' => 'Une crème.',
        'status' => 'published',
        'ingredients_inci' => 'Aqua, Glycerin',
    ])->assertRedirect();

    $product = Product::query()->where('name', 'Crème hydratante')->firstOrFail();
    expect($product->status->value)->toBe('published');
});

test('admin can update a product and its categories', function () {
    $product = Product::factory()->create(['name' => 'Sérum']);
    $oldCategory = Category::factory()->create();
    $newCategory = Category::factory()->create();
    $product->categories()->attach($oldCategory);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
        'name' => 'Sérum reformulé',
        'description' => $product->description,
        'status' => 'draft',
        'category_ids' => [$newCategory->id],
    ])->assertRedirect();

    $product->refresh();
    expect($product->slug)->toBe('serum-reformule');
    expect($product->categories->pluck('id')->all())->toBe([$newCategory->id]);
});

test('admin can delete a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->delete("/admin/products/{$product->id}")
        ->assertRedirect('/admin/products');

    expect(Product::query()->find($product->id))->toBeNull();
});

test('admin can add a variant axis with values to a product', function () {
    $product = Product::factory()->create();

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/options", [
        'name' => 'Contenance',
        'values' => ['30 ml', '50 ml'],
    ])->assertRedirect();

    $option = ProductOption::query()->where('product_id', $product->id)->firstOrFail();
    expect($option->name)->toBe('Contenance');
    expect($option->values()->pluck('value')->all())->toBe(['30 ml', '50 ml']);
});

test('admin can delete a variant axis', function () {
    $option = ProductOption::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/products/{$option->product_id}/options/{$option->id}")
        ->assertRedirect();

    expect(ProductOption::query()->find($option->id))->toBeNull();
});

test('admin can add a variant with option values to a product', function () {
    $product = Product::factory()->create();
    $option = ProductOption::factory()->create(['product_id' => $product->id]);
    $value = ProductOptionValue::factory()->create(['product_option_id' => $option->id]);

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/variants", [
        'sku' => 'SKU-TEST-001',
        'price_cents' => 1999,
        'stock_quantity' => 10,
        'option_value_ids' => [$value->id],
    ])->assertRedirect();

    $variant = ProductVariant::query()->where('sku', 'SKU-TEST-001')->firstOrFail();
    expect($variant->product_id)->toBe($product->id);
    expect($variant->optionValues->pluck('id')->all())->toBe([$value->id]);
});

test('a variant sku must be unique', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['sku' => 'SKU-DUPLICATE']);

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/variants", [
        'sku' => 'SKU-DUPLICATE',
        'price_cents' => 1000,
    ])->assertInvalid(['sku']);
});

test('admin can update a variant', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'stock_quantity' => 5]);

    $this->actingAs($this->admin)->put("/admin/products/{$product->id}/variants/{$variant->id}", [
        'sku' => $variant->sku,
        'price_cents' => 2500,
        'stock_quantity' => 42,
    ])->assertRedirect();

    expect($variant->fresh()->stock_quantity)->toBe(42);
    expect($variant->fresh()->price_cents)->toBe(2500);
});

test('admin can delete a variant', function () {
    $variant = ProductVariant::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/products/{$variant->product_id}/variants/{$variant->id}")
        ->assertRedirect();

    expect(ProductVariant::query()->find($variant->id))->toBeNull();
});
