<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('roles and permissions from the domain are seeded', function () {
    expect(Role::pluck('name')->sort()->values()->all())->toEqual(['admin', 'staff', 'support']);

    expect(Permission::pluck('name')->sort()->values()->all())->toEqual([
        'content.manage',
        'orders.manage',
        'orders.refund',
        'products.manage',
        'reviews.moderate',
        'settings.manage',
    ]);
});

test('admin has every permission', function () {
    $admin = Role::findByName('admin');

    expect($admin->permissions)->toHaveCount(6);
});

test('staff can manage products and orders but not refund or moderate reviews', function () {
    $user = User::factory()->create();
    $user->assignRole('staff');

    expect($user->can('products.manage'))->toBeTrue();
    expect($user->can('orders.manage'))->toBeTrue();
    expect($user->can('orders.refund'))->toBeFalse();
    expect($user->can('reviews.moderate'))->toBeFalse();
});

test('support can manage orders and moderate reviews but not the catalog or finance', function () {
    $user = User::factory()->create();
    $user->assignRole('support');

    expect($user->can('orders.manage'))->toBeTrue();
    expect($user->can('reviews.moderate'))->toBeTrue();
    expect($user->can('products.manage'))->toBeFalse();
    expect($user->can('settings.manage'))->toBeFalse();
});

test('a user with no role has no permission', function () {
    $user = User::factory()->create();

    expect($user->can('orders.manage'))->toBeFalse();
});
