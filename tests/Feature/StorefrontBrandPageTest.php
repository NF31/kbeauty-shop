<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the brands directory lists only brands with at least one product', function () {
    $withProduct = Brand::factory()->create(['name' => 'COSRX']);
    Product::factory()->create(['brand_id' => $withProduct->id]);

    Brand::factory()->create(['name' => 'Sans produit']);

    $this->get('/marques')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/brands-index')
            ->has('brands', 1)
            ->where('brands.0.name', 'COSRX')
        );
});

test('the brand page lists only that brand published products', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $brand = Brand::factory()->create(['name' => 'COSRX']);
    $matching = Product::factory()->published()->create(['brand_id' => $brand->id]);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $other = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $other->id]);

    $this->get("/marques/{$brand->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/brand')
            ->where('brand.name', 'COSRX')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
        );
});

test('the brand page filters by gamme (category) scoped to that brand', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $brand = Brand::factory()->create();
    $category = Category::factory()->create();

    $matching = Product::factory()->published()->create(['brand_id' => $brand->id]);
    $matching->categories()->attach($category->id);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $sameBrandOtherCategory = Product::factory()->published()->create(['brand_id' => $brand->id]);
    ProductVariant::factory()->default()->create(['product_id' => $sameBrandOtherCategory->id]);

    $this->get("/marques/{$brand->slug}?category={$category->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/brand')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
        );
});

test('the brand page only exposes categories that have products from that brand', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $brand = Brand::factory()->create();
    $usedCategory = Category::factory()->create(['name' => 'Sérums']);
    $unusedCategory = Category::factory()->create(['name' => 'Masques']);

    $product = Product::factory()->published()->create(['brand_id' => $brand->id]);
    $product->categories()->attach($usedCategory->id);
    ProductVariant::factory()->default()->create(['product_id' => $product->id]);

    $this->get("/marques/{$brand->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/brand')
            ->has('categoryOptions', 1)
            ->where('categoryOptions.0.name', 'Sérums')
        );
});
