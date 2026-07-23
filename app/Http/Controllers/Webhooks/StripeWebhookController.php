<?php

namespace App\Http\Controllers\Webhooks;

use App\Application\Orders\UseCases\ConfirmOrderPayment;
use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Exception\SignatureVerificationException;

/**
 * Seule source de vérité pour la confirmation d'un paiement (docs/FEATURES.md
 * 9.4) : un retour navigateur sur `/commande/confirmation` (9.2) ne fait
 * jamais passer une commande à `paid`, uniquement ce webhook signé par Stripe.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGatewayInterface $gateway, ConfirmOrderPayment $confirmOrderPayment): Response
    {
        $signature = $request->header('Stripe-Signature');

        if (! $signature) {
            return response('Signature manquante.', 400);
        }

        try {
            $event = $gateway->verifyWebhookSignature($request->getContent(), $signature);
        } catch (SignatureVerificationException) {
            return response('Signature invalide.', 400);
        }

        if ($event->type === 'payment_intent.succeeded' && $event->paymentIntentId) {
            $confirmOrderPayment($event->paymentIntentId);
        }

        return response('', 200);
    }
}
