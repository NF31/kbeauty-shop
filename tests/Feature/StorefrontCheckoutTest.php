<?php

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function addAndReachCheckout(ProductVariant $variant): User
{
    $user = User::factory()->create();

    test()->actingAs($user)
        ->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    return $user;
}

test('a guest is redirected to login before reaching /commande, and back to /commande after logging in', function () {
    $this->get('/commande')->assertRedirect('/login');

    $user = User::factory()->create();

    $this->from('/commande')
        ->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/commande');
});

test('an authenticated user with an empty cart is redirected away from /commande', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/commande')->assertRedirect('/panier');
});

test('an authenticated user with items in cart can reach /commande', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $this->actingAs($user)
        ->get('/commande')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'address')
            ->where('cart.itemCount', 1)
        );
});

test('a user with addresses already set in session reaches the recap step', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping' => [
                'full_name' => 'Jeanne Dupont',
                'line1' => '12 rue des Lilas',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country_code' => 'FR',
            ],
            'billing_same_as_shipping' => true,
        ]);

    $this->actingAs($user)
        ->get('/commande')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('step', 'recap')
        );
});

test('a user can submit shipping address with billing same as shipping', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping' => [
                'full_name' => 'Jeanne Dupont',
                'line1' => '12 rue des Lilas',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country_code' => 'FR',
            ],
            'billing_same_as_shipping' => true,
        ])
        ->assertRedirect();

    expect(Address::query()->count())->toBe(2);
    expect(Address::query()->where('type', AddressType::Shipping)->sole()->user_id)->toBe($user->id);
    expect(Address::query()->where('type', AddressType::Billing)->sole()->full_name)->toBe('Jeanne Dupont');
});

test('a distinct billing address is persisted when billing_same_as_shipping is false', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping' => [
                'full_name' => 'Jeanne Dupont',
                'line1' => '12 rue des Lilas',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country_code' => 'FR',
            ],
            'billing_same_as_shipping' => false,
            'billing' => [
                'full_name' => 'Société ACME',
                'line1' => '5 avenue de la Facture',
                'postal_code' => '69000',
                'city' => 'Lyon',
                'country_code' => 'FR',
            ],
        ])
        ->assertRedirect();

    $billing = Address::query()->where('type', AddressType::Billing)->sole();
    expect($billing->full_name)->toBe('Société ACME');
    expect($billing->city)->toBe('Lyon');
});

test('billing address fields are required when billing_same_as_shipping is false', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping' => [
                'full_name' => 'Jeanne Dupont',
                'line1' => '12 rue des Lilas',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country_code' => 'FR',
            ],
            'billing_same_as_shipping' => false,
        ])
        ->assertSessionHasErrors([
            'billing.full_name',
            'billing.line1',
            'billing.postal_code',
            'billing.city',
            'billing.country_code',
        ]);

    expect(Address::query()->count())->toBe(0);
});

test('an authenticated user checkout address is attached to their account', function () {
    $user = User::factory()->create();
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);

    $this->actingAs($user)
        ->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping' => [
                'full_name' => 'Jeanne Dupont',
                'line1' => '12 rue des Lilas',
                'postal_code' => '75001',
                'city' => 'Paris',
                'country_code' => 'FR',
            ],
            'billing_same_as_shipping' => true,
        ])
        ->assertRedirect();

    expect(Address::query()->where('type', AddressType::Shipping)->sole()->user_id)->toBe($user->id);
});

test('/commande exposes the user saved addresses grouped by type', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    Address::factory()->for($user)->create(['type' => AddressType::Shipping, 'full_name' => 'Adresse livraison']);
    Address::factory()->for($user)->create(['type' => AddressType::Billing, 'full_name' => 'Adresse facturation']);

    $this->actingAs($user)
        ->get('/commande')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->has('savedShippingAddresses', 1)
            ->has('savedBillingAddresses', 1)
            ->where('savedShippingAddresses.0.full_name', 'Adresse livraison')
            ->where('savedBillingAddresses.0.full_name', 'Adresse facturation')
        );
});

test('a user can select an existing saved address instead of creating a new one', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $savedShipping = Address::factory()->for($user)->create(['type' => AddressType::Shipping]);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping_address_id' => $savedShipping->id,
            'billing_same_as_shipping' => true,
        ])
        ->assertRedirect();

    // Le tunnel duplique toujours l'adresse choisie vers une nouvelle ligne
    // `billing` (comportement inchangé), donc aucune nouvelle ligne shipping
    // n'a été créée mais une ligne billing a bien été ajoutée.
    expect(Address::query()->where('type', AddressType::Shipping)->count())->toBe(1);
    expect(Address::query()->where('type', AddressType::Billing)->count())->toBe(1);
});

test('a saved address belonging to another user cannot be selected', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $otherUsersAddress = Address::factory()->create(['type' => AddressType::Shipping]);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping_address_id' => $otherUsersAddress->id,
            'billing_same_as_shipping' => true,
        ])
        ->assertSessionHasErrors(['shipping_address_id']);
});

test('a user can select distinct saved addresses for shipping and billing', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $user = addAndReachCheckout($variant);

    $savedShipping = Address::factory()->for($user)->create(['type' => AddressType::Shipping]);
    $savedBilling = Address::factory()->for($user)->create(['type' => AddressType::Billing]);

    $this->actingAs($user)
        ->post('/commande/adresse', [
            'shipping_address_id' => $savedShipping->id,
            'billing_same_as_shipping' => false,
            'billing_address_id' => $savedBilling->id,
        ])
        ->assertRedirect();

    // Aucune nouvelle adresse ne doit avoir été créée : les deux lignes
    // existantes ont été directement réutilisées comme shipping/billing.
    expect(Address::query()->count())->toBe(2);
});
