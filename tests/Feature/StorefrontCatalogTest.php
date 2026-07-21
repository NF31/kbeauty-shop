<?php

use App\Models\Brand;
use App\Models\Category;
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

test('the catalog page filters products by category', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $category = Category::factory()->create();
    $matching = Product::factory()->published()->create();
    $matching->categories()->attach($category->id);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $other = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $other->id]);

    $this->get("/produits?category={$category->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
            ->where('activeCategory.slug', $category->slug)
        );
});

test('the catalog page filters products by brand', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $brand = Brand::factory()->create();
    $matching = Product::factory()->published()->create(['brand_id' => $brand->id]);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $other = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $other->id]);

    $this->get("/produits?brand={$brand->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
            ->where('activeBrand.slug', $brand->slug)
        );
});

test('the catalog page filters products by price range', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $cheap = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $cheap->id, 'price_cents' => 500]);

    $matching = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $matching->id, 'price_cents' => 2500]);

    $expensive = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $expensive->id, 'price_cents' => 9000]);

    $this->get('/produits?price_min=20&price_max=30')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
        );
});

test('the catalog page sorts products by price ascending', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $expensive = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $expensive->id, 'price_cents' => 9000]);

    $cheap = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create(['product_id' => $cheap->id, 'price_cents' => 500]);

    $this->get('/produits?sort=price_asc')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->where('products.data.0.id', $cheap->id)
            ->where('products.data.1.id', $expensive->id)
            ->where('sort', 'price_asc')
        );
});

test('the catalog page searches products by name', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $matching = Product::factory()->published()->create(['name' => 'Sérum vitamine C éclat']);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $other = Product::factory()->published()->create(['name' => 'Crème hydratante nuit']);
    ProductVariant::factory()->default()->create(['product_id' => $other->id]);

    $this->get('/produits?q=vitamine')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
            ->where('search', 'vitamine')
        );
});

test('the catalog page search is case insensitive and matches the short description', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $matching = Product::factory()->published()->create([
        'name' => 'Produit A',
        'short_description' => 'Apaise les peaux SENSIBLES',
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $matching->id]);

    $other = Product::factory()->published()->create([
        'name' => 'Produit B',
        'short_description' => 'Hydrate en profondeur',
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $other->id]);

    $this->get('/produits?q=sensibles')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->has('products.data', 1)
            ->where('products.data.0.id', $matching->id)
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
