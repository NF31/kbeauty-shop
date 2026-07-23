<?php

use App\Models\Brand;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('support role cannot manage brands', function () {
    $support = User::factory()->create();
    $support->assignRole('support');

    $this->actingAs($support)->get('/admin/brands')->assertForbidden();
});

test('admin can list brands with product count', function () {
    Brand::factory()->create(['name' => 'COSRX']);
    Brand::factory()->create(['name' => 'Beauty of Joseon']);

    $this->actingAs($this->admin)->get('/admin/brands')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/brands/index')
            ->has('brands', 2)
        );
});

test('admin can create a brand with an auto-generated slug', function () {
    $this->actingAs($this->admin)->post('/admin/brands', [
        'name' => 'COSRX',
        'country_of_origin' => 'Corée du Sud',
    ])->assertRedirect('/admin/brands');

    $brand = Brand::query()->where('name', 'COSRX')->firstOrFail();
    expect($brand->slug)->toBe('cosrx');
    expect($brand->country_of_origin)->toBe('Corée du Sud');
});

test('creating a brand with a duplicate name gets a unique slug', function () {
    Brand::factory()->create(['name' => 'COSRX', 'slug' => 'cosrx']);

    $this->actingAs($this->admin)->post('/admin/brands', [
        'name' => 'COSRX',
    ])->assertRedirect();

    expect(Brand::query()->where('slug', 'cosrx-1')->exists())->toBeTrue();
});

test('admin can update a brand and the slug follows the new name', function () {
    $brand = Brand::factory()->create(['name' => 'COSRX', 'slug' => 'cosrx']);

    $this->actingAs($this->admin)->put("/admin/brands/{$brand->id}", [
        'name' => 'COSRX Corp',
    ])->assertRedirect('/admin/brands');

    expect($brand->fresh()->slug)->toBe('cosrx-corp');
});

test('deleting a brand nullifies the brand on its products', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    $this->actingAs($this->admin)->delete("/admin/brands/{$brand->id}")
        ->assertRedirect('/admin/brands');

    expect(Brand::query()->find($brand->id))->toBeNull();
    expect($product->fresh()->brand_id)->toBeNull();
});
