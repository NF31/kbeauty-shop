import { router, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useState } from 'react';
import { PageHeading } from '@/components/storefront/page-heading';
import { SeoHead } from '@/components/storefront/seo-head';
import { Button } from '@/components/ui/button';
import { localizedPath } from '@/lib/locale-path';

type SkinTypeOption = { value: string; label: string };

type DiagnosticResult = {
    diagnosticId: number | null;
    skinType: { value: string; label: string };
    analysis: string;
    recommendedProduct: {
        id: number;
        slug: string;
        name: string;
        brand: { id: number; name: string } | null;
        defaultVariantId: number | null;
        priceCents: number | null;
        thumbnailUrl: string | null;
    } | null;
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
    const [addError, setAddError] = useState<string | null>(null);

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

    const addToCart = () => {
        if (!result?.recommendedProduct?.defaultVariantId) {
            return;
        }

        setAddError(null);

        router.post(
            cartPath,
            {
                product_variant_id: result.recommendedProduct.defaultVariantId,
                quantity: 1,
            },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setAddError(
                        errors.quantity ??
                            t("Impossible d'ajouter ce produit au panier."),
                    ),
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
            <div className="mx-auto max-w-xl p-4 md:p-8">
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

                        {result.recommendedProduct && (
                            <div className="flex items-center gap-4 rounded-md border p-4">
                                {result.recommendedProduct.thumbnailUrl && (
                                    <img
                                        src={
                                            result.recommendedProduct
                                                .thumbnailUrl
                                        }
                                        alt={result.recommendedProduct.name}
                                        className="h-20 w-20 rounded-md object-cover"
                                    />
                                )}
                                <div className="flex-1">
                                    {result.recommendedProduct.brand && (
                                        <p className="text-sm text-muted-foreground">
                                            {
                                                result.recommendedProduct.brand
                                                    .name
                                            }
                                        </p>
                                    )}
                                    <p className="font-medium">
                                        {result.recommendedProduct.name}
                                    </p>
                                    {result.recommendedProduct.priceCents !==
                                        null && (
                                        <p className="text-sm">
                                            {euros(
                                                result.recommendedProduct
                                                    .priceCents,
                                            )}
                                        </p>
                                    )}
                                </div>
                                <Button
                                    onClick={addToCart}
                                    disabled={
                                        !result.recommendedProduct
                                            .defaultVariantId
                                    }
                                >
                                    {t('Ajouter au panier')}
                                </Button>
                            </div>
                        )}

                        {addError && (
                            <p className="text-sm text-destructive">
                                {addError}
                            </p>
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
