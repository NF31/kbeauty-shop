<?php

namespace App\Application\Orders\UseCases;

use App\Application\Stock\UseCases\RecordStockMovement;
use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Domain\Shared\Contracts\UserRepositoryInterface;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentStatus;
use App\Jobs\SendPlacedOrderEventToKlaviyo;
use App\Notifications\NewPaidOrderAlert;
use App\Notifications\OrderConfirmation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Seule source de vérité pour la confirmation d'un paiement (docs/FEATURES.md
 * 9.4) : appelé uniquement depuis le webhook Stripe `payment_intent.succeeded`,
 * jamais depuis un retour navigateur.
 */
class ConfirmOrderPayment
{
    public function __construct(
        private readonly PaymentRepositoryInterface $payments,
        private readonly OrderRepositoryInterface $orders,
        private readonly RecordStockMovement $recordStockMovement,
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly GenerateOrderInvoice $generateInvoice,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function __invoke(string $providerPaymentId): void
    {
        $payment = $this->payments->findByProviderPaymentId($providerPaymentId, ['order.items', 'order.user']);

        if (! $payment) {
            Log::warning('Webhook Stripe : PaymentIntent sans Payment correspondant.', ['payment_intent_id' => $providerPaymentId]);

            return;
        }

        // Stripe peut renvoyer le même événement plusieurs fois (livraison au moins une fois),
        // y compris en retard après un remboursement complet : ne jamais retraiter un paiement
        // déjà `Succeeded` (double décrément de stock) ni un paiement déjà `Refunded` (ferait
        // repasser la commande en "payée" sans passer par RefundOrder, cf. incident du 2026-08-05).
        if (in_array($payment->status, [PaymentStatus::Succeeded, PaymentStatus::Refunded], true)) {
            return;
        }

        $this->unitOfWork->run(function () use ($payment) {
            $this->payments->markSucceeded($payment);

            $order = $payment->order;
            $this->orders->markPaid($order);

            foreach ($order->items as $item) {
                ($this->recordStockMovement)(
                    $item->variant,
                    InventoryMovementType::Sale,
                    -$item->quantity,
                    "Commande {$order->order_number}",
                );
            }
        });

        $order = $payment->order;

        ($this->generateInvoice)($order);

        $order->user?->notify(new OrderConfirmation($order));
        Notification::send($this->users->admins(), new NewPaidOrderAlert($order));

        SendPlacedOrderEventToKlaviyo::dispatch($order);
    }
}
