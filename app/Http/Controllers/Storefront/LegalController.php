<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function mentions(): Response
    {
        return Inertia::render('storefront/legal/mentions-legales');
    }

    public function cgv(): Response
    {
        return Inertia::render('storefront/legal/cgv');
    }

    public function confidentialite(): Response
    {
        return Inertia::render('storefront/legal/confidentialite');
    }

    public function livraison(): Response
    {
        return Inertia::render('storefront/legal/livraison');
    }

    public function retours(): Response
    {
        return Inertia::render('storefront/legal/retours');
    }
}
