<?php

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('a guest is redirected to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

test('a user without an admin role gets a 403', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('admin, staff and support roles can all access the dashboard', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get('/admin')->assertOk();
})->with(['admin', 'staff', 'support']);

test('the dashboard exposes catalog stats', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Product::factory()->count(2)->create(['status' => ProductStatus::Draft]);
    $published = Product::factory()->published()->create();
    $lowStockVariant = ProductVariant::factory()->for($published)->create(['stock_quantity' => 1]);
    ProductVariant::factory()->for($published)->create(['stock_quantity' => 50]);

    $this->actingAs($admin)->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->where('stats.productsCount', 3)
            ->where('stats.publishedProductsCount', 1)
            ->where('stats.lowStockVariantsCount', 1)
        );

    expect($lowStockVariant->stock_quantity)->toBe(1);
});
