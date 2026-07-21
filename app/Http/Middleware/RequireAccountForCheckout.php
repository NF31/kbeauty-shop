<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le tunnel de commande (docs/FEATURES.md 9.1/9.2) exige un compte — pas de
 * checkout invité. `redirect()->guest()` stocke l'URL demandée en session
 * (`url.intended`) ; les réponses Fortify (`LoginResponse`/`RegisterResponse`)
 * font déjà `redirect()->intended()`, donc l'utilisateur revient
 * automatiquement ici une fois connecté ou son compte créé.
 */
class RequireAccountForCheckout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => 'Créez un compte ou connectez-vous pour passer commande.',
            ]);

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
