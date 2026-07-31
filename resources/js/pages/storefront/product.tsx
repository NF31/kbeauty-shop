import { Link, router, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import type { ProductGalleryImage } from '@/components/storefront/product-gallery';
import { ProductGallery } from '@/components/storefront/product-gallery';
import { QuantitySelector } from '@/components/storefront/quantity-selector';
import { SeoHead } from '@/components/storefront/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

type ProductPageProps = {
    product: {
        id: number;
        name: string;
        short_description: string | null;
        description: string;
        ingredients_inci: string | null;
        how_to_use: string | null;
        brand: { id: number; name: string } | null;
    };
    defaultVariantId: number | null;
    priceCents: number | null;
    compareAtPriceCents: number | null;
    stockQuantity: number | null;
    images: ProductGalleryImage[];
    seo: { title: string; description: string; image: string | null };
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function ProductPage({
    product,
    defaultVariantId,
    priceCents,
    compareAtPriceCents,
    stockQuantity,
    images,
    seo,
}: ProductPageProps) {
    const { t } = useLaravelReactI18n();
    const [quantity, setQuantity] = useState(1);
    const [isAdding, setIsAdding] = useState(false);
    const [addError, setAddError] = useState<string | null>(null);
    const inStock = (stockQuantity ?? 0) > 0;
    const { url, props } = usePage();
    const { locale, appUrl } = props;
    const activeFiltersQueryString = url.split('?')[1] ?? '';
    const productPath = locale === 'en' ? '/en/produits' : '/produits';
    const canonicalPath = url.split('?')[0];

    const jsonLd = [
        {
            '@context': 'https://schema.org',
            '@type': 'Product',
            name: product.name,
            description: seo.description,
            image: images.map((image) => image.url),
            ...(product.brand && {
                brand: { '@type': 'Brand', name: product.brand.name },
            }),
            ...(priceCents !== null && {
                offers: {
                    '@type': 'Offer',
                    priceCurrency: 'EUR',
                    price: (priceCents / 100).toFixed(2),
                    availability: inStock
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                    url: `${appUrl}${canonicalPath}`,
                },
            }),
        },
        {
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: [
                {
                    '@type': 'ListItem',
                    position: 1,
                    name: t('Accueil'),
                    item: `${appUrl}${locale === 'en' ? '/en' : '/'}`,
                },
                {
                    '@type': 'ListItem',
                    position: 2,
                    name: t('Catalogue'),
                    item: `${appUrl}${productPath}`,
                },
                {
                    '@type': 'ListItem',
                    position: 3,
                    name: product.name,
                    item: `${appUrl}${canonicalPath}`,
                },
            ],
        },
    ];

    const addToCart = () => {
        if (!defaultVariantId) {
            return;
        }

        setIsAdding(true);
        setAddError(null);

        router.post(
            locale === 'en' ? '/en/panier' : '/panier',
            { product_variant_id: defaultVariantId, quantity },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setAddError(
                        errors.quantity ??
                            t("Impossible d'ajouter ce produit au panier."),
                    ),
                onFinish: () => setIsAdding(false),
            },
        );
    };

    return (
        <>
            <SeoHead
                title={seo.title}
                description={seo.description}
                image={seo.image}
                type="product"
                jsonLd={jsonLd}
            />
            <div className="mx-auto grid max-w-6xl gap-10 px-4 py-8 md:grid-cols-2 md:gap-16 md:px-16 md:py-16">
                <ProductGallery images={images} productName={product.name} />

                <div className="flex flex-col gap-6">
                    {activeFiltersQueryString && (
                        <Link
                            href={`${locale === 'en' ? '/en' : ''}/produits?${activeFiltersQueryString}`}
                            className="text-sm text-muted-foreground underline"
                        >
                            ← {t('Retour au catalogue filtré')}
                        </Link>
                    )}
                    <div className="space-y-3">
                        {product.brand && (
                            <p className="text-sm text-muted-foreground">
                                {product.brand.name}
                            </p>
                        )}
                        <h1 className="font-heading text-4xl leading-tight font-medium tracking-tight md:text-5xl">
                            {product.name}
                        </h1>
                        {priceCents !== null && (
                            <p className="flex items-baseline gap-3">
                                <span className="text-2xl font-medium">
                                    {euros(priceCents)}
                                </span>
                                {compareAtPriceCents !== null &&
                                    compareAtPriceCents > priceCents && (
                                        <span className="text-base text-muted-foreground line-through">
                                            {euros(compareAtPriceCents)}
                                        </span>
                                    )}
                            </p>
                        )}
                    </div>

                    {inStock ? (
                        <div className="flex flex-col gap-4">
                            <QuantitySelector
                                value={quantity}
                                onChange={setQuantity}
                                max={stockQuantity ?? 1}
                            />

                            <Button
                                size="lg"
                                onClick={addToCart}
                                disabled={isAdding || !defaultVariantId}
                            >
                                {t('Ajouter au panier')}
                            </Button>

                            {addError && (
                                <p className="text-sm text-destructive">
                                    {addError}
                                </p>
                            )}
                        </div>
                    ) : (
                        <Badge
                            variant="outline"
                            className="w-fit text-muted-foreground"
                        >
                            {t('Rupture de stock')}
                        </Badge>
                    )}
                </div>
            </div>

            <div className="mx-auto max-w-6xl border-t px-4 py-8 md:px-16 md:py-16">
                <Tabs defaultValue="benefits">
                    <TabsList>
                        <TabsTrigger value="benefits">
                            {t('Bénéfices')}
                        </TabsTrigger>
                        <TabsTrigger value="description">
                            {t('Description')}
                        </TabsTrigger>
                        <TabsTrigger value="ingredients">
                            {t('Ingrédients')}
                        </TabsTrigger>
                        <TabsTrigger value="how-to-use">
                            {t("Mode d'emploi")}
                        </TabsTrigger>
                        <TabsTrigger value="reviews">{t('Avis')}</TabsTrigger>
                    </TabsList>

                    <TabsContent value="benefits">
                        {product.short_description ? (
                            <p className="leading-relaxed whitespace-pre-line">
                                {product.short_description}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                {t('Aucun bénéfice renseigné pour ce produit.')}
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="description">
                        <p className="leading-relaxed whitespace-pre-line">
                            {product.description}
                        </p>
                    </TabsContent>

                    <TabsContent value="ingredients">
                        {product.ingredients_inci ? (
                            <p className="leading-relaxed whitespace-pre-line">
                                {product.ingredients_inci}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                {t(
                                    'Liste INCI non renseignée pour ce produit.',
                                )}
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="how-to-use">
                        {product.how_to_use ? (
                            <p className="leading-relaxed whitespace-pre-line">
                                {product.how_to_use}
                            </p>
                        ) : (
                            <p className="text-muted-foreground">
                                {t(
                                    "Mode d'emploi non renseigné pour ce produit.",
                                )}
                            </p>
                        )}
                    </TabsContent>

                    <TabsContent value="reviews">
                        <p className="text-muted-foreground">
                            {t('Aucun avis pour le moment.')}
                        </p>
                    </TabsContent>
                </Tabs>
            </div>
        </>
    );
}
