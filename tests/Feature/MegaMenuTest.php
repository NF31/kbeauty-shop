<?php

use App\Models\Category;
use App\Models\Product;
use App\Support\MegaMenuPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a root category with a published product is included', function () {
    $category = Category::factory()->create(['name' => 'Sérum', 'position' => 1]);
    $product = Product::factory()->published()->create();
    $product->categories()->attach($category);

    $categories = MegaMenuPresenter::categories();

    expect($categories)->toHaveCount(1);
    expect($categories[0]['name'])->toBe('Sérum');
    expect($categories[0]['hasOwnProducts'])->toBeTrue();
});

test('a root category with only draft products is excluded', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category);

    expect(MegaMenuPresenter::categories())->toBeEmpty();
});

test('a parent with no products of its own but a populated child is included as a non-clickable heading', function () {
    $parent = Category::factory()->create(['name' => 'Soins visage', 'position' => 1]);
    $child = Category::factory()->create(['name' => 'Toner', 'parent_id' => $parent->id, 'position' => 1]);
    $product = Product::factory()->published()->create();
    $product->categories()->attach($child);

    $categories = MegaMenuPresenter::categories();

    expect($categories)->toHaveCount(1);
    expect($categories[0]['hasOwnProducts'])->toBeFalse();
    expect($categories[0]['children'])->toHaveCount(1);
    expect($categories[0]['children'][0]['name'])->toBe('Toner');
});

test('a child category with only draft products is excluded from its parent', function () {
    $parent = Category::factory()->create();
    $emptyChild = Category::factory()->create(['parent_id' => $parent->id]);
    $draftProduct = Product::factory()->create();
    $draftProduct->categories()->attach($emptyChild);
    $publishedProduct = Product::factory()->published()->create();
    $publishedProduct->categories()->attach($parent);

    $categories = MegaMenuPresenter::categories();

    expect($categories)->toHaveCount(1);
    expect($categories[0]['children'])->toBeEmpty();
});

test('categories are ordered by position', function () {
    Category::factory()->create(['name' => 'B', 'position' => 2]);
    Category::factory()->create(['name' => 'A', 'position' => 1]);

    foreach (Category::all() as $category) {
        Product::factory()->published()->create()->categories()->attach($category);
    }

    $names = array_column(MegaMenuPresenter::categories(), 'name');

    expect($names)->toBe(['A', 'B']);
});
