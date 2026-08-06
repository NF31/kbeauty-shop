import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { MapPin, Package } from 'lucide-react';
import { PageHeading } from '@/components/storefront/page-heading';
import { localizedPath } from '@/lib/locale-path';
import { formatMoney } from '@/lib/money';

type OrderItem = {
    productName: string;
    variantLabel: string;
    imageUrl: string | null;
    quantity: number;
    totalCents: number;
};

type OrderSummary = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    totalCents: number;
    currency: string;
    placedAt: string | null;
    items: OrderItem[];
};

export default function Dashboard({
    ordersCount,
    addressesCount,
    recentOrders,
}: {
    ordersCount: number;
    addressesCount: number;
    recentOrders: OrderSummary[];
}) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            { title: t('Mon compte'), href: '#' },
        ],
    });

    return (
        <>
            <Head title={t('Tableau de bord')} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
                <PageHeading title={t('Mon compte')} />

                <div className="grid gap-4 sm:grid-cols-2">
                    <Link
                        href={localizedPath('/mon-compte/commandes', locale)}
                        className="flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-accent"
                    >
                        <Package className="size-5 text-muted-foreground" />
                        <div>
                            <p className="font-medium">{t('Mes commandes')}</p>
                            <p className="text-sm text-muted-foreground">
                                {ordersCount}{' '}
                                {ordersCount > 1
                                    ? t('commandes')
                                    : t('commande')}
                            </p>
                        </div>
                    </Link>
                    <Link
                        href={localizedPath('/mon-compte/adresses', locale)}
                        className="flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-accent"
                    >
                        <MapPin className="size-5 text-muted-foreground" />
                        <div>
                            <p className="font-medium">{t('Mes adresses')}</p>
                            <p className="text-sm text-muted-foreground">
                                {addressesCount}{' '}
                                {addressesCount > 1
                                    ? t('adresses enregistrées')
                                    : t('adresse enregistrée')}
                            </p>
                        </div>
                    </Link>
                </div>

                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-xl font-semibold">
                            {t('Dernières commandes')}
                        </h2>
                        {recentOrders.length > 0 && (
                            <Link
                                href={localizedPath(
                                    '/mon-compte/commandes',
                                    locale,
                                )}
                                className="text-sm underline"
                            >
                                {t('Voir toutes mes commandes')}
                            </Link>
                        )}
                    </div>

                    {recentOrders.length === 0 ? (
                        <div className="rounded-lg border p-8 text-center text-muted-foreground">
                            <p className="mb-4">
                                {t("Vous n'avez pas encore passé de commande.")}
                            </p>
                            <Link
                                href={localizedPath('/produits', locale)}
                                className="underline"
                            >
                                {t('Découvrir les produits')}
                            </Link>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {recentOrders.map((order) => (
                                <Link
                                    key={order.id}
                                    href={localizedPath(
                                        `/mon-compte/commandes/${order.id}`,
                                        locale,
                                    )}
                                    className="block rounded-lg border p-4 transition-colors hover:bg-accent md:p-6"
                                >
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p className="font-medium">
                                                {t('Commande :orderNumber', {
                                                    orderNumber:
                                                        order.orderNumber,
                                                })}
                                            </p>
                                            {order.placedAt && (
                                                <p className="text-sm text-muted-foreground">
                                                    {new Date(
                                                        order.placedAt,
                                                    ).toLocaleDateString(
                                                        locale === 'en'
                                                            ? 'en-GB'
                                                            : 'fr-FR',
                                                        {
                                                            day: 'numeric',
                                                            month: 'long',
                                                            year: 'numeric',
                                                        },
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-medium">
                                                {order.statusLabel}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatMoney(
                                                    order.totalCents,
                                                    order.currency,
                                                )}
                                            </p>
                                        </div>
                                    </div>

                                    <ul className="mt-4 space-y-2">
                                        {order.items.map((item, index) => (
                                            <li
                                                key={index}
                                                className="flex items-center gap-3 text-sm text-muted-foreground"
                                            >
                                                {item.imageUrl ? (
                                                    <img
                                                        src={item.imageUrl}
                                                        alt=""
                                                        loading="lazy"
                                                        className="size-10 shrink-0 rounded-md object-cover"
                                                    />
                                                ) : (
                                                    <span className="size-10 shrink-0 rounded-md bg-muted" />
                                                )}
                                                <span className="min-w-0 break-words">
                                                    {item.quantity} ×{' '}
                                                    {item.productName}
                                                    {item.variantLabel
                                                        ? ` (${item.variantLabel})`
                                                        : ''}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
