import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { Check, Copy, Share2 } from 'lucide-react';
import { PageHeading } from '@/components/storefront/page-heading';
import { Button } from '@/components/ui/button';
import { useClipboard } from '@/hooks/use-clipboard';
import { localizedPath } from '@/lib/locale-path';
import { share } from '@/routes/storefront/account/wishlist';
import { destroy } from '@/routes/storefront/wishlist';

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

export default function WishlistPage({
    products,
    shareToken,
}: {
    products: WishlistProduct[];
    shareToken: string | null;
}) {
    const { t } = useLaravelReactI18n();
    const { locale, appUrl } = usePage().props;
    const [copiedText, copy] = useClipboard();

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            {
                title: t('Mon compte'),
                href: localizedPath('/dashboard', locale),
            },
            { title: t('Mes favoris'), href: '#' },
        ],
    });

    const shareUrl = shareToken
        ? `${appUrl}${localizedPath(`/favoris/partages/${shareToken}`, locale)}`
        : null;

    const remove = (slug: string) => {
        router.delete(destroy(slug).url, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('Mes favoris')} />
            <div className="mx-auto max-w-6xl space-y-6 p-4 md:p-8">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <PageHeading title={t('Mes favoris')} />

                    <div className="flex items-center gap-2">
                        {shareUrl && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => copy(shareUrl)}
                            >
                                {copiedText === shareUrl ? (
                                    <Check className="size-4" />
                                ) : (
                                    <Copy className="size-4" />
                                )}
                                {t('Copier le lien')}
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.post(
                                    share().url,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <Share2 className="size-4" />
                            {shareToken
                                ? t('Régénérer le lien')
                                : t('Partager mes favoris')}
                        </Button>
                    </div>
                </div>

                {products.length === 0 ? (
                    <div className="rounded-lg border p-8 text-center text-muted-foreground">
                        <p className="mb-4">
                            {t("Tu n'as encore aucun produit en favori.")}
                        </p>
                        <Link
                            href={localizedPath('/produits', locale)}
                            className="underline"
                        >
                            {t('Découvrir les produits')}
                        </Link>
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        {products.map((product) => (
                            <div
                                key={product.id}
                                className="group flex flex-col gap-2"
                            >
                                <Link
                                    href={localizedPath(
                                        `/produits/${product.slug}`,
                                        locale,
                                    )}
                                    className="block aspect-square overflow-hidden rounded-md bg-muted"
                                >
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
                                </Link>
                                {product.brand && (
                                    <p className="text-xs text-muted-foreground">
                                        {product.brand.name}
                                    </p>
                                )}
                                <p className="text-sm font-medium">
                                    {product.name}
                                </p>
                                <div className="flex items-center justify-between gap-2">
                                    {product.priceCents !== null && (
                                        <span className="text-sm text-muted-foreground">
                                            {euros(product.priceCents)}
                                        </span>
                                    )}
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="text-muted-foreground"
                                        onClick={() => remove(product.slug)}
                                    >
                                        {t('Retirer')}
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
