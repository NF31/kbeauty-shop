<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('support role cannot manage categories', function () {
    $support = User::factory()->create();
    $support->assignRole('support');

    $this->actingAs($support)->get('/admin/categories')->assertForbidden();
});

test('admin can list categories with parent and product count', function () {
    $parent = Category::factory()->create(['name' => 'Soins visage']);
    Category::factory()->create(['name' => 'Sérums', 'parent_id' => $parent->id]);

    $this->actingAs($this->admin)->get('/admin/categories')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/categories/index')
            ->has('categories', 2)
        );
});

test('admin can create a category with an auto-generated slug', function () {

    $this->actingAs($this->admin)->post('/admin/categories', [
        'name' => 'Soins visage',
    ])->assertRedirect('/admin/categories');

    $category = Category::query()->where('name', 'Soins visage')->firstOrFail();
    expect($category->slug)->toBe('soins-visage');
    expect($category->parent_id)->toBeNull();
});

test('creating a category with a duplicate name gets a unique slug', function () {
    Category::factory()->create(['name' => 'Soins visage', 'slug' => 'soins-visage']);

    $this->actingAs($this->admin)->post('/admin/categories', [
        'name' => 'Soins visage',
    ])->assertRedirect();

    expect(Category::query()->where('slug', 'soins-visage-1')->exists())->toBeTrue();
});

test('admin can update a category and the slug follows the new name', function () {
    $category = Category::factory()->create(['name' => 'Soins visage', 'slug' => 'soins-visage']);

    $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
        'name' => 'Soins du visage',
    ])->assertRedirect('/admin/categories');

    expect($category->fresh()->slug)->toBe('soins-du-visage');
});

test('a category cannot be set as its own parent', function () {
    $category = Category::factory()->create();

    $this->actingAs($this->admin)->put("/admin/categories/{$category->id}", [
        'name' => $category->name,
        'parent_id' => $category->id,
    ])->assertInvalid(['parent_id']);
});

test('a category cannot be moved under one of its own descendants', function () {
    $grandparent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($this->admin)->put("/admin/categories/{$grandparent->id}", [
        'name' => $grandparent->name,
        'parent_id' => $child->id,
    ])->assertInvalid(['parent_id']);
});

test('deleting a category promotes its children to root categories', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($this->admin)->delete("/admin/categories/{$parent->id}")
        ->assertRedirect('/admin/categories');

    expect(Category::query()->find($parent->id))->toBeNull();
    expect($child->fresh()->parent_id)->toBeNull();
});
