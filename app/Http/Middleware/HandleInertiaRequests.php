<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use App\Services\CloudinaryService;
use App\Support\CartPresenter;
use App\Support\MegaMenuPresenter;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Spatie\Honeypot\Honeypot;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'appUrl' => config('app.url'),
            'gtmId' => config('services.gtm.id'),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames() ?? [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => fn () => app()->getLocale(),
            'cart' => fn () => CartPresenter::present(
                app(CartService::class)->findExisting($request),
                app(CloudinaryService::class),
            ),
            'honeypot' => fn () => app(Honeypot::class)->toArray(),
            'megaMenuCategories' => fn () => MegaMenuPresenter::categories(),
        ];
    }
}
