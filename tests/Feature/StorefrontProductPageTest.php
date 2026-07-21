<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a published product page is publicly visible', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $product = Product::factory()->published()->create(['name' => 'Sérum vitamine C']);
    $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'price_cents' => 2990]);

    $this->get("/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/product')
            ->where('product.name', 'Sérum vitamine C')
            ->where('priceCents', 2990)
            ->where('defaultVariantId', $variant->id)
        );
});

test('the product page exposes ingredients and how-to-use for the tabs', function () {
    $product = Product::factory()->published()->create([
        'ingredients_inci' => 'Aqua, Glycerin, Niacinamide',
        'how_to_use' => 'Appliquer matin et soir sur peau propre.',
    ]);

    $this->get("/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/product')
            ->where('product.ingredients_inci', 'Aqua, Glycerin, Niacinamide')
            ->where('product.how_to_use', 'Appliquer matin et soir sur peau propre.')
        );
});

test('the product page exposes the default variant stock quantity', function () {
    $product = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'stock_quantity' => 7,
    ]);

    $this->get("/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/product')
            ->where('stockQuantity', 7)
        );
});

test('the product page exposes the compare-at price when it is set and higher than the price', function () {
    $product = Product::factory()->published()->create();
    ProductVariant::factory()->default()->create([
        'product_id' => $product->id,
        'price_cents' => 2990,
        'compare_at_price_cents' => 3990,
    ]);

    $this->get("/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/product')
            ->where('priceCents', 2990)
            ->where('compareAtPriceCents', 3990)
        );
});

test('a draft product page returns 404', function () {
    $product = Product::factory()->create(['status' => 'draft']);

    $this->get("/produits/{$product->slug}")->assertNotFound();
});

test('an archived product page returns 404', function () {
    $product = Product::factory()->published()->create(['status' => 'archived']);

    $this->get("/produits/{$product->slug}")->assertNotFound();
});

test('the product page exposes images sorted by position with their variant association', function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });

    $product = Product::factory()->published()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    ProductImage::factory()->create(['product_id' => $product->id, 'position' => 1, 'product_variant_id' => null]);
    ProductImage::factory()->create(['product_id' => $product->id, 'position' => 0, 'product_variant_id' => $variant->id]);

    $this->get("/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/product')
            ->has('images', 2)
            ->where('images.0.product_variant_id', $variant->id)
            ->where('images.1.product_variant_id', null)
        );
});
