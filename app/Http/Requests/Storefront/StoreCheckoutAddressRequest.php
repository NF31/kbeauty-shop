<?php

namespace App\Http\Requests\Storefront;

use App\Enums\AddressType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCheckoutAddressRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        // Une adresse existante (`*_address_id`) dispense de saisir les champs
        // du formulaire correspondant — sinon on garde la validation
        // "nouvelle adresse" telle quelle (miroir du superRefine front).
        $hasShippingId = $this->filled('shipping_address_id');
        $billingSameAsShipping = $this->boolean('billing_same_as_shipping', true);
        $hasBillingId = $this->filled('billing_address_id');

        return [
            'shipping_address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', AddressType::Shipping->value),
            ],
            'shipping.full_name' => [$hasShippingId ? 'nullable' : 'required', 'string', 'max:255'],
            'shipping.line1' => [$hasShippingId ? 'nullable' : 'required', 'string', 'max:255'],
            'shipping.line2' => ['nullable', 'string', 'max:255'],
            'shipping.postal_code' => [$hasShippingId ? 'nullable' : 'required', 'string', 'max:20'],
            'shipping.city' => [$hasShippingId ? 'nullable' : 'required', 'string', 'max:255'],
            'shipping.country_code' => [$hasShippingId ? 'nullable' : 'required', 'string', 'size:2'],
            'shipping.phone' => ['nullable', 'string', 'max:30'],

            'billing_same_as_shipping' => ['boolean'],

            'billing_address_id' => [
                'nullable',
                'integer',
                Rule::exists('addresses', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', AddressType::Billing->value),
            ],
            'billing.full_name' => [(! $billingSameAsShipping && ! $hasBillingId) ? 'required' : 'nullable', 'string', 'max:255'],
            'billing.line1' => [(! $billingSameAsShipping && ! $hasBillingId) ? 'required' : 'nullable', 'string', 'max:255'],
            'billing.line2' => ['nullable', 'string', 'max:255'],
            'billing.postal_code' => [(! $billingSameAsShipping && ! $hasBillingId) ? 'required' : 'nullable', 'string', 'max:20'],
            'billing.city' => [(! $billingSameAsShipping && ! $hasBillingId) ? 'required' : 'nullable', 'string', 'max:255'],
            'billing.country_code' => [(! $billingSameAsShipping && ! $hasBillingId) ? 'required' : 'nullable', 'string', 'size:2'],
            'billing.phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
