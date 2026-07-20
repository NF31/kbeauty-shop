<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CloudinaryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('support role cannot manage product images', function () {
    $support = User::factory()->create();
    $support->assignRole('support');
    $product = Product::factory()->create();

    $this->actingAs($support)
        ->post("/admin/products/{$product->id}/images", [
            'image' => UploadedFile::fake()->image('serum.jpg'),
        ])
        ->assertForbidden();
});

test('admin can upload a product image, stored as a Cloudinary public_id', function () {
    $product = Product::factory()->create();

    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('upload')->once()->andReturn('products/1/fake-public-id');
    });

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/images", [
        'image' => UploadedFile::fake()->image('serum.jpg'),
        'alt_text' => 'Sérum vitamine C',
    ])->assertRedirect();

    $image = ProductImage::query()->where('product_id', $product->id)->firstOrFail();
    expect($image->path)->toBe('products/1/fake-public-id');
    expect($image->alt_text)->toBe('Sérum vitamine C');
});

test('an image can be associated with a specific variant of the same product', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    $this->mock(CloudinaryService::class, function ($mock) {
        $mock->shouldReceive('upload')->once()->andReturn('products/1/fake-public-id');
    });

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/images", [
        'image' => UploadedFile::fake()->image('teinte.jpg'),
        'product_variant_id' => $variant->id,
    ])->assertRedirect();

    $image = ProductImage::query()->where('product_id', $product->id)->firstOrFail();
    expect($image->product_variant_id)->toBe($variant->id);
});

test('an image cannot be associated with a variant of another product', function () {
    $product = Product::factory()->create();
    $otherVariant = ProductVariant::factory()->create();

    $this->actingAs($this->admin)->post("/admin/products/{$product->id}/images", [
        'image' => UploadedFile::fake()->image('serum.jpg'),
        'product_variant_id' => $otherVariant->id,
    ])->assertInvalid(['product_variant_id']);
});

test('admin can delete a product image', function () {
    $image = ProductImage::factory()->create();

    $this->mock(CloudinaryService::class, function ($mock) use ($image) {
        $mock->shouldReceive('destroy')->once()->with($image->path);
    });

    $this->actingAs($this->admin)
        ->delete("/admin/products/{$image->product_id}/images/{$image->id}")
        ->assertRedirect();

    expect(ProductImage::query()->find($image->id))->toBeNull();
});
