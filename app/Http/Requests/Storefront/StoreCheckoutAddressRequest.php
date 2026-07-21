<?php

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class StoreCheckoutAddressRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'shipping.full_name' => ['required', 'string', 'max:255'],
            'shipping.line1' => ['required', 'string', 'max:255'],
            'shipping.line2' => ['nullable', 'string', 'max:255'],
            'shipping.postal_code' => ['required', 'string', 'max:20'],
            'shipping.city' => ['required', 'string', 'max:255'],
            'shipping.country_code' => ['required', 'string', 'size:2'],
            'shipping.phone' => ['nullable', 'string', 'max:30'],

            'billing_same_as_shipping' => ['boolean'],

            'billing.full_name' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing.line1' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing.line2' => ['nullable', 'string', 'max:255'],
            'billing.postal_code' => ['required_if:billing_same_as_shipping,false', 'string', 'max:20'],
            'billing.city' => ['required_if:billing_same_as_shipping,false', 'string', 'max:255'],
            'billing.country_code' => ['required_if:billing_same_as_shipping,false', 'string', 'size:2'],
            'billing.phone' => ['nullable', 'string', 'max:30'],
        ];
    }
}
