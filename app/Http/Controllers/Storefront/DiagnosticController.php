<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\SkinType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AiCoreDiagnosticClient;
use App\Services\CloudinaryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiagnosticController extends Controller
{
    private const MAX_RECOMMENDED_PRODUCTS = 4;

    public function index(): Response
    {
        return Inertia::render('storefront/diagnostic', [
            'skinTypeOptions' => $this->skinTypeOptions(),
            'result' => null,
            'seo' => $this->seo(),
        ]);
    }

    /**
     * Phase 5 : vraie analyse via kbeauty-ai-core-service (API vision Claude).
     * Si le microservice est injoignable ou refuse l'analyse (ex: cle
     * Anthropic sans credit), on ne fabrique pas un faux resultat a la
     * place - le formulaire est represente avec une erreur, plutot que de
     * presenter un contenu invente comme une vraie analyse.
     */
    public function analyze(Request $request, AiCoreDiagnosticClient $aiCore, CloudinaryService $cloudinary): Response|RedirectResponse
    {
        $validated = $request->validate([
            'skin_type' => ['required', Rule::enum(SkinType::class)],
            'photo' => ['required', 'image', 'max:8192'],
        ]);

        $skinType = SkinType::from($validated['skin_type']);

        $analysis = $aiCore->analyzeSkin($request->file('photo'), $skinType->value);

        if ($analysis === null) {
            return back()->withErrors([
                'photo' => __("L'analyse est indisponible pour le moment. Reessaie dans quelques instants."),
            ]);
        }

        $products = Product::query()
            ->published()
            ->whereJsonContains('skin_types', $skinType->value)
            ->with(['variants', 'primaryImage', 'brand'])
            ->inRandomOrder()
            ->limit(self::MAX_RECOMMENDED_PRODUCTS)
            ->get();

        return Inertia::render('storefront/diagnostic', [
            'skinTypeOptions' => $this->skinTypeOptions(),
            'seo' => $this->seo(),
            'result' => [
                'diagnosticId' => $analysis['diagnosticId'],
                'skinType' => ['value' => $skinType->value, 'label' => $skinType->label()],
                'analysis' => $analysis['analysis'],
                'scores' => $analysis['scores'],
                'recommendedProducts' => $products->map(function (Product $product) use ($cloudinary) {
                    $defaultVariant = $product->variants->firstWhere('is_default', true) ?? $product->variants->first();

                    return [
                        'id' => $product->id,
                        'slug' => $product->slug,
                        'name' => $product->name,
                        'brand' => $product->brand,
                        'defaultVariantId' => $defaultVariant?->id,
                        'priceCents' => $defaultVariant?->price_cents,
                        'thumbnailUrl' => $product->primaryImage
                            ? $cloudinary->url($product->primaryImage->path, 400, 400)
                            : null,
                    ];
                })->all(),
            ],
        ]);
    }

    /**
     * @return array{title: string, description: string, image: null}
     */
    private function seo(): array
    {
        return [
            'title' => __('Diagnostic peau'),
            'description' => __('Reponds a quelques questions pour recevoir une recommandation adaptee a ta peau.'),
            'image' => null,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function skinTypeOptions(): array
    {
        return array_map(
            fn (SkinType $type) => ['value' => $type->value, 'label' => $type->label()],
            SkinType::cases(),
        );
    }
}
