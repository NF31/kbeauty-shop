<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Orders\Exceptions\NoSucceededPaymentException;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Notifications\RefundConfirmation;
use Illuminate\Support\Facades\DB;

/**
 * Rembourse une commande via l'API Stripe (docs/ARCHITECTURE.md §4). Supporte
 * le remboursement partiel : la commande ne passe à `refunded` (et le paiement
 * à `refunded`) qu'une fois la somme des remboursements réussis égale ou
 * supérieure au total payé — un remboursement partiel laisse le statut de la
 * commande inchangé.
 */
class RefundOrder
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly OrderRepositoryInterface $orders,
        private readonly PaymentRepositoryInterface $payments,
    ) {}

    public function __invoke(Order $order, int $amountCents, ?string $reason = null): Refund
    {
        $payment = $this->payments->findLatestSucceeded($order);

        if (! $payment) {
            throw NoSucceededPaymentException::forOrder();
        }

        $result = $this->gateway->refund($payment->provider_payment_id, $amountCents);

        $refund = DB::transaction(function () use ($order, $payment, $amountCents, $reason, $result) {
            $refund = $this->orders->createRefund([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount_cents' => $amountCents,
                'reason' => $reason,
                'status' => $result->status === 'succeeded' ? RefundStatus::Succeeded : RefundStatus::Pending,
                'created_at' => now(),
            ]);

            if ($refund->status === RefundStatus::Succeeded) {
                $totalRefundedCents = $this->orders->totalSucceededRefundCents($order);

                if ($totalRefundedCents >= $order->total_cents) {
                    $this->orders->markRefunded($order);
                    $this->payments->markRefunded($payment);
                }
            }

            return $refund;
        });

        if ($refund->status === RefundStatus::Succeeded) {
            $order->user->notify(new RefundConfirmation($refund));
        }

        return $refund;
    }
}
