<?php

namespace App\Domain\Payments\Contracts;

use App\Domain\Payments\CheckoutSessionResult;
use App\Domain\Payments\CheckoutSessionStatusResult;
use App\Domain\Payments\RefundResult;
use App\Domain\Payments\WebhookEvent;
use App\Models\Order;
use Stripe\Exception\SignatureVerificationException;

interface PaymentGatewayInterface
{
    public function createCheckoutSession(Order $order, string $returnUrl): CheckoutSessionResult;

    public function retrieveCheckoutSession(string $sessionId): CheckoutSessionStatusResult;

    public function refund(string $paymentIntentId, int $amountCents): RefundResult;

    /**
     * @throws SignatureVerificationException
     */
    public function verifyWebhookSignature(string $payload, string $signature): WebhookEvent;
}
