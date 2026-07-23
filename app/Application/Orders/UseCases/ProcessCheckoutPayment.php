<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Payments\CheckoutPaymentResult;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Models\Order;

/**
 * Étape "Payer" du récapitulatif (docs/ARCHITECTURE.md §4) : crée (ou
 * remet à jour) le `PaymentIntent` Stripe correspondant à la commande. La
 * confirmation définitive du paiement n'arrive jamais ici mais via le
 * webhook Stripe (tâche 9.4, voir ConfirmOrderPayment).
 */
class ProcessCheckoutPayment
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly PaymentRepositoryInterface $payments,
    ) {}

    public function __invoke(Order $order): CheckoutPaymentResult
    {
        $payment = $this->payments->findLatestPending($order);

        if ($payment) {
            // Le statut local reste `pending` tant que le webhook (9.4) n'a
            // pas confirmé le paiement — mais côté Stripe le PaymentIntent
            // peut déjà être `succeeded` (ex. rechargement de la page après
            // un paiement réussi). Stripe refuse de modifier le montant d'un
            // PaymentIntent qui n'est plus modifiable, donc on vérifie son
            // statut réel avant de le mettre à jour.
            $intent = $this->gateway->retrievePaymentIntent($payment->provider_payment_id);

            if ($intent->status === 'succeeded') {
                return CheckoutPaymentResult::alreadySucceeded();
            }

            if (in_array($intent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
                $intent = $this->gateway->updatePaymentIntentAmount($payment->provider_payment_id, $order->total_cents);

                $this->payments->updateAmount($payment, $order->total_cents);
            }

            return CheckoutPaymentResult::pending($intent);
        }

        $intent = $this->gateway->createPaymentIntent($order);

        $this->payments->create([
            'order_id' => $order->id,
            'provider' => PaymentProvider::Stripe,
            'provider_payment_id' => $intent->id,
            'status' => PaymentStatus::Pending,
            'amount_cents' => $order->total_cents,
        ]);

        return CheckoutPaymentResult::pending($intent);
    }
}
