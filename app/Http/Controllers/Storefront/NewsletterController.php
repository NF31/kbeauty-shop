<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterSubscriptionConfirmation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    /**
     * `consent` n'est jamais persiste tel quel (RGPD : seule la date de consentement compte,
     * pas la valeur de la case a cocher). Double opt-in (13.3) : `consent_at` n'est renseigne
     * qu'a la confirmation du lien recu par email, jamais a la soumission du formulaire - une
     * adresse tierce soumise sans son accord ne recoit qu'un email qu'elle peut ignorer, elle
     * n'est jamais consideree comme inscrite.
     */
    public function store(SubscribeNewsletterRequest $request): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::query()->firstOrNew(
            ['email' => $request->validated('email')],
        );

        if ($subscriber->exists && $subscriber->consent_at && ! $subscriber->unsubscribed_at) {
            Inertia::flash('toast', ['type' => 'success', 'message' => __('Tu es déjà inscrit à la newsletter.')]);

            return back();
        }

        $subscriber->fill(['consent_at' => null, 'unsubscribed_at' => null])->save();
        $subscriber->notify(new NewsletterSubscriptionConfirmation);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Vérifie ta boîte mail pour confirmer ton inscription.')]);

        return back();
    }

    /**
     * Lien signe (URL::temporarySignedRoute, 7 jours) envoye par NewsletterSubscriptionConfirmation
     * - la signature garantit que seul le detenteur de la boite mail peut poser `consent_at`,
     * pas la simple soumission du formulaire (voir store()).
     */
    public function confirm(NewsletterSubscriber $subscriber): RedirectResponse
    {
        if (! $subscriber->consent_at) {
            $subscriber->fill(['consent_at' => now()])->save();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inscription à la newsletter confirmée.')]);

        return redirect('/');
    }
}
