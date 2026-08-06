<?php

namespace App\Support\Honeypot;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Honeypot\SpamResponder\SpamResponder;

/**
 * Remplace le SpamResponder par défaut du package (qui renvoie une réponse
 * texte brute, incompatible avec le flux de requêtes Inertia) par une
 * redirection back() avec erreur, cohérente avec le reste des formulaires.
 */
class InertiaSpamResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next): RedirectResponse
    {
        return redirect()->back()->withErrors([
            'spam' => __('Ta soumission a été bloquée, réessaie.'),
        ]);
    }
}
