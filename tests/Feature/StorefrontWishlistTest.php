<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\CloudinaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('url')->andReturn('https://res.cloudinary.com/fake/image.jpg');
    });
});

test('a guest is redirected to login on the wishlist page', function () {
    $this->get('/mon-compte/favoris')->assertRedirect('/login');
});

test('a guest cannot add a product to a wishlist', function () {
    $product = Product::factory()->published()->create();

    $this->post("/favoris/{$product->slug}")->assertRedirect('/login');

    expect(Wishlist::query()->count())->toBe(0);
});

test('an authenticated user can add a product to their wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();

    $this->actingAs($user)
        ->post("/favoris/{$product->slug}")
        ->assertRedirect();

    expect(Wishlist::query()->where(['user_id' => $user->id, 'product_id' => $product->id])->exists())->toBeTrue();
});

test('adding the same product twice does not create a duplicate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();

    $this->actingAs($user)->post("/favoris/{$product->slug}");
    $this->actingAs($user)->post("/favoris/{$product->slug}")->assertRedirect();

    expect(Wishlist::query()->where(['user_id' => $user->id, 'product_id' => $product->id])->count())->toBe(1);
});

test('an authenticated user can remove a product from their wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();
    Wishlist::factory()->for($user)->for($product)->create();

    $this->actingAs($user)
        ->delete("/favoris/{$product->slug}")
        ->assertRedirect();

    expect(Wishlist::query()->where(['user_id' => $user->id, 'product_id' => $product->id])->exists())->toBeFalse();
});

test('the wishlist page only lists the current user products', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $mine = Product::factory()->published()->create(['name' => 'À moi']);
    Wishlist::factory()->for($user)->for($mine)->create();

    $notMine = Product::factory()->published()->create();
    Wishlist::factory()->for($otherUser)->for($notMine)->create();

    $this->actingAs($user)
        ->get('/mon-compte/favoris')
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist')
            ->has('products', 1)
            ->where('products.0.name', 'À moi')
        );
});

test('the wishlist page never lists an unpublished product', function () {
    $user = User::factory()->create();
    $draft = Product::factory()->create(['status' => ProductStatus::Draft]);
    Wishlist::factory()->for($user)->for($draft)->create();

    $this->actingAs($user)
        ->get('/mon-compte/favoris')
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist')
            ->has('products', 0)
        );
});

test('a user can generate a wishlist share link', function () {
    $user = User::factory()->create();
    expect($user->wishlist_share_token)->toBeNull();

    $this->actingAs($user)
        ->post('/mon-compte/favoris/partager')
        ->assertRedirect();

    expect($user->refresh()->wishlist_share_token)->not->toBeNull();
});

test('regenerating the share link changes the token', function () {
    $user = User::factory()->create(['wishlist_share_token' => 'ancien-token']);

    $this->actingAs($user)->post('/mon-compte/favoris/partager');

    expect($user->refresh()->wishlist_share_token)->not->toBe('ancien-token');
});

test('the public wishlist page is accessible without authentication', function () {
    $user = User::factory()->create(['name' => 'Jeanne', 'wishlist_share_token' => 'un-token-valide']);
    $product = Product::factory()->published()->create();
    Wishlist::factory()->for($user)->for($product)->create();

    $this->get('/favoris/partages/un-token-valide')
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist-public')
            ->where('ownerName', 'Jeanne')
            ->has('products', 1)
        );
});

test('an invalid share token returns a 404', function () {
    $this->get('/favoris/partages/token-inexistant')->assertNotFound();
});
