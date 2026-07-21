<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\SkinType;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SkinGuideController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('storefront/skin-guide', [
            'skinTypeOptions' => array_map(
                fn (SkinType $type) => ['value' => $type->value, 'label' => $type->label()],
                SkinType::cases(),
            ),
        ]);
    }
}
