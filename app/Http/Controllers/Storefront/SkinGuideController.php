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
            'seo' => [
                'title' => __('Quel est ton type de peau ?'),
                'description' => __('Réponds à quelques questions pour découvrir les soins coréens adaptés à ton type de peau.'),
                'image' => null,
            ],
        ]);
    }
}
