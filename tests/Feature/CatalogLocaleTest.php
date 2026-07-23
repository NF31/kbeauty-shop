<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });
});

test('the default route serves the catalog in French', function () {
    $product = Product::factory()->published()->create([
        'name' => ['fr' => 'Sérum vitamine C', 'en' => 'Vitamin C Serum'],
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $product->id]);

    $this->get('/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->where('locale', 'fr')
            ->where('products.data.0.name', 'Sérum vitamine C')
        );
});

test('the /en prefixed route serves the catalog in English', function () {
    $product = Product::factory()->published()->create([
        'name' => ['fr' => 'Sérum vitamine C', 'en' => 'Vitamin C Serum'],
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $product->id]);

    $this->get('/en/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/catalog')
            ->where('locale', 'en')
            ->where('products.data.0.name', 'Vitamin C Serum')
        );
});

test('the locale does not leak between requests', function () {
    $this->get('/en/produits')->assertOk();

    $this->get('/produits')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'fr'));
});
