import { router, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { PageHeading } from '@/components/storefront/page-heading';
import { SeoHead } from '@/components/storefront/seo-head';
import { Button } from '@/components/ui/button';
import { localizedPath } from '@/lib/locale-path';

type SkinTypeOption = { value: string; label: string };

type RecommendedProduct = {
    id: number;
    slug: string;
    name: string;
    brand: { id: number; name: string } | null;
    defaultVariantId: number | null;
    priceCents: number | null;
    thumbnailUrl: string | null;
};

type DiagnosticResult = {
    diagnosticId: number | null;
    skinType: { value: string; label: string };
    analysis: string;
    recommendedProducts: RecommendedProduct[];
};

type DiagnosticPageProps = {
    skinTypeOptions: SkinTypeOption[];
    result: DiagnosticResult | null;
    seo: { title: string; description: string; image: string | null };
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function DiagnosticPage({
    skinTypeOptions,
    result,
    seo,
}: DiagnosticPageProps) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const [isAnalyzing, setIsAnalyzing] = useState(false);
    const [addingVariantId, setAddingVariantId] = useState<number | null>(null);
    const [addErrors, setAddErrors] = useState<Record<number, string>>({});

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            { title: t('Diagnostic peau'), href: '#' },
        ],
    });

    const diagnosticPath = localizedPath('/diagnostic-peau', locale);
    const cartPath = localizedPath('/panier', locale);

    const analyze = (skinType: string) => {
        setIsAnalyzing(true);

        router.post(
            diagnosticPath,
            { skin_type: skinType },
            {
                preserveScroll: true,
                onFinish: () => setIsAnalyzing(false),
            },
        );
    };

    const addToCart = (variantId: number | null) => {
        if (!variantId) {
            return;
        }

        setAddingVariantId(variantId);
        setAddErrors((errors) => {
            const rest = { ...errors };
            delete rest[variantId];

            return rest;
        });

        router.post(
            cartPath,
            { product_variant_id: variantId, quantity: 1 },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setAddErrors((prev) => ({
                        ...prev,
                        [variantId]:
                            errors.quantity ??
                            t("Impossible d'ajouter ce produit au panier."),
                    })),
                onFinish: () => setAddingVariantId(null),
            },
        );
    };

    return (
        <>
            <SeoHead
                title={seo.title}
                description={seo.description}
                image={seo.image}
            />
            <div className="mx-auto max-w-2xl p-4 md:p-8">
                <div className="mb-6">
                    <PageHeading
                        title={t('Diagnostic peau')}
                        description={t(
                            'Choisis ton type de peau pour recevoir une recommandation adaptee.',
                        )}
                    />
                </div>

                {!result && (
                    <div className="flex flex-col gap-3">
                        {skinTypeOptions.map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                disabled={isAnalyzing}
                                onClick={() => analyze(option.value)}
                                className="rounded-md border p-4 text-center font-medium transition-colors hover:bg-muted disabled:opacity-50"
                            >
                                {option.label}
                            </button>
                        ))}
                    </div>
                )}

                {result && (
                    <div className="flex flex-col gap-6">
                        <div className="rounded-md border p-4">
                            <p className="mb-1 text-sm font-medium text-muted-foreground">
                                {result.skinType.label}
                            </p>
                            <p>{result.analysis}</p>
                        </div>

                        {result.recommendedProducts.length > 0 && (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {result.recommendedProducts.map((product) => (
                                    <div
                                        key={product.id}
                                        className="flex flex-col gap-3 rounded-md border p-4"
                                    >
                                        <div className="flex items-center gap-4">
                                            {product.thumbnailUrl && (
                                                <img
                                                    src={product.thumbnailUrl}
                                                    alt={product.name}
                                                    width={400}
                                                    height={400}
                                                    className="h-20 w-20 rounded-md object-cover"
                                                />
                                            )}
                                            <div className="flex-1">
                                                {product.brand && (
                                                    <p className="text-sm text-muted-foreground">
                                                        {product.brand.name}
                                                    </p>
                                                )}
                                                <p className="font-medium">
                                                    {product.name}
                                                </p>
                                                {product.priceCents !==
                                                    null && (
                                                    <p className="text-sm">
                                                        {euros(
                                                            product.priceCents,
                                                        )}
                                                    </p>
                                                )}
                                            </div>
                                        </div>

                                        <Button
                                            onClick={() =>
                                                addToCart(
                                                    product.defaultVariantId,
                                                )
                                            }
                                            disabled={
                                                !product.defaultVariantId ||
                                                addingVariantId ===
                                                    product.defaultVariantId
                                            }
                                        >
                                            {t('Ajouter au panier')}
                                        </Button>

                                        {product.defaultVariantId &&
                                            addErrors[
                                                product.defaultVariantId
                                            ] && (
                                                <p className="text-sm text-destructive">
                                                    {
                                                        addErrors[
                                                            product
                                                                .defaultVariantId
                                                        ]
                                                    }
                                                </p>
                                            )}
                                    </div>
                                ))}
                            </div>
                        )}

                        <button
                            type="button"
                            onClick={() => router.get(diagnosticPath)}
                            className="text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                        >
                            {t('Refaire le test')}
                        </button>
                    </div>
                )}
            </div>
        </>
    );
}
