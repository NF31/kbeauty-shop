<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class NewsletterController extends Controller
{
    /**
     * `consent` n'est jamais persiste tel quel (RGPD : seule la date de consentement compte,
     * pas la valeur de la case a cocher) - `consent_at` sert a la fois de preuve d'opt-in et de
     * marqueur "deja inscrit" pour rendre l'endpoint idempotent (un email soumis deux fois ne
     * duplique pas la ligne, ni ne notifie une erreur a l'utilisateur).
     */
    public function store(SubscribeNewsletterRequest $request): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::query()->firstOrCreate(
            ['email' => $request->validated('email')],
            ['consent_at' => now()],
        );

        $subscriber->fill(['unsubscribed_at' => null])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Inscription à la newsletter confirmée.')]);

        return back();
    }
}
