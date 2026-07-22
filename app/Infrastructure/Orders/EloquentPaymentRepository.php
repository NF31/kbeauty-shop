<?php

namespace App\Infrastructure\Orders;

use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function findLatestSucceeded(Order $order): ?Payment
    {
        return $order->payments()
            ->where('status', PaymentStatus::Succeeded)
            ->latest('paid_at')
            ->first();
    }

    public function findLatestPending(Order $order): ?Payment
    {
        return $order->payments()
            ->where('status', PaymentStatus::Pending)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<int, string>  $with
     */
    public function findByProviderPaymentId(string $providerPaymentId, array $with = []): ?Payment
    {
        return Payment::query()
            ->with($with)
            ->where('provider_payment_id', $providerPaymentId)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Payment
    {
        return Payment::query()->create($data);
    }

    public function updateAmount(Payment $payment, int $amountCents): void
    {
        $payment->update(['amount_cents' => $amountCents]);
    }

    public function markSucceeded(Payment $payment): void
    {
        $payment->update(['status' => PaymentStatus::Succeeded, 'paid_at' => now()]);
    }

    public function markRefunded(Payment $payment): void
    {
        $payment->update(['status' => PaymentStatus::Refunded]);
    }
}
