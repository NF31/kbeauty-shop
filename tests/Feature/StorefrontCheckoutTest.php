<?php

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function addAndReachCheckout(ProductVariant $variant): string
{
    $response = test()->post('/panier', ['product_variant_id' => $variant->id, 'quantity' => 1]);

    return $response->getCookie(CartService::COOKIE_NAME)->getValue();
}

test('a guest with an empty cart is redirected away from /commande', function () {
    $this->get('/commande')->assertRedirect('/panier');
});

test('a guest with items in cart can reach /commande', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $token = addAndReachCheckout($variant);

    $this->withCookie(CartService::COOKIE_NAME, $token)
        ->get('/commande')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout')
            ->where('cart.itemCount', 1)
        );
});

test('a guest can submit shipping address with billing same as shipping', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $token = addAndReachCheckout($variant);

    $this->withCookie(CartService::COOKIE_NAME, $token)
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
    expect(Address::query()->where('type', AddressType::Shipping)->sole()->user_id)->toBeNull();
    expect(Address::query()->where('type', AddressType::Billing)->sole()->full_name)->toBe('Jeanne Dupont');
});

test('a distinct billing address is persisted when billing_same_as_shipping is false', function () {
    $variant = ProductVariant::factory()->create(['stock_quantity' => 5]);
    $token = addAndReachCheckout($variant);

    $this->withCookie(CartService::COOKIE_NAME, $token)
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
    $token = addAndReachCheckout($variant);

    $this->withCookie(CartService::COOKIE_NAME, $token)
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
