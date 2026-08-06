<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireAccountForCheckout;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Sentry\Laravel\Integration as SentryIntegration;
use Spatie\Csp\AddCspHeaders;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Stripe pose sa propre signature (Stripe-Signature) : le jeton CSRF de session n'a pas de sens ici.
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AddCspHeaders::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'checkout.auth' => RequireAccountForCheckout::class,
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Sans ca, un 429 (throttle) sur une visite Inertia en cours ouvre la
        // fenetre de debug d'Inertia (reponse non reconnue) au lieu d'un
        // message lisible - on redirige vers la page precedente avec un
        // toast d'erreur, meme pattern que RequireAccountForCheckout.
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if (! $request->header('X-Inertia')) {
                return null;
            }

            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('Trop de tentatives, réessaie dans quelques instants.'),
            ]);

            return back();
        });

        // Page d'erreur Inertia (resources/js/pages/error.tsx) au lieu de la
        // page Laravel/Symfony brute par defaut - coherente avec le design
        // du site pour les codes qu'un visiteur peut reellement rencontrer.
        // La locale ne peut pas venir des props partagees Inertia (HandleInertiaRequests
        // ne tourne pas sur une route non matchee, ex. 404) - deduite du prefixe
        // d'URL /en, meme convention que SetLocale.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (app()->hasDebugModeEnabled() || ! in_array($response->getStatusCode(), [403, 404, 419, 500, 503], true)) {
                return $response;
            }

            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'toast' => [
                        'type' => 'error',
                        'message' => __('La page a expiré, réessaie.'),
                    ],
                ]);
            }

            $locale = $request->is('en') || $request->is('en/*') ? 'en' : 'fr';

            return Inertia::render('error', [
                'status' => $response->getStatusCode(),
                'locale' => $locale,
            ])
                ->toResponse($request)
                ->setStatusCode($response->getStatusCode());
        });

        SentryIntegration::handles($exceptions);
    })->create();
