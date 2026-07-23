<?php

namespace App\Http\Controllers\Storefront;

use App\Application\Orders\UseCases\PlaceOrder;
use App\Application\Orders\UseCases\ProcessCheckoutPayment;
use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Enums\AddressType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCheckoutAddressRequest;
use App\Models\Address;
use App\Models\Payment;
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

    private const SESSION_ORDER_ID = 'checkout.order_id';

    public function index(Request $request, CartService $cartService, CloudinaryService $cloudinary): Response|RedirectResponse
    {
        $cart = $cartService->current($request);
        $cart->loadMissing('items');

        if ($cart->items->isEmpty()) {
            return redirect()->route($this->localizedRoute('storefront.cart.index'));
        }

        $shippingAddress = $this->sessionAddress($request, self::SESSION_SHIPPING_ADDRESS_ID);
        $billingAddress = $this->sessionAddress($request, self::SESSION_BILLING_ADDRESS_ID);

        $addresses = $request->user()?->addresses()->orderByDesc('is_default')->orderByDesc('id')->get();

        return Inertia::render('storefront/checkout', [
            'step' => $shippingAddress && $billingAddress ? 'recap' : 'address',
            'cart' => CartPresenter::present($cart, $cloudinary),
            // Adresses déjà enregistrées dans l'espace client (9.6) — permet
            // de choisir une adresse existante plutôt que d'en ressaisir une
            // nouvelle à chaque commande.
            'savedShippingAddresses' => $addresses?->where('type', AddressType::Shipping)->values() ?? [],
            'savedBillingAddresses' => $addresses?->where('type', AddressType::Billing)->values() ?? [],
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
        ]);
    }

    public function storeAddress(StoreCheckoutAddressRequest $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        $shipping = $request->filled('shipping_address_id')
            ? Address::query()->findOrFail($request->integer('shipping_address_id'))
            : Address::query()->create([
                ...$request->validated('shipping'),
                'user_id' => $userId,
                'type' => AddressType::Shipping,
            ]);

        $billingSameAsShipping = $request->boolean('billing_same_as_shipping', true);

        // "Même adresse que la livraison" duplique toujours la ligne en une
        // nouvelle adresse de type billing (même quand `shipping` vient d'une
        // adresse déjà enregistrée) — cohérent avec le comportement d'origine
        // où chaque commande dispose de sa propre ligne `billing`.
        $billing = match (true) {
            $billingSameAsShipping => Address::query()->create([
                'full_name' => $shipping->full_name,
                'line1' => $shipping->line1,
                'line2' => $shipping->line2,
                'postal_code' => $shipping->postal_code,
                'city' => $shipping->city,
                'country_code' => $shipping->country_code,
                'phone' => $shipping->phone,
                'user_id' => $userId,
                'type' => AddressType::Billing,
            ]),
            $request->filled('billing_address_id') => Address::query()->findOrFail($request->integer('billing_address_id')),
            default => Address::query()->create([
                ...$request->validated('billing'),
                'user_id' => $userId,
                'type' => AddressType::Billing,
            ]),
        };

        $request->session()->put(self::SESSION_SHIPPING_ADDRESS_ID, $shipping->id);
        $request->session()->put(self::SESSION_BILLING_ADDRESS_ID, $billing->id);

        return back();
    }

    /**
     * Étape "Payer" du récapitulatif (docs/ARCHITECTURE.md §4) : crée (ou
     * remet à jour) l'`Order` en `pending` à partir du panier courant, puis
     * un `PaymentIntent` Stripe. La confirmation définitive du paiement
     * n'arrive jamais ici mais via le webhook Stripe (tâche 9.4).
     */
    public function pay(
        Request $request,
        CartService $cartService,
        CloudinaryService $cloudinary,
        OrderRepositoryInterface $orders,
        PlaceOrder $placeOrder,
        ProcessCheckoutPayment $processCheckoutPayment,
    ): Response|RedirectResponse {
        $cart = $cartService->current($request);
        $cart->loadMissing('items');

        $shippingAddress = $this->sessionAddress($request, self::SESSION_SHIPPING_ADDRESS_ID);
        $billingAddress = $this->sessionAddress($request, self::SESSION_BILLING_ADDRESS_ID);

        if ($cart->items->isEmpty() || ! $shippingAddress || ! $billingAddress) {
            return redirect()->route($this->localizedRoute('storefront.checkout.index'));
        }

        $orderId = $request->session()->get(self::SESSION_ORDER_ID);
        $existingOrder = $orderId ? $orders->find((int) $orderId) : null;

        $order = $placeOrder($cart, $shippingAddress, $billingAddress, $existingOrder);

        $request->session()->put(self::SESSION_ORDER_ID, $order->id);

        $result = $processCheckoutPayment($order);

        if ($result->alreadySucceeded) {
            return redirect()->route($this->localizedRoute('storefront.checkout.confirmation'));
        }

        return Inertia::render('storefront/checkout', [
            'step' => 'payment',
            'cart' => CartPresenter::present($cart, $cloudinary),
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'order' => [
                'orderNumber' => $order->order_number,
                'totalCents' => $order->total_cents,
                'currency' => $order->currency,
            ],
            'clientSecret' => $result->intent->clientSecret,
            'stripeKey' => config('services.stripe.key'),
            // Préremplit le Payment Element avec ce qu'on a déjà collecté à
            // l'étape adresse — inutile de redemander nom/adresse/téléphone,
            // et l'email du compte s'il est connecté (pas encore capturé pour
            // un invité, cf. tunnel 9.1).
            'customerEmail' => $request->user()?->email,
        ]);
    }

    /**
     * `return_url` de `stripe.confirmPayment()` — nécessaire pour les moyens
     * de paiement avec redirection (ex. iDEAL), même si la CB ne repasse pas
     * par ici. Le *statut de la commande* n'est jamais mis à jour depuis
     * cette page : seul le webhook Stripe (`payment_intent.succeeded`, tâche
     * 9.4) fait foi. On vide en revanche le panier ici si le paiement est
     * confirmé côté Stripe — sinon le client le retrouverait plein en
     * retournant sur /panier après avoir payé.
     */
    public function confirmation(Request $request, CartService $cartService, PaymentGatewayInterface $gateway, OrderRepositoryInterface $orders): Response
    {
        $orderId = $request->session()->get(self::SESSION_ORDER_ID);
        $order = $orderId ? $orders->find((int) $orderId) : null;

        $paymentConfirmed = false;

        if ($order) {
            $payment = Payment::query()->where('order_id', $order->id)->latest('id')->first();

            $paymentConfirmed = $payment && $gateway->retrievePaymentIntent($payment->provider_payment_id)->status === 'succeeded';

            if ($paymentConfirmed) {
                $cartService->findExisting($request)?->items()->delete();

                // Le tunnel de cette commande est terminé — sans ça, un
                // nouveau passage sur /commande avec un panier différent
                // retomberait sur cette même commande déjà payée (session
                // encore porteuse de son shipping/billing/order id).
                $request->session()->forget([
                    self::SESSION_SHIPPING_ADDRESS_ID,
                    self::SESSION_BILLING_ADDRESS_ID,
                    self::SESSION_ORDER_ID,
                ]);
            }
        }

        return Inertia::render('storefront/checkout-confirmation', [
            'orderNumber' => $order?->order_number,
            // Statut Stripe lu en direct, pas le statut local de la commande
            // (qui reste `pending` tant que le webhook 9.4 n'est pas passé) —
            // sinon le client voit "en cours de confirmation" alors que
            // Stripe a déjà répondu "succeeded".
            'paymentConfirmed' => $paymentConfirmed,
        ]);
    }

    private function sessionAddress(Request $request, string $sessionKey): ?Address
    {
        $id = $request->session()->get($sessionKey);

        return $id ? Address::query()->find((int) $id) : null;
    }

    /**
     * Les routes EN du tunnel d'achat (25.1) partagent le meme nom que leur
     * equivalent FR, prefixe `en.` (voir routes/storefront.php). Sans ce
     * prefixage, `route('storefront.cart.index')` resoudrait toujours vers
     * la version FR meme depuis une requete /en/..., cassant la continuite
     * de langue sur les redirections internes.
     */
    private function localizedRoute(string $name): string
    {
        return app()->getLocale() === 'en' ? "en.{$name}" : $name;
    }
}
