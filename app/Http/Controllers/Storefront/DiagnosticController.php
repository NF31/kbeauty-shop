<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\SkinType;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AiCoreDiagnosticClient;
use App\Services\CloudinaryService;
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
     * Analyse bidon (voir ROADMAP.md Phase 3) : le contenu du diagnostic est
     * hardcode, seul le flux compte ici. On notifie quand meme le
     * microservice Spring Boot (Phase 2) pour prouver que le pipeline
     * event-driven fonctionne de bout en bout, avant de brancher une vraie
     * IA en Phase 5.
     */
    public function analyze(Request $request, AiCoreDiagnosticClient $aiCore, CloudinaryService $cloudinary): Response
    {
        $validated = $request->validate([
            'skin_type' => ['required', Rule::enum(SkinType::class)],
        ]);

        $skinType = SkinType::from($validated['skin_type']);

        $diagnosticId = $aiCore->createDiagnosticRequest();

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
                'diagnosticId' => $diagnosticId,
                'skinType' => ['value' => $skinType->value, 'label' => $skinType->label()],
                'analysis' => $this->hardcodedAnalysis($skinType),
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

    private function hardcodedAnalysis(SkinType $skinType): string
    {
        return match ($skinType) {
            SkinType::Dry => __('Ta peau manque de lipides : elle tiraille et pele facilement. Privilegie des textures riches (baumes, huiles) et evite les nettoyants moussants agressifs.'),
            SkinType::Oily => __('Production de sebum elevee, surtout sur la zone T. Mise sur des textures legeres et non comedogenes, sans sur-nettoyer (ca relance la production de sebum).'),
            SkinType::Combination => __('Zone T grasse, joues plus seches. Une routine mixte : leger sur le centre du visage, plus riche sur les joues.'),
            SkinType::Sensitive => __('Peau reactive aux changements de temperature et aux actifs forts. Privilegie les formules minimalistes, sans parfum, et introduis les nouveaux actifs un par un.'),
            SkinType::Normal => __("Peau equilibree, ni trop grasse ni trop seche. L'objectif est le maintien : hydratation reguliere et protection solaire."),
            SkinType::Dull => __('Manque d\'eclat, teint irregulier. Les exfoliants doux (acides AHA/BHA a faible dose) et la vitamine C aident a relancer le renouvellement cellulaire.'),
            SkinType::Mature => __("Perte de fermete et rides d'expression. Cible des actifs comme le retinol (en douceur) et les peptides, associes a une hydratation renforcee."),
            SkinType::Dehydrated => __('Manque d\'eau, pas forcement de lipides (une peau grasse peut aussi etre deshydratee). L\'acide hyaluronique et les brumes hydratantes sont tes allies.'),
            SkinType::Acneic => __('Imperfections et inflammations recurrentes. Niacinamide et acide salicylique aident a reguler sans agresser la barriere cutanee.'),
        };
    }
}
