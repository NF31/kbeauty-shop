<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('a guest is redirected to login', function () {
    $this->get('/mon-compte/adresses')->assertRedirect('/login');
});

test('a user only sees their own addresses, default first', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Address::factory()->for($user)->create(['full_name' => 'Adresse normale']);
    Address::factory()->for($user)->create([
        'full_name' => 'Adresse par defaut',
        'is_default' => true,
    ]);
    Address::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->get('/mon-compte/adresses')
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/account-addresses')
            ->has('addresses', 2)
            ->where('addresses.0.fullName', 'Adresse par defaut')
        );
});

test('a user can create an address', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/mon-compte/adresses', [
        'type' => 'shipping',
        'full_name' => 'Jeanne Dupont',
        'line1' => '12 rue des Fleurs',
        'postal_code' => '75001',
        'city' => 'Paris',
        'country_code' => 'FR',
    ])->assertRedirect();

    expect(Address::query()->where('user_id', $user->id)->count())->toBe(1);
});

test('creating a new default address unsets the previous default of the same type', function () {
    $user = User::factory()->create();
    $existingDefault = Address::factory()->for($user)->create(['is_default' => true]);

    $this->actingAs($user)->post('/mon-compte/adresses', [
        'type' => 'shipping',
        'full_name' => 'Jeanne Dupont',
        'line1' => '12 rue des Fleurs',
        'postal_code' => '75001',
        'city' => 'Paris',
        'country_code' => 'FR',
        'is_default' => true,
    ]);

    expect($existingDefault->refresh()->is_default)->toBeFalse();
});

test('a user can update their own address', function () {
    $user = User::factory()->create();
    $address = Address::factory()->for($user)->create(['city' => 'Lyon']);

    $this->actingAs($user)->put("/mon-compte/adresses/{$address->id}", [
        'type' => 'shipping',
        'full_name' => $address->full_name,
        'line1' => $address->line1,
        'postal_code' => $address->postal_code,
        'city' => 'Marseille',
        'country_code' => 'FR',
    ])->assertRedirect();

    expect($address->refresh()->city)->toBe('Marseille');
});

test('a user cannot update another user\'s address', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $address = Address::factory()->for($otherUser)->create();

    $this->actingAs($user)->put("/mon-compte/adresses/{$address->id}", [
        'type' => 'shipping',
        'full_name' => 'Hack',
        'line1' => 'Hack',
        'postal_code' => '00000',
        'city' => 'Hack',
        'country_code' => 'FR',
    ])->assertForbidden();
});

test('a user can delete their own address', function () {
    $user = User::factory()->create();
    $address = Address::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete("/mon-compte/adresses/{$address->id}")
        ->assertRedirect();

    expect(Address::query()->find($address->id))->toBeNull();
});

test('a user cannot delete an address referenced by an existing order', function () {
    $user = User::factory()->create();
    $address = Address::factory()->for($user)->create();
    Order::factory()->for($user)->create([
        'shipping_address_id' => $address->id,
        'billing_address_id' => $address->id,
    ]);

    $this->actingAs($user)
        ->delete("/mon-compte/adresses/{$address->id}")
        ->assertRedirect()
        ->assertSessionHasErrors('address');

    expect(Address::query()->find($address->id))->not->toBeNull();
});

test('a user cannot delete another user\'s address', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $address = Address::factory()->for($otherUser)->create();

    $this->actingAs($user)
        ->delete("/mon-compte/adresses/{$address->id}")
        ->assertForbidden();

    expect(Address::query()->find($address->id))->not->toBeNull();
});
