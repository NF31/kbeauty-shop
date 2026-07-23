<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });
});

test('the product page serves French by default and English on the /en route', function () {
    $product = Product::factory()->published()->create([
        'name' => ['fr' => 'Sérum vitamine C', 'en' => 'Vitamin C Serum'],
        'description' => ['fr' => 'Un sérum éclaircissant.', 'en' => 'A brightening serum.'],
    ]);
    ProductVariant::factory()->default()->create(['product_id' => $product->id]);

    $this->get("/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'fr')
            ->where('product.name', 'Sérum vitamine C')
            ->where('product.description', 'Un sérum éclaircissant.')
        );

    $this->get("/en/produits/{$product->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('product.name', 'Vitamin C Serum')
            ->where('product.description', 'A brightening serum.')
        );
});

test('the cart page responds in French by default and in English on the /en route', function () {
    $this->get('/panier')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/cart')
            ->where('locale', 'fr')
        );

    $this->get('/en/panier')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/cart')
            ->where('locale', 'en')
        );
});

test('a guest can add a product to the cart via the /en prefixed route', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

    $response = $this->post('/en/panier', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertRedirect();

    $token = $response->getCookie(CartService::COOKIE_NAME)->getValue();

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->get('/en/panier')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('items', 1));
});

test('the checkout index responds in English on the /en route for an authenticated user', function () {
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

    $this->actingAs($user)->post('/en/panier', [
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $this->actingAs($user)
        ->get('/en/commande')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('locale', 'en')
        );
});

test('checkout redirects to the English cart route when the cart is empty on /en/commande', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/en/commande')
        ->assertRedirect('/en/panier');
});
