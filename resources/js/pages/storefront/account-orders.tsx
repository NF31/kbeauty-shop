import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
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
    itemsCount: number;
    items: OrderItem[];
};

export default function AccountOrdersPage({
    orders,
}: {
    orders: Paginated<OrderSummary>;
}) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            {
                title: t('Mon compte'),
                href: localizedPath('/dashboard', locale),
            },
            { title: t('Mes commandes'), href: '#' },
        ],
    });

    return (
        <>
            <Head title={t('Mes commandes')} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
                <PageHeading title={t('Mes commandes')} />

                {orders.data.length === 0 ? (
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
                        {orders.data.map((order) => (
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
                                                orderNumber: order.orderNumber,
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
                                                    width={200}
                                                    height={200}
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

                <Pagination links={orders.links} />
            </div>
        </>
    );
}
