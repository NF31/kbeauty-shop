<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the catalog page lists published products with brand and price', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $product = Product::factory()->published()->create(['name' => 'Sérum vitamine C']);
    ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_cents' => 2990]);

    $this->get('/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Sérum vitamine C')
            ->where('products.data.0.priceCents', 2990)
        );
});

test('the catalog page exposes the compare-at price when it is set and higher than the price', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $product = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'price_cents' => 2990,
        'compare_at_price_cents' => 3990,
    ]);

    $this->get('/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->where('products.data.0.priceCents', 2990)
            ->where('products.data.0.compareAtPriceCents', 3990)
        );
});

test('the catalog page filters products by skin type', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $matching = Product::factory()->published()->create(['skin_types' => ['seche', 'sensible']]);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $other = Product::factory()->published()->create(['skin_types' => ['grasse']]);
    ProductVariant::factory()->default()->create(['product_id' => $other->id]);

    $this->get('/produits?skin_type=seche')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
            ->where('activeSkinType.value', 'seche')
        );
});

test('an invalid skin type query parameter is ignored', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    Product::factory()->published()->create();

    $this->get('/produits?skin_type=inexistant')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('activeSkinType', null)
        );
});

test('the catalog page excludes draft and archived products', function () {
    Product::factory()->create(['status' => 'draft']);
    Product::factory()->published()->create(['status' => 'archived']);
    Product::factory()->published()->create();

    $this->get('/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
        );
});

test('the catalog page paginates results', function () {
    Product::factory()->published()->count(30)->create();

    $this->get('/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 24)
            ->where('products.last_page', 2)
        );
});
