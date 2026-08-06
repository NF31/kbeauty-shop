import { Head, Link, setLayoutProps, usePage } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { PageHeading } from '@/components/storefront/page-heading';
import { localizedPath } from '@/lib/locale-path';
import { formatMoney } from '@/lib/money';

type Address = {
    fullName: string;
    line1: string;
    line2: string | null;
    postalCode: string;
    city: string;
    countryCode: string;
    phone: string | null;
};

type OrderItem = {
    productName: string;
    variantLabel: string;
    imageUrl: string | null;
    quantity: number;
    unitPriceCents: number;
    totalCents: number;
};

type OrderDetail = {
    id: number;
    orderNumber: string;
    status: string;
    statusLabel: string;
    currency: string;
    subtotalCents: number;
    discountCents: number;
    shippingCents: number;
    taxCents: number;
    totalCents: number;
    placedAt: string | null;
    shippingAddress: Address | null;
    billingAddress: Address | null;
    items: OrderItem[];
    hasInvoice: boolean;
    canRequestReturn: boolean;
    returnRequest: { status: string; statusLabel: string } | null;
};

function AddressBlock({
    title,
    address,
}: {
    title: string;
    address: Address | null;
}) {
    if (!address) {
        return null;
    }

    return (
        <div>
            <p className="mb-1 font-medium">{title}</p>
            <p className="text-sm text-muted-foreground">
                {address.fullName}
                <br />
                {address.line1}
                {address.line2 && (
                    <>
                        <br />
                        {address.line2}
                    </>
                )}
                <br />
                {address.postalCode} {address.city}
                <br />
                {address.countryCode}
                {address.phone && (
                    <>
                        <br />
                        {address.phone}
                    </>
                )}
            </p>
        </div>
    );
}

export default function AccountOrderPage({ order }: { order: OrderDetail }) {
    const { t } = useLaravelReactI18n();
    const { locale } = usePage().props;
    const orderTitle = t('Commande :orderNumber', {
        orderNumber: order.orderNumber,
    });

    setLayoutProps({
        breadcrumbs: [
            { title: t('Accueil'), href: localizedPath('/', locale) },
            {
                title: t('Mon compte'),
                href: localizedPath('/dashboard', locale),
            },
            {
                title: t('Mes commandes'),
                href: localizedPath('/mon-compte/commandes', locale),
            },
            { title: orderTitle, href: '#' },
        ],
    });

    return (
        <>
            <Head title={orderTitle} />
            <div className="mx-auto max-w-3xl space-y-6 p-4 md:p-8">
                <Link
                    href={localizedPath('/mon-compte/commandes', locale)}
                    className="text-sm text-muted-foreground underline"
                >
                    ← {t('Mes commandes')}
                </Link>

                <PageHeading
                    title={orderTitle}
                    description={
                        order.placedAt
                            ? new Date(order.placedAt).toLocaleDateString(
                                  locale === 'en' ? 'en-GB' : 'fr-FR',
                                  {
                                      day: 'numeric',
                                      month: 'long',
                                      year: 'numeric',
                                  },
                              )
                            : undefined
                    }
                    actions={
                        <div className="flex items-center gap-3">
                            <p className="text-sm font-medium">
                                {order.statusLabel}
                            </p>
                            {order.status === 'pending' && (
                                <Link
                                    href={localizedPath(
                                        `/commande/${order.id}/reprendre`,
                                        locale,
                                    )}
                                    className="text-sm text-primary underline"
                                >
                                    {t('Continuer le paiement')}
                                </Link>
                            )}
                            {order.hasInvoice && (
                                <a
                                    href={localizedPath(
                                        `/mon-compte/commandes/${order.id}/facture`,
                                        locale,
                                    )}
                                    className="text-sm text-primary underline"
                                >
                                    {t('Télécharger ma facture')}
                                </a>
                            )}
                            {order.canRequestReturn && (
                                <Link
                                    href={localizedPath(
                                        `/mon-compte/commandes/${order.id}/retour`,
                                        locale,
                                    )}
                                    className="text-sm text-primary underline"
                                >
                                    {t('Demander un retour')}
                                </Link>
                            )}
                            {order.returnRequest && (
                                <p className="text-sm text-muted-foreground">
                                    {t('Retour :')}{' '}
                                    {order.returnRequest.statusLabel}
                                </p>
                            )}
                        </div>
                    }
                />

                <div className="rounded-lg border">
                    <ul className="divide-y">
                        {order.items.map((item, index) => (
                            <li
                                key={index}
                                className="grid grid-cols-[auto_minmax(0,1fr)] items-center gap-x-4 gap-y-2 p-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:gap-4"
                            >
                                {item.imageUrl ? (
                                    <img
                                        src={item.imageUrl}
                                        alt=""
                                        width={200}
                                        height={200}
                                        loading="lazy"
                                        className="size-16 rounded-md object-cover"
                                    />
                                ) : (
                                    <span className="size-16 rounded-md bg-muted" />
                                )}
                                <div className="min-w-0 overflow-hidden">
                                    <p className="font-medium break-words">
                                        {item.productName}
                                    </p>
                                    {item.variantLabel && (
                                        <p className="text-sm break-words text-muted-foreground">
                                            {item.variantLabel}
                                        </p>
                                    )}
                                    <p className="text-sm text-muted-foreground">
                                        {item.quantity} ×{' '}
                                        {formatMoney(
                                            item.unitPriceCents,
                                            order.currency,
                                        )}
                                    </p>
                                </div>
                                <p className="col-span-2 justify-self-end font-medium sm:col-span-1 sm:col-start-3">
                                    {formatMoney(
                                        item.totalCents,
                                        order.currency,
                                    )}
                                </p>
                            </li>
                        ))}
                    </ul>

                    <div className="space-y-1 border-t p-4 text-sm">
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                {t('Sous-total')}
                            </span>
                            <span>
                                {formatMoney(
                                    order.subtotalCents,
                                    order.currency,
                                )}
                            </span>
                        </div>
                        {order.discountCents > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    {t('Remise')}
                                </span>
                                <span>
                                    -
                                    {formatMoney(
                                        order.discountCents,
                                        order.currency,
                                    )}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between">
                            <span className="text-muted-foreground">
                                {t('Livraison')}
                            </span>
                            <span>
                                {formatMoney(
                                    order.shippingCents,
                                    order.currency,
                                )}
                            </span>
                        </div>
                        {order.taxCents > 0 && (
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    {t('Taxes')}
                                </span>
                                <span>
                                    {formatMoney(
                                        order.taxCents,
                                        order.currency,
                                    )}
                                </span>
                            </div>
                        )}
                        <div className="flex justify-between pt-1 font-medium">
                            <span>{t('Total')}</span>
                            <span>
                                {formatMoney(order.totalCents, order.currency)}
                            </span>
                        </div>
                    </div>
                </div>

                <div className="grid gap-6 sm:grid-cols-2">
                    <AddressBlock
                        title={t('Adresse de livraison')}
                        address={order.shippingAddress}
                    />
                    <AddressBlock
                        title={t('Adresse de facturation')}
                        address={order.billingAddress}
                    />
                </div>
            </div>
        </>
    );
}
