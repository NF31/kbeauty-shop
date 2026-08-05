<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('a guest is redirected to login', function () {
    test()->get('/admin/users')->assertRedirect('/login');
});

test('staff and support cannot access user management', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    test()->actingAs($staff)->get('/admin/users')->assertForbidden();
    test()->actingAs($staff)
        ->patch("/admin/users/{$this->admin->id}/role", ['role' => 'staff'])
        ->assertForbidden();
});

test('an admin can change another user\'s role', function () {
    $user = User::factory()->create();
    $user->assignRole('support');

    test()->actingAs($this->admin)
        ->patch("/admin/users/{$user->id}/role", ['role' => 'staff'])
        ->assertRedirect('/admin/users');

    expect($user->fresh()->getRoleNames()->all())->toBe(['staff']);
});

test('an admin cannot remove their own admin role', function () {
    test()->actingAs($this->admin)
        ->patch("/admin/users/{$this->admin->id}/role", ['role' => 'staff'])
        ->assertSessionHasErrors('role');

    expect($this->admin->fresh()->getRoleNames()->all())->toBe(['admin']);
});

test('an invalid role is rejected', function () {
    $user = User::factory()->create();

    test()->actingAs($this->admin)
        ->patch("/admin/users/{$user->id}/role", ['role' => 'superadmin'])
        ->assertSessionHasErrors('role');
});
