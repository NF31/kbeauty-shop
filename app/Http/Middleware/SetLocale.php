<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fixe la locale de l'application pour la durée de la requête. Prend la
 * locale en paramètre de middleware (`->middleware('locale:en')`) plutôt
 * que via un segment `{locale}` de route — évite les collisions de noms de
 * route entre les groupes FR (par défaut, sans préfixe) et EN (préfixé).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        App::setLocale($locale);

        return $next($request);
    }
}
