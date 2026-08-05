<?php

namespace App\Domain\Orders\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findLatestSucceeded(Order $order): ?Payment;

    public function findLatestPending(Order $order): ?Payment;

    public function findLatest(Order $order): ?Payment;

    /**
     * @param  array<int, string>  $with
     */
    public function findByProviderPaymentId(string $providerPaymentId, array $with = []): ?Payment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment;

    public function replaceProviderPaymentId(Payment $payment, string $providerPaymentId, int $amountCents): void;

    /**
     * Marque le paiement réussi et repointe `provider_payment_id` sur le
     * vrai id du PaymentIntent (jusque-là l'id de la Checkout Session, cf.
     * `ProcessCheckoutPayment`) — nécessaire pour que `RefundOrder` puisse
     * ensuite l'utiliser tel quel avec l'API Stripe des remboursements.
     */
    public function markSucceeded(Payment $payment, string $paymentIntentId): void;

    public function markRefunded(Payment $payment): void;
}
