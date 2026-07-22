<?php

namespace App\Domain\Orders\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findLatestSucceeded(Order $order): ?Payment;

    public function findLatestPending(Order $order): ?Payment;

    /**
     * @param  array<int, string>  $with
     */
    public function findByProviderPaymentId(string $providerPaymentId, array $with = []): ?Payment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment;

    public function updateAmount(Payment $payment, int $amountCents): void;

    public function markSucceeded(Payment $payment): void;

    public function markRefunded(Payment $payment): void;
}
