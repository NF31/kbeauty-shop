import { Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { CreditCard, PackageCheck, Sparkles } from 'lucide-react';
import { SeoHead } from '@/components/storefront/seo-head';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { localizedPath } from '@/lib/locale-path';

type HomeProduct = {
    id: number;
    slug: string;
    name: string;
    brand: { id: number; name: string } | null;
    priceCents: number | null;
    compareAtPriceCents: number | null;
    thumbnailUrl: string | null;
};

type HomeBrand = {
    name: string;
    slug: string;
};

type HomePageProps = {
    products: HomeProduct[];
    brands: HomeBrand[];
    seo: { title: string; description: string; image: string | null };
};

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

export default function HomePage({ products, brands, seo }: HomePageProps) {
    const { t } = useLaravelReactI18n();
    const { props } = usePage();
    const { locale } = props;

    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: props.name,
        url: props.appUrl,
        logo: `${props.appUrl}/logo.png`,
    };

    const valueProps = [
        {
            icon: Sparkles,
            iconClassName: 'bg-secondary text-secondary-foreground',
            title: t('Sélection rigoureuse'),
            description: t(
                'Des soins coréens choisis pour leur efficacité, pas pour suivre une tendance.',
            ),
        },
        {
            icon: PackageCheck,
            iconClassName: 'bg-brand-sage text-brand-sage-foreground',
            title: t('Livraison suivie'),
            description: t(
                'Chaque commande est expédiée avec un numéro de suivi.',
            ),
        },
        {
            icon: CreditCard,
            iconClassName: 'bg-brand-gold text-brand-gold-foreground',
            title: t('Paiement sécurisé'),
            description: t('Paiement par carte sécurisé via Stripe.'),
        },
    ];

    return (
        <>
            <SeoHead
                title={seo.title}
                description={seo.description}
                image={seo.image}
                jsonLd={jsonLd}
            />

            <div className="relative overflow-hidden bg-gradient-to-b from-secondary/60 via-secondary/15 to-background">
                <div className="mx-auto flex max-w-6xl flex-col items-center gap-6 px-4 py-16 text-center md:py-24">
                    <img
                        src="/logo-mark.png"
                        alt="Korea Beauty"
                        className="size-20 rounded-full shadow-md md:size-24"
                    />
                    <Badge
                        variant="secondary"
                        className="rounded-full px-4 py-1.5 text-sm"
                    >
                        {t('✨ Nouvelle sélection K-Beauty')}
                    </Badge>
                    <h1 className="font-heading text-4xl leading-tight font-medium tracking-tight md:text-6xl">
                        {t('Le meilleur de la K-Beauty, sélectionné pour vous')}
                    </h1>
                    <p className="max-w-xl text-muted-foreground md:text-lg">
                        {t(
                            'Soins coréens sélectionnés, livrés chez vous. Découvrez notre sélection de marques et de soins pensés pour chaque type de peau.',
                        )}
                    </p>
                    <div className="flex flex-wrap justify-center gap-3">
                        <Button
                            size="lg"
                            className="transition-transform hover:-translate-y-0.5 hover:shadow-lg"
                            asChild
                        >
                            <Link href={localizedPath('/produits', locale)}>
                                {t('Découvrir les produits')}
                            </Link>
                        </Button>
                        <Button
                            size="lg"
                            variant="secondary"
                            className="border border-transparent transition-transform hover:-translate-y-0.5 hover:shadow-lg"
                            asChild
                        >
                            <Link
                                href={localizedPath('/diagnostic-peau', locale)}
                            >
                                {t("Besoin d'aide pour choisir ?")}
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>

            {products.length > 0 && (
                <div className="mx-auto max-w-6xl px-4 py-12 md:py-16">
                    <div className="mb-8 flex items-center justify-between gap-4">
                        <h2 className="font-heading text-2xl font-medium tracking-tight md:text-3xl">
                            {t('Nouveautés')}
                        </h2>
                        <Link
                            href={localizedPath('/produits', locale)}
                            className="text-sm text-muted-foreground underline"
                        >
                            {t('Voir tout')}
                        </Link>
                    </div>

                    <div className="grid grid-cols-2 gap-6 md:grid-cols-4">
                        {products.map((product, index) => (
                            <Link
                                key={product.id}
                                href={localizedPath(
                                    `/produits/${product.slug}`,
                                    locale,
                                )}
                                className="group flex flex-col gap-2"
                            >
                                <div className="relative aspect-square overflow-hidden rounded-md bg-muted shadow-sm transition-shadow duration-300 group-hover:shadow-lg">
                                    {product.thumbnailUrl && (
                                        <img
                                            src={product.thumbnailUrl}
                                            alt={product.name}
                                            // Premiere image de la grille =
                                            // candidate LCP sur la page
                                            // d'accueil.
                                            fetchPriority={
                                                index === 0 ? 'high' : undefined
                                            }
                                            loading={
                                                index === 0 ? undefined : 'lazy'
                                            }
                                            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        />
                                    )}
                                    {product.compareAtPriceCents !== null &&
                                        product.priceCents !== null &&
                                        product.compareAtPriceCents >
                                            product.priceCents && (
                                            <Badge
                                                variant="secondary"
                                                className="absolute top-2 left-2"
                                            >
                                                {t('Promo')}
                                            </Badge>
                                        )}
                                </div>
                                {product.brand && (
                                    <p className="text-xs text-muted-foreground">
                                        {product.brand.name}
                                    </p>
                                )}
                                <p className="text-sm font-medium">
                                    {product.name}
                                </p>
                                {product.priceCents !== null && (
                                    <p className="flex items-baseline gap-2 text-sm text-muted-foreground">
                                        <span>{euros(product.priceCents)}</span>
                                        {product.compareAtPriceCents !== null &&
                                            product.compareAtPriceCents >
                                                product.priceCents && (
                                                <span className="text-xs line-through">
                                                    {euros(
                                                        product.compareAtPriceCents,
                                                    )}
                                                </span>
                                            )}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                </div>
            )}

            {brands.length > 0 && (
                <div className="border-t">
                    <div className="mx-auto max-w-6xl px-4 py-12 md:py-16">
                        <div className="mb-8 flex items-center justify-between gap-4">
                            <h2 className="font-heading text-2xl font-medium tracking-tight md:text-3xl">
                                {t('Nos marques')}
                            </h2>
                            <Link
                                href={localizedPath('/marques', locale)}
                                className="text-sm text-muted-foreground underline"
                            >
                                {t('Voir tout')}
                            </Link>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {brands.map((brand) => (
                                <Link
                                    key={brand.slug}
                                    href={localizedPath(
                                        `/marques/${brand.slug}`,
                                        locale,
                                    )}
                                    className="rounded-full bg-secondary px-4 py-2 text-sm text-secondary-foreground transition-all duration-200 hover:-translate-y-0.5 hover:bg-secondary/70 hover:shadow-md"
                                >
                                    {brand.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                </div>
            )}

            <div className="border-t">
                <div className="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-3 md:py-16">
                    {valueProps.map(
                        ({ icon: Icon, iconClassName, title, description }) => (
                            <div
                                key={title}
                                className="group flex flex-col gap-3"
                            >
                                <div
                                    className={`flex size-11 items-center justify-center rounded-full transition-transform duration-300 group-hover:scale-110 ${iconClassName}`}
                                >
                                    <Icon className="size-5" />
                                </div>
                                <p className="font-medium">{title}</p>
                                <p className="text-sm text-muted-foreground">
                                    {description}
                                </p>
                            </div>
                        ),
                    )}
                </div>
            </div>
        </>
    );
}
