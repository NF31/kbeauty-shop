<?php

namespace App\Application\Cart\UseCases;

use App\Models\Cart;
use App\Notifications\AbandonedCartReminder;
use Illuminate\Support\Carbon;

/**
 * Relance panier abandonné (docs/FEATURES.md 24.1) : un panier compte comme
 * "abandonné" dès que son dernier article ajouté/modifié n'a plus bougé
 * depuis INACTIVITY_THRESHOLD, tant qu'il appartient à un compte (un panier
 * invité n'a pas d'email à qui écrire) et qu'il n'est pas vide. Un seul
 * email par "épisode" d'abandon : abandoned_cart_reminder_sent_at n'est
 * réarmé que si le panier est retouché après ce relevé, pour ne pas
 * spammer un client qui laisse simplement son panier de côté durablement.
 */
class SendAbandonedCartReminders
{
    private const INACTIVITY_THRESHOLD_HOURS = 2;

    public function __invoke(): int
    {
        $threshold = Carbon::now()->subHours(self::INACTIVITY_THRESHOLD_HOURS);

        $sent = 0;

        Cart::query()
            ->whereNotNull('user_id')
            ->whereHas('items')
            ->with(['items.variant.product', 'user'])
            ->each(function (Cart $cart) use ($threshold, &$sent) {
                $lastActivity = $cart->items->max('updated_at');

                if (! $lastActivity || $lastActivity->gt($threshold)) {
                    return;
                }

                if ($cart->abandoned_cart_reminder_sent_at?->gte($lastActivity)) {
                    return;
                }

                $cart->user->notify(new AbandonedCartReminder($cart));
                $cart->update(['abandoned_cart_reminder_sent_at' => now()]);
                $sent++;
            });

        return $sent;
    }
}
