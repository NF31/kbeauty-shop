<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\PaymentIntentResult;
use App\Domain\Payments\RefundResult;
use App\Models\Order;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

interface PaymentGatewayInterface
{
    public function createPaymentIntent(Order $order): PaymentIntentResult;

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntentResult;

    public function updatePaymentIntentAmount(string $paymentIntentId, int $amountCents): PaymentIntentResult;

    public function refund(string $paymentIntentId, int $amountCents): RefundResult;

    /**
     * Le parsing d'un event webhook est intrinsèquement spécifique au provider
     * (Stripe) — l'abstraire complètement coûterait plus que ça n'apporte ici.
     *
     * @throws SignatureVerificationException
     */
    public function verifyWebhookSignature(string $payload, string $signature): Event;
}
