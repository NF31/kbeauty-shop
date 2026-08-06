<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Payments\Contracts\PaymentGatewayInterface;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessStripeWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\WebhookClient\Models\WebhookCall;
use Stripe\Exception\SignatureVerificationException;

/**
 * Seule source de vérité pour la confirmation d'un paiement (docs/FEATURES.md
 * 9.4) : un retour navigateur sur `/commande/confirmation` (9.2) ne fait
 * jamais passer une commande à `paid`, uniquement ce webhook signé par Stripe.
 */
class StripeWebhookController extends Controller
{
    /**
     * `checkout.session.completed` est l'événement principal ; on écoute
     * aussi `checkout.session.async_payment_succeeded` pour les moyens de
     * paiement à confirmation différée (ex. virements SEPA) où le premier
     * événement arrive avec `payment_status: unpaid`. Dans les deux cas, seul
     * `payment_status === 'paid'` déclenche la confirmation (recommandation
     * Stripe).
     */
    private const PAYMENT_SUCCEEDED_EVENTS = ['checkout.session.completed', 'checkout.session.async_payment_succeeded'];

    public function __invoke(Request $request, PaymentGatewayInterface $gateway): Response
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

        if (
            in_array($event->type, self::PAYMENT_SUCCEEDED_EVENTS, true)
            && $event->paymentStatus === 'paid'
            && $event->sessionId
            && $event->paymentIntentId
        ) {
            $webhookCall = WebhookCall::create([
                'name' => 'stripe',
                'url' => $request->fullUrl(),
                'headers' => $request->headers->all(),
                'payload' => $request->json()->all(),
            ]);

            ProcessStripeWebhookJob::dispatch($webhookCall, $event);
        }

        return response('', 200);
    }
}
