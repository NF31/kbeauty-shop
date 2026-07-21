<?php

namespace App\Http\Controllers\Webhooks;

use App\Enums\InventoryMovementType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\StockService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;

/**
 * Seule source de vérité pour la confirmation d'un paiement (docs/FEATURES.md
 * 9.4) : un retour navigateur sur `/commande/confirmation` (9.2) ne fait
 * jamais passer une commande à `paid`, uniquement ce webhook signé par Stripe.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe, StockService $stockService): Response
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            return response('Signature manquante.', 400);
        }

        try {
            $event = $stripe->constructWebhookEvent($request->getContent(), $signature);
        } catch (SignatureVerificationException) {
            return response('Signature invalide.', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            /** @var PaymentIntent $paymentIntent */
            $paymentIntent = $event->data->object;

            $this->markAsPaid($paymentIntent, $stockService);
        }

        return response('', 200);
    }

    private function markAsPaid(PaymentIntent $paymentIntent, StockService $stockService): void
    {
        $payment = Payment::query()
            ->with('order.items')
            ->where('provider_payment_id', $paymentIntent->id)
            ->first();

        if (! $payment) {
            Log::warning('Webhook Stripe : PaymentIntent sans Payment correspondant.', ['payment_intent_id' => $paymentIntent->id]);

            return;
        }

        // Stripe peut renvoyer le même événement plusieurs fois (livraison au moins une fois) :
        // ne décrémenter le stock qu'une seule fois par paiement.
        if ($payment->status === PaymentStatus::Succeeded) {
            return;
        }

        DB::transaction(function () use ($payment, $stockService) {
            $payment->update(['status' => PaymentStatus::Succeeded, 'paid_at' => now()]);

            $order = $payment->order;
            $order->update(['status' => OrderStatus::Paid]);

            foreach ($order->items as $item) {
                $stockService->recordMovement(
                    $item->variant,
                    InventoryMovementType::Sale,
                    -$item->quantity,
                    "Commande {$order->order_number}",
                );
            }
        });
    }
}
