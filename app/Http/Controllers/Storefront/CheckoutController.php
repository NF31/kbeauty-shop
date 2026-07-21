<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\AddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCheckoutAddressRequest;
use App\Models\Address;
use App\Services\CartService;
use App\Services\CloudinaryService;
use App\Support\CartPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    private const SESSION_SHIPPING_ADDRESS_ID = 'checkout.shipping_address_id';

    private const SESSION_BILLING_ADDRESS_ID = 'checkout.billing_address_id';

    public function index(Request $request, CartService $cartService, CloudinaryService $cloudinary): Response|RedirectResponse
    {
        $cart = $cartService->current($request);
        $cart->loadMissing('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route('storefront.cart.index');
        }

        return Inertia::render('storefront/checkout', [
            'cart' => CartPresenter::present($cart, $cloudinary),
            'defaultShippingAddress' => $request->user()?->addresses()
                ->where('type', AddressType::Shipping)
                ->where('is_default', true)
                ->first(),
            'shippingAddressId' => $request->session()->get(self::SESSION_SHIPPING_ADDRESS_ID),
            'billingAddressId' => $request->session()->get(self::SESSION_BILLING_ADDRESS_ID),
        ]);
    }

    public function storeAddress(StoreCheckoutAddressRequest $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        $shipping = Address::query()->create([
            ...$request->validated('shipping'),
            'user_id' => $userId,
            'type' => AddressType::Shipping,
        ]);

        $billingSameAsShipping = $request->boolean('billing_same_as_shipping', true);

        $billing = $billingSameAsShipping
            ? Address::query()->create([
                ...$request->validated('shipping'),
                'user_id' => $userId,
                'type' => AddressType::Billing,
            ])
            : Address::query()->create([
                ...$request->validated('billing'),
                'user_id' => $userId,
                'type' => AddressType::Billing,
            ]);

        $request->session()->put(self::SESSION_SHIPPING_ADDRESS_ID, $shipping->id);
        $request->session()->put(self::SESSION_BILLING_ADDRESS_ID, $billing->id);

        return back();
    }
}
