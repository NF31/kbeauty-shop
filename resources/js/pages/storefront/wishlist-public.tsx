import { Head, Link, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { PageHeading } from '@/components/storefront/page-heading';
import { localizedPath } from '@/lib/locale-path';

function euros(cents: number): string {
    return (cents / 100).toFixed(2) + ' €';
}

type WishlistProduct = {
    id: number;
    slug: string;
    name: string;
    brand: { id: number; name: string } | null;
    priceCents: number | null;
    thumbnailUrl: string | null;
};

export default function WishlistPublicPage({
    ownerName,
    products,
}: {
    ownerName: string;
    products: WishlistProduct[];
}) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

    return (
        <>
            <Head title={t('Les favoris de :name', { name: ownerName })} />
            <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
                <PageHeading
                    title={t('Les favoris de :name', { name: ownerName })}
                />

                {products.length === 0 ? (
                    <p className="text-muted-foreground">
                        {t('Cette liste de favoris est vide pour le moment.')}
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {products.map((product) => (
                            <Link
                                key={product.id}
                                href={localizedPath(
                                    `/produits/${product.slug}`,
                                    locale,
                                )}
                                className="group flex flex-col gap-2"
                            >
                                <div className="aspect-square overflow-hidden rounded-md bg-muted">
                                    {product.thumbnailUrl && (
                                        <img
                                            src={product.thumbnailUrl}
                                            alt={product.name}
                                            width={400}
                                            height={400}
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                                        />
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
                                    <p className="text-sm text-muted-foreground">
                                        {euros(product.priceCents)}
                                    </p>
                                )}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
