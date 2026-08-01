<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use Symfony\Component\HttpFoundation\Response;

class SitemapController extends Controller
{
    /**
     * Routes statiques (nom sans prefixe `en.`) exposees dans les deux
     * langues. Le panier/tunnel de commande/espace client sont volontairement
     * exclus : deja bloques par robots.txt, contenu prive ou variable par
     * utilisateur.
     *
     * @var array<int, string>
     */
    private const STATIC_ROUTES = [
        'home',
        'storefront.products.index',
        'storefront.brands.index',
        'storefront.skin-guide',
        'storefront.legal.mentions',
        'storefront.legal.cgv',
        'storefront.legal.confidentialite',
        'storefront.legal.livraison',
        'storefront.legal.retours',
    ];

    public function __invoke(): Response
    {
        $sitemap = Sitemap::create();

        foreach (self::STATIC_ROUTES as $name) {
            $this->addLocalizedUrl($sitemap, $name);
        }

        Product::query()
            ->published()
            ->orderBy('slug')
            ->get(['id', 'slug', 'updated_at'])
            ->each(fn (Product $product) => $this->addLocalizedUrl(
                $sitemap,
                'storefront.products.show',
                [$product],
                $product->updated_at,
            ));

        Brand::query()
            ->whereHas('products', fn (Builder $query) => $query->where('status', ProductStatus::Published))
            ->orderBy('slug')
            ->get(['id', 'slug', 'updated_at'])
            ->each(fn (Brand $brand) => $this->addLocalizedUrl(
                $sitemap,
                'storefront.brands.show',
                [$brand],
                $brand->updated_at,
            ));

        return $sitemap->toResponse(request());
    }

    /**
     * Ajoute la version FR et le miroir EN (routes.php: meme nom prefixe
     * `en.`) d'une page, chacune reliee a l'autre via <xhtml:link
     * hreflang> (Url::addAlternate) pour eviter tout souci de contenu
     * duplique aux yeux des moteurs de recherche.
     *
     * @param  array<int, mixed>  $parameters
     */
    private function addLocalizedUrl(
        Sitemap $sitemap,
        string $routeName,
        array $parameters = [],
        ?\DateTimeInterface $lastModified = null,
    ): void {
        $fr = route($routeName, $parameters);
        $en = route('en.'.$routeName, $parameters);

        $frUrl = Url::create($fr)->addAlternate($en, 'en');
        $enUrl = Url::create($en)->addAlternate($fr, 'fr');

        if ($lastModified !== null) {
            $frUrl->setLastModificationDate($lastModified);
            $enUrl->setLastModificationDate($lastModified);
        }

        $sitemap->add($frUrl)->add($enUrl);
    }
}
