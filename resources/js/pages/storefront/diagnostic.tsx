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
    scores: Record<string, number> | null;
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
    const [selectedSkinType, setSelectedSkinType] = useState<string | null>(
        null,
    );
    const [photoFile, setPhotoFile] = useState<File | null>(null);
    const [photoError, setPhotoError] = useState<string | null>(null);

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            { title: t('Diagnostic peau'), href: '#' },
        ],
    });

    const diagnosticPath = localizedPath('/diagnostic-peau', locale);
    const cartPath = localizedPath('/panier', locale);

    const analyze = () => {
        if (!selectedSkinType || !photoFile) {
            return;
        }

        setIsAnalyzing(true);
        setPhotoError(null);

        router.post(
            diagnosticPath,
            { skin_type: selectedSkinType, photo: photoFile },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: (errors) => setPhotoError(errors.photo ?? null),
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
                    <div className="flex flex-col gap-6">
                        <div className="flex flex-col gap-3">
                            <p className="text-sm font-medium">
                                {t('Ton type de peau')}
                            </p>
                            <div className="flex flex-col gap-3">
                                {skinTypeOptions.map((option) => (
                                    <button
                                        key={option.value}
                                        type="button"
                                        disabled={isAnalyzing}
                                        onClick={() =>
                                            setSelectedSkinType(option.value)
                                        }
                                        aria-pressed={
                                            selectedSkinType === option.value
                                        }
                                        className={`rounded-md border p-4 text-center font-medium transition-colors hover:bg-muted disabled:opacity-50 ${
                                            selectedSkinType === option.value
                                                ? 'border-foreground bg-muted'
                                                : ''
                                        }`}
                                    >
                                        {option.label}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="flex flex-col gap-2">
                            <label
                                htmlFor="diagnostic-photo"
                                className="text-sm font-medium"
                            >
                                {t('Une photo de ton visage')}
                            </label>
                            <input
                                id="diagnostic-photo"
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                disabled={isAnalyzing}
                                onChange={(e) =>
                                    setPhotoFile(
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                                className="rounded-md border p-2 text-sm disabled:opacity-50"
                            />
                            <p className="text-xs text-muted-foreground">
                                {t(
                                    'Analysee puis jetee immediatement, jamais conservee.',
                                )}
                            </p>
                            {photoError && (
                                <p className="text-sm text-destructive">
                                    {photoError}
                                </p>
                            )}
                        </div>

                        <Button
                            onClick={analyze}
                            disabled={
                                isAnalyzing ||
                                !selectedSkinType ||
                                !photoFile
                            }
                        >
                            {isAnalyzing
                                ? t('Analyse en cours...')
                                : t('Lancer le diagnostic')}
                        </Button>
                    </div>
                )}

                {result && (
                    <div className="flex flex-col gap-6">
                        <div className="rounded-md border p-4">
                            <p className="mb-1 text-sm font-medium text-muted-foreground">
                                {result.skinType.label}
                            </p>
                            <p>{result.analysis}</p>

                            {result.scores && (
                                <div className="mt-4 flex flex-col gap-2">
                                    {Object.entries(result.scores).map(
                                        ([label, value]) => (
                                            <div
                                                key={label}
                                                className="flex items-center gap-3"
                                            >
                                                <span className="w-24 shrink-0 text-xs capitalize text-muted-foreground">
                                                    {label}
                                                </span>
                                                <div className="h-2 flex-1 rounded-full bg-muted">
                                                    <div
                                                        className="h-2 rounded-full bg-foreground"
                                                        style={{
                                                            width: `${value}%`,
                                                        }}
                                                    />
                                                </div>
                                                <span className="w-8 shrink-0 text-right text-xs text-muted-foreground">
                                                    {value}%
                                                </span>
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}
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
                            onClick={() => {
                                setSelectedSkinType(null);
                                setPhotoFile(null);
                                setPhotoError(null);
                                router.get(diagnosticPath);
                            }}
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
