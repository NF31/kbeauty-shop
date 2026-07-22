<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Refund;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Rembourse une commande via l'API Stripe (docs/ARCHITECTURE.md §4). Supporte
 * le remboursement partiel : la commande ne passe à `refunded` (et le paiement
 * à `refunded`) qu'une fois la somme des remboursements réussis égale ou
 * supérieure au total payé — un remboursement partiel laisse le statut de la
 * commande inchangé.
 */
class RefundOrder
{
    public function __construct(private readonly StripeService $stripe) {}

    public function __invoke(Order $order, int $amountCents, ?string $reason = null): Refund
    {
        $payment = $order->payments()
            ->where('status', PaymentStatus::Succeeded)
            ->latest('paid_at')
            ->first();

        if (! $payment) {
            throw new RuntimeException('Aucun paiement réussi à rembourser pour cette commande.');
        }

        $stripeRefund = $this->stripe->refundPayment($payment->provider_payment_id, $amountCents);

        return DB::transaction(function () use ($order, $payment, $amountCents, $reason, $stripeRefund) {
            $refund = Refund::query()->create([
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'amount_cents' => $amountCents,
                'reason' => $reason,
                'status' => $stripeRefund->status === 'succeeded' ? RefundStatus::Succeeded : RefundStatus::Pending,
                'created_at' => now(),
            ]);

            if ($refund->status === RefundStatus::Succeeded) {
                $totalRefundedCents = $order->refunds()
                    ->where('status', RefundStatus::Succeeded)
                    ->sum('amount_cents');

                if ($totalRefundedCents >= $order->total_cents) {
                    $order->update(['status' => OrderStatus::Refunded]);
                    $payment->update(['status' => PaymentStatus::Refunded]);
                }
            }

            return $refund;
        });
    }
}
