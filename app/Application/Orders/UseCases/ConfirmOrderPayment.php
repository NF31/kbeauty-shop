<?php

namespace App\Application\Orders\UseCases;

use App\Domain\Orders\Contracts\OrderRepositoryInterface;
use App\Domain\Orders\Contracts\PaymentRepositoryInterface;
use App\Domain\Shared\Contracts\UnitOfWorkInterface;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentStatus;
use App\Models\User;
use App\Notifications\NewPaidOrderAlert;
use App\Notifications\OrderConfirmation;
use App\Services\StockService;
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
        private readonly StockService $stock,
        private readonly UnitOfWorkInterface $unitOfWork,
        private readonly GenerateOrderInvoice $generateInvoice,
    ) {}

    public function __invoke(string $providerPaymentId): void
    {
        $payment = $this->payments->findByProviderPaymentId($providerPaymentId, ['order.items', 'order.user']);

        if (! $payment) {
            Log::warning('Webhook Stripe : PaymentIntent sans Payment correspondant.', ['payment_intent_id' => $providerPaymentId]);

            return;
        }

        // Stripe peut renvoyer le même événement plusieurs fois (livraison au moins une fois) :
        // ne décrémenter le stock qu'une seule fois par paiement.
        if ($payment->status === PaymentStatus::Succeeded) {
            return;
        }

        $this->unitOfWork->run(function () use ($payment) {
            $this->payments->markSucceeded($payment);

            $order = $payment->order;
            $this->orders->markPaid($order);

            foreach ($order->items as $item) {
                $this->stock->recordMovement(
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
        Notification::send(User::role('admin')->get(), new NewPaidOrderAlert($order));
    }
}
